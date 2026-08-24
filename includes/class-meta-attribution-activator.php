<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Attribution_Activator
{
    public static function activate()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Meta Ad Attributions Table
        $table_ad_attributions = $wpdb->prefix . 'meta_ad_attributions';
        $sql1 = "CREATE TABLE $table_ad_attributions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visitor_id varchar(100) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            fbclid varchar(255) DEFAULT NULL,
            fbc varchar(255) DEFAULT NULL,
            fbp varchar(255) DEFAULT NULL,
            utm_source varchar(100) DEFAULT NULL,
            utm_medium varchar(100) DEFAULT NULL,
            utm_campaign varchar(100) DEFAULT NULL,
            utm_term varchar(100) DEFAULT NULL,
            utm_content varchar(100) DEFAULT NULL,
            campaign_id varchar(100) DEFAULT NULL,
            adset_id varchar(100) DEFAULT NULL,
            ad_id varchar(100) DEFAULT NULL,
            landing_page text DEFAULT NULL,
            referrer text DEFAULT NULL,
            user_agent varchar(500) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            first_touch_at datetime DEFAULT NULL,
            last_touch_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY visitor_id (visitor_id),
            KEY fbclid (fbclid),
            KEY utm_source (utm_source)
        ) $charset_collate;";
        dbDelta($sql1);

        // 2. Meta Tracking Sessions Table
        $table_sessions = $wpdb->prefix . 'meta_tracking_sessions';
        $sql2 = "CREATE TABLE $table_sessions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            visitor_id varchar(100) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            fbclid varchar(255) DEFAULT NULL,
            fbc varchar(255) DEFAULT NULL,
            fbp varchar(255) DEFAULT NULL,
            utm_source varchar(100) DEFAULT NULL,
            utm_medium varchar(100) DEFAULT NULL,
            utm_campaign varchar(100) DEFAULT NULL,
            current_url text DEFAULT NULL,
            referrer text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY visitor_id (visitor_id)
        ) $charset_collate;";
        dbDelta($sql2);

        // 3. Meta Order Attributions Table
        $table_order_attributions = $wpdb->prefix . 'meta_order_attributions';
        $sql3 = "CREATE TABLE $table_order_attributions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            order_number varchar(100) DEFAULT NULL,
            visitor_id varchar(100) DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            attribution_source varchar(100) NOT NULL DEFAULT 'direct',
            attribution_medium varchar(100) DEFAULT NULL,
            campaign varchar(255) DEFAULT NULL,
            campaign_id varchar(100) DEFAULT NULL,
            adset_id varchar(100) DEFAULT NULL,
            ad_id varchar(100) DEFAULT NULL,
            fbclid varchar(255) DEFAULT NULL,
            fbc varchar(255) DEFAULT NULL,
            fbp varchar(255) DEFAULT NULL,
            utm_source varchar(100) DEFAULT NULL,
            utm_medium varchar(100) DEFAULT NULL,
            utm_campaign varchar(100) DEFAULT NULL,
            utm_term varchar(100) DEFAULT NULL,
            utm_content varchar(100) DEFAULT NULL,
            order_amount decimal(12,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            attribution_model varchar(50) NOT NULL DEFAULT 'first_paid_touch',
            first_touch_at datetime DEFAULT NULL,
            last_touch_at datetime DEFAULT NULL,
            attributed_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_id (order_id),
            KEY visitor_id (visitor_id),
            KEY fbclid (fbclid)
        ) $charset_collate;";
        dbDelta($sql3);

        // 4. Meta Conversion Events Table (Audit Log)
        $table_conversion_events = $wpdb->prefix . 'meta_conversion_events';
        $sql4 = "CREATE TABLE $table_conversion_events (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED DEFAULT NULL,
            event_id varchar(100) NOT NULL,
            event_name varchar(100) NOT NULL DEFAULT 'Purchase',
            action_source varchar(50) NOT NULL DEFAULT 'website',
            status varchar(50) NOT NULL DEFAULT 'pending',
            http_status int(11) DEFAULT NULL,
            user_data_hashed longtext DEFAULT NULL,
            custom_data longtext DEFAULT NULL,
            meta_response longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            retry_count int(11) NOT NULL DEFAULT 0,
            first_attempt_at datetime DEFAULT NULL,
            last_attempt_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY event_id (event_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql4);

        // Default Options
        add_option('meta_attribution_enabled', 'yes');
        add_option('meta_enable_browser_pixel', 'yes');
        add_option('meta_enable_capi', 'yes');
        add_option('meta_pixel_id', '');
        add_option('meta_access_token', '');
        add_option('meta_test_event_code', '');
        add_option('meta_qualified_order_status', 'completed');
        add_option('meta_attribution_model', 'first_paid_touch');
    }
}
