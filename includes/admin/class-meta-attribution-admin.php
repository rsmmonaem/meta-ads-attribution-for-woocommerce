<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meta_Attribution_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_meta_attribution_retry_event', array($this, 'handle_retry_event'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Meta Ads Attribution',
            'Meta Attribution',
            'manage_options',
            'meta-ads-attribution',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-bar',
            56
        );

        add_submenu_page(
            'meta-ads-attribution',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'meta-ads-attribution',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'meta-ads-attribution',
            'Settings',
            'Settings',
            'manage_options',
            'meta-ads-attribution-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings()
    {
        $sanitize_text_args = array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        );

        register_setting('meta_attribution_settings_group', 'meta_attribution_enabled', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'yes',
        ));

        register_setting('meta_attribution_settings_group', 'meta_enable_browser_pixel', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'yes',
        ));

        register_setting('meta_attribution_settings_group', 'meta_enable_capi', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'yes',
        ));

        register_setting('meta_attribution_settings_group', 'meta_pixel_id', $sanitize_text_args);
        register_setting('meta_attribution_settings_group', 'meta_access_token', $sanitize_text_args);
        register_setting('meta_attribution_settings_group', 'meta_test_event_code', $sanitize_text_args);

        register_setting('meta_attribution_settings_group', 'meta_qualified_order_status', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'completed',
        ));

        register_setting('meta_attribution_settings_group', 'meta_attribution_model', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'first_paid_touch',
        ));
    }

    public function render_dashboard_page()
    {
        require_once META_ATTRIBUTION_WC_PATH . 'includes/admin/views/dashboard-page.php';
    }

    public function render_settings_page()
    {
        require_once META_ATTRIBUTION_WC_PATH . 'includes/admin/views/settings-page.php';
    }

    public function handle_retry_event()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('meta_retry_event_action', 'meta_retry_nonce');

        $event_db_id = isset($_POST['event_db_id']) ? intval(wp_unslash($_POST['event_db_id'])) : 0;

        if ($event_db_id > 0) {
            global $wpdb;
            $event_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meta_conversion_events WHERE id = %d LIMIT 1", $event_db_id));

            if ($event_log && $event_log->order_id) {
                $result = Meta_CAPI_Service::get_instance()->send_delivered_purchase($event_log->order_id);
                if ($result['success']) {
                    wp_safe_redirect(add_query_arg(array('page' => 'meta-ads-attribution', 'meta_notice' => 'retry_success'), admin_url('admin.php')));
                    exit;
                }
            }
        }

        wp_safe_redirect(add_query_arg(array('page' => 'meta-ads-attribution', 'meta_notice' => 'retry_failed'), admin_url('admin.php')));
        exit;
    }
}
