<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Pixel_Renderer
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
        add_action('wp_head', array($this, 'render_pixel_header'));
        add_action('wp_footer', array($this, 'render_page_events'));
    }

    public function render_pixel_header()
    {
        if (get_option('meta_attribution_enabled', 'yes') !== 'yes' || get_option('meta_enable_browser_pixel', 'yes') !== 'yes') {
            return;
        }

        $pixel_id = get_option('meta_pixel_id', '');
        if (empty($pixel_id)) return;

        ?>
        <!-- Meta Pixel Code by Meta Ads Attribution for WooCommerce -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        
        fbq('init', '<?php echo esc_js($pixel_id); ?>');
        fbq('track', 'PageView');

        window.metaFbqTrack = function(eventName, customData = {}, eventId = null) {
            if (typeof fbq === 'function') {
                const options = eventId ? { eventID: eventId } : {};
                fbq('track', eventName, customData, options);
            }
        };
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel_id); ?>&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Meta Pixel Code -->
        <?php
    }

    public function render_page_events()
    {
        if (!class_exists('WooCommerce') || get_option('meta_attribution_enabled', 'yes') !== 'yes') {
            return;
        }

        // 1. Single Product ViewContent Event
        if (is_product()) {
            global $product;
            if ($product) {
                $event_id = 'view_content_' . $product->get_id() . '_' . time();
                ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.metaFbqTrack === 'function') {
                        window.metaFbqTrack('ViewContent', {
                            content_name: '<?php echo esc_js($product->get_name()); ?>',
                            content_ids: ['<?php echo esc_js($product->get_id()); ?>'],
                            content_type: 'product',
                            value: <?php echo floatval($product->get_price()); ?>,
                            currency: '<?php echo esc_js(get_woocommerce_currency()); ?>'
                        }, '<?php echo esc_js($event_id); ?>');
                    }
                });
                </script>
                <?php
            }
        }

        // 2. InitiateCheckout Event
        if (is_checkout() && !is_order_received_page()) {
            $event_id = 'initiate_checkout_' . time();
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.metaFbqTrack === 'function') {
                    window.metaFbqTrack('InitiateCheckout', {
                        value: <?php echo floatval(WC()->cart ? WC()->cart->get_total('edit') : 0); ?>,
                        currency: '<?php echo esc_js(get_woocommerce_currency()); ?>',
                        num_items: <?php echo intval(WC()->cart ? WC()->cart->get_cart_contents_count() : 0); ?>
                    }, '<?php echo esc_js($event_id); ?>');
                }
            });
            </script>
            <?php
        }
    }
}
