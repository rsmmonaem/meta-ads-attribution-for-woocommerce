<?php
/**
 * Plugin Name:       Meta Ads Attribution & Delivered Conversions for WooCommerce
 * Plugin URI:        https://github.com/rsmmonaem/meta-ads-attribution-for-woocommerce
 * Description:       Production-ready Meta/Facebook Ads attribution, customer journey tracking, order attribution, and delivered-order Conversions API (CAPI) system for WooCommerce.
 * Version:           1.0.0
 * Author:            RsmMonaem
 * Author URI:        https://github.com/rsmmonaem
 * Text Domain:       meta-ads-attribution-wc
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('META_ATTRIBUTION_WC_VERSION', '1.0.0');
define('META_ATTRIBUTION_WC_FILE', __FILE__);
define('META_ATTRIBUTION_WC_PATH', plugin_dir_path(__FILE__));
define('META_ATTRIBUTION_WC_URL', plugin_dir_url(__FILE__));

// Require Core Files
require_once META_ATTRIBUTION_WC_PATH . 'includes/class-meta-attribution-activator.php';
require_once META_ATTRIBUTION_WC_PATH . 'includes/class-meta-attribution-engine.php';
require_once META_ATTRIBUTION_WC_PATH . 'includes/class-meta-capi-service.php';
require_once META_ATTRIBUTION_WC_PATH . 'includes/class-meta-pixel-renderer.php';
require_once META_ATTRIBUTION_WC_PATH . 'includes/class-woocommerce-integration.php';
require_once META_ATTRIBUTION_WC_PATH . 'includes/admin/class-meta-attribution-admin.php';

// Activation & Deactivation Hooks
register_activation_hook(__FILE__, array('Meta_Attribution_Activator', 'activate'));

// Initialize Core Plugin Services
function run_meta_ads_attribution_wc() {
    Meta_Attribution_Engine::get_instance();
    Meta_Pixel_Renderer::get_instance();
    WooCommerce_Meta_Integration::get_instance();
    Meta_Attribution_Admin::get_instance();
}
add_action('plugins_loaded', 'run_meta_ads_attribution_wc');
