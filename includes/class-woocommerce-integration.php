<?php

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerce_Meta_Integration
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('woocommerce_checkout_order_processed', array($this, 'attach_attribution_to_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
    }

    public function attach_attribution_to_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $visitor_id = isset($_COOKIE['meta_visitor_id']) ? sanitize_text_field($_COOKIE['meta_visitor_id']) : '';

        global $wpdb;
        $attribution = null;

        if ($visitor_id) {
            $attribution = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meta_ad_attributions WHERE visitor_id = %s LIMIT 1", $visitor_id));
        }

        if (!$attribution && get_current_user_id()) {
            $attribution = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meta_ad_attributions WHERE user_id = %d ORDER BY id DESC LIMIT 1", get_current_user_id()));
        }

        $source = 'direct';
        $is_meta = false;

        if ($attribution) {
            $source = $attribution->utm_source ? $attribution->utm_source : ($attribution->fbclid ? 'facebook' : 'direct');
            $is_meta = !empty($attribution->fbclid) || in_array(strtolower((string)$source), array('facebook', 'meta', 'instagram', 'ig', 'fb'));
        }

        // Store WooCommerce Order Meta
        $order->update_meta_data('_meta_visitor_id', $visitor_id ? $visitor_id : ($attribution ? $attribution->visitor_id : ''));
        $order->update_meta_data('_meta_attribution_source', $is_meta ? 'facebook' : $source);
        $order->update_meta_data('_meta_fbclid', $attribution ? $attribution->fbclid : '');
        $order->update_meta_data('_meta_fbc', $attribution ? $attribution->fbc : '');
        $order->update_meta_data('_meta_fbp', $attribution ? $attribution->fbp : '');
        $order->save();

        // Create Order Attribution Database Record
        $table_order_attributions = $wpdb->prefix . 'meta_order_attributions';
        $wpdb->replace(
            $table_order_attributions,
            array(
                'order_id' => $order_id,
                'order_number' => $order->get_order_number(),
                'visitor_id' => $visitor_id ? $visitor_id : ($attribution ? $attribution->visitor_id : null),
                'user_id' => get_current_user_id() ? get_current_user_id() : ($attribution ? $attribution->user_id : null),
                'attribution_source' => $is_meta ? 'facebook' : $source,
                'attribution_medium' => $attribution ? $attribution->utm_medium : null,
                'campaign' => $attribution ? $attribution->utm_campaign : null,
                'campaign_id' => $attribution ? $attribution->campaign_id : null,
                'adset_id' => $attribution ? $attribution->adset_id : null,
                'ad_id' => $attribution ? $attribution->ad_id : null,
                'fbclid' => $attribution ? $attribution->fbclid : null,
                'fbc' => $attribution ? $attribution->fbc : null,
                'fbp' => $attribution ? $attribution->fbp : null,
                'utm_source' => $attribution ? $attribution->utm_source : null,
                'utm_medium' => $attribution ? $attribution->utm_medium : null,
                'utm_campaign' => $attribution ? $attribution->utm_campaign : null,
                'utm_term' => $attribution ? $attribution->utm_term : null,
                'utm_content' => $attribution ? $attribution->utm_content : null,
                'order_amount' => (float) $order->get_total(),
                'currency' => strtoupper($order->get_currency()),
                'attribution_model' => get_option('meta_attribution_model', 'first_paid_touch'),
                'first_touch_at' => $attribution ? $attribution->first_touch_at : current_time('mysql'),
                'last_touch_at' => $attribution ? $attribution->last_touch_at : current_time('mysql'),
                'attributed_at' => current_time('mysql'),
            )
        );
    }

    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        $qualified_status = get_option('meta_qualified_order_status', 'completed');

        if (strtolower($new_status) === strtolower($qualified_status)) {
            global $wpdb;
            $table_order_attributions = $wpdb->prefix . 'meta_order_attributions';
            $order_attr = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_order_attributions} WHERE order_id = %d LIMIT 1", $order_id));

            $is_meta = $order_attr && (
                $order_attr->attribution_source === 'facebook' ||
                !empty($order_attr->fbclid) ||
                in_array(strtolower((string)$order_attr->utm_source), array('facebook', 'meta', 'instagram', 'ig', 'fb'))
            );

            if ($is_meta) {
                // Dispatch Meta CAPI Delivered Purchase Event
                Meta_CAPI_Service::get_instance()->send_delivered_purchase($order_id);
            }
        }
    }
}
