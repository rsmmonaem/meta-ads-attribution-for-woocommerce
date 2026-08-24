<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap" style="max-width: 900px;">
    <h1>Meta Ads Attribution Settings</h1>
    <p style="color: #646970;">Configure your Meta Pixel, Conversions API Access Token, Test Event Code, and Qualified Conversion Status.</p>

    <form method="post" action="options.php" style="background: #ffffff; padding: 24px; border: 1px solid #c3c4c7; border-radius: 4px; margin-top: 16px;">
        <?php settings_fields('meta_attribution_settings_group'); ?>
        <?php do_settings_sections('meta_attribution_settings_group'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">Enable System</th>
                    <td>
                        <select name="meta_attribution_enabled">
                            <option value="yes" <?php selected(get_option('meta_attribution_enabled', 'yes'), 'yes'); ?>>Enabled (Active)</option>
                            <option value="no" <?php selected(get_option('meta_attribution_enabled', 'yes'), 'no'); ?>>Disabled</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Enable Browser Pixel</th>
                    <td>
                        <select name="meta_enable_browser_pixel">
                            <option value="yes" <?php selected(get_option('meta_enable_browser_pixel', 'yes'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('meta_enable_browser_pixel', 'yes'), 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Enable Conversions API (CAPI)</th>
                    <td>
                        <select name="meta_enable_capi">
                            <option value="yes" <?php selected(get_option('meta_enable_capi', 'yes'), 'yes'); ?>>Yes (Server-Side)</option>
                            <option value="no" <?php selected(get_option('meta_enable_capi', 'yes'), 'no'); ?>>No</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="meta_pixel_id">Meta Pixel ID</label></th>
                    <td>
                        <input type="text" id="meta_pixel_id" name="meta_pixel_id" value="<?php echo esc_attr(get_option('meta_pixel_id', '')); ?>" class="regular-text">
                        <p class="description">Your Meta Pixel ID from Meta Events Manager (e.g. 123456789012345).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="meta_access_token">CAPI Access Token</label></th>
                    <td>
                        <input type="password" id="meta_access_token" name="meta_access_token" value="<?php echo esc_attr(get_option('meta_access_token', '')); ?>" class="large-text">
                        <p class="description">Your Meta Conversions API Access Token generated in Meta Events Manager settings.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="meta_test_event_code">Test Event Code</label></th>
                    <td>
                        <input type="text" id="meta_test_event_code" name="meta_test_event_code" value="<?php echo esc_attr(get_option('meta_test_event_code', '')); ?>" class="regular-text">
                        <p class="description">Optional: Test event code from Meta Events Manager "Test Events" tab (e.g. TEST12345). Clear in production!</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="meta_qualified_order_status">Qualified Conversion Status</label></th>
                    <td>
                        <select id="meta_qualified_order_status" name="meta_qualified_order_status">
                            <option value="completed" <?php selected(get_option('meta_qualified_order_status', 'completed'), 'completed'); ?>>Completed (Delivered)</option>
                            <option value="processing" <?php selected(get_option('meta_qualified_order_status', 'completed'), 'processing'); ?>>Processing</option>
                            <option value="on-hold" <?php selected(get_option('meta_qualified_order_status', 'completed'), 'on-hold'); ?>>On Hold</option>
                        </select>
                        <p class="description">The WooCommerce order status that triggers sending the qualified Purchase event to Meta.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="meta_attribution_model">Attribution Model</label></th>
                    <td>
                        <select id="meta_attribution_model" name="meta_attribution_model">
                            <option value="first_paid_touch" <?php selected(get_option('meta_attribution_model', 'first_paid_touch'), 'first_paid_touch'); ?>>First Paid Touch (Recommended)</option>
                            <option value="first_touch" <?php selected(get_option('meta_attribution_model', 'first_paid_touch'), 'first_touch'); ?>>First Touch</option>
                            <option value="last_touch" <?php selected(get_option('meta_attribution_model', 'first_paid_touch'), 'last_touch'); ?>>Last Touch</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
