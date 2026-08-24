<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Attribution_Engine
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
        add_action('template_redirect', array($this, 'capture_attribution'));
    }

    public function capture_attribution()
    {
        if (get_option('meta_attribution_enabled', 'yes') !== 'yes' || is_admin()) {
            return;
        }

        // 1. Resolve or Generate Visitor Cookie (90 Days)
        $cookie_name = 'meta_visitor_id';
        $visitor_id = isset($_COOKIE[$cookie_name]) ? sanitize_text_field($_COOKIE[$cookie_name]) : '';

        if (empty($visitor_id)) {
            $visitor_id = wp_generate_uuid4();
            setcookie($cookie_name, $visitor_id, time() + (86400 * 90), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false);
            $_COOKIE[$cookie_name] = $visitor_id;
        }

        // 2. Extract Query Parameters
        $fbclid = isset($_GET['fbclid']) ? sanitize_text_field($_GET['fbclid']) : null;
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : null;
        $utm_medium = isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : null;
        $utm_campaign = isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : null;
        $utm_term = isset($_GET['utm_term']) ? sanitize_text_field($_GET['utm_term']) : null;
        $utm_content = isset($_GET['utm_content']) ? sanitize_text_field($_GET['utm_content']) : null;

        $campaign_id = isset($_GET['campaign_id']) ? sanitize_text_field($_GET['campaign_id']) : (isset($_GET['ad_campaign_id']) ? sanitize_text_field($_GET['ad_campaign_id']) : null);
        $adset_id = isset($_GET['adset_id']) ? sanitize_text_field($_GET['adset_id']) : null;
        $ad_id = isset($_GET['ad_id']) ? sanitize_text_field($_GET['ad_id']) : null;

        // 3. Resolve Meta Cookies (_fbp, _fbc)
        $fbp = isset($_COOKIE['_fbp']) ? sanitize_text_field($_COOKIE['_fbp']) : null;
        if (!$fbp && isset($_SESSION['meta_fbp'])) {
            $fbp = $_SESSION['meta_fbp'];
        }
        if (!$fbp) {
            $fbp = 'fb.1.' . time() . '.' . rand(100000000, 999999999);
            $_SESSION['meta_fbp'] = $fbp;
        }

        $fbc = isset($_COOKIE['_fbc']) ? sanitize_text_field($_COOKIE['_fbc']) : null;
        if ($fbclid && !$fbc) {
            $fbc = 'fb.1.' . time() . '.' . $fbclid;
            $_SESSION['meta_fbc'] = $fbc;
        }

        $landing_page = esc_url_raw(home_url(add_query_arg($_GET, $GLOBALS['wp']->request)));
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : null;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr($_SERVER['HTTP_USER_AGENT'], 0, 500)) : null;
        $ip_address = $this->get_client_ip();
        $user_id = get_current_user_id();

        global $wpdb;
        $table_ad_attributions = $wpdb->prefix . 'meta_ad_attributions';
        $now = current_time('mysql');

        // 4. Find or Create Attribution Record
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meta_ad_attributions WHERE visitor_id = %s LIMIT 1", $visitor_id));
        $is_meta = !empty($fbclid) || in_array(strtolower((string)$utm_source), array('facebook', 'meta', 'instagram', 'ig', 'fb'));

        if (!$existing) {
            $wpdb->insert(
                $table_ad_attributions,
                array(
                    'visitor_id' => $visitor_id,
                    'user_id' => $user_id ? $user_id : null,
                    'fbclid' => $fbclid,
                    'fbc' => $fbc,
                    'fbp' => $fbp,
                    'utm_source' => $utm_source ? $utm_source : ($is_meta ? 'facebook' : 'direct'),
                    'utm_medium' => $utm_medium ? $utm_medium : ($is_meta ? 'cpc' : null),
                    'utm_campaign' => $utm_campaign,
                    'utm_term' => $utm_term,
                    'utm_content' => $utm_content,
                    'campaign_id' => $campaign_id,
                    'adset_id' => $adset_id,
                    'ad_id' => $ad_id,
                    'landing_page' => $landing_page,
                    'referrer' => $referrer,
                    'user_agent' => $user_agent,
                    'ip_address' => $ip_address,
                    'first_touch_at' => $now,
                    'last_touch_at' => $now,
                )
            );
        } else {
            $update_data = array(
                'last_touch_at' => $now,
                'user_agent' => $user_agent,
                'ip_address' => $ip_address,
            );

            if ($user_id && empty($existing->user_id)) {
                $update_data['user_id'] = $user_id;
            }

            if ($is_meta || $fbclid) {
                if ($fbclid) {
                    $update_data['fbclid'] = $fbclid;
                    $update_data['fbc'] = $fbc;
                }
                if ($utm_source) $update_data['utm_source'] = $utm_source;
                if ($utm_medium) $update_data['utm_medium'] = $utm_medium;
                if ($utm_campaign) $update_data['utm_campaign'] = $utm_campaign;
                if ($utm_term) $update_data['utm_term'] = $utm_term;
                if ($utm_content) $update_data['utm_content'] = $utm_content;
                if ($campaign_id) $update_data['campaign_id'] = $campaign_id;
                if ($adset_id) $update_data['adset_id'] = $adset_id;
                if ($ad_id) $update_data['ad_id'] = $ad_id;
            }

            $wpdb->update($table_ad_attributions, $update_data, array('visitor_id' => $visitor_id));
        }

        // 5. Log Tracking Session
        $table_sessions = $wpdb->prefix . 'meta_tracking_sessions';
        $wpdb->insert(
            $table_sessions,
            array(
                'session_id' => session_id() ? session_id() : wp_generate_password(16, false),
                'visitor_id' => $visitor_id,
                'user_id' => $user_id ? $user_id : null,
                'fbclid' => $fbclid,
                'fbc' => $fbc,
                'fbp' => $fbp,
                'utm_source' => $utm_source,
                'utm_medium' => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'current_url' => $landing_page,
                'referrer' => $referrer,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
            )
        );
    }

    private function get_client_ip()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return $ip;
    }
}
