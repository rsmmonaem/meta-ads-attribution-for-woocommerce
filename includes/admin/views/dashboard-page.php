<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Compute Metrics
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_visitors_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}meta_ad_attributions WHERE utm_source = 'facebook' OR fbclid IS NOT NULL"));
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_sessions_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}meta_tracking_sessions WHERE utm_source = 'facebook' OR fbclid IS NOT NULL"));
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_total_orders = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}meta_order_attributions WHERE attribution_source = 'facebook' OR fbclid IS NOT NULL"));

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_delivered_orders_count = intval($wpdb->get_var("
    SELECT COUNT(DISTINCT o.order_id) 
    FROM {$wpdb->prefix}meta_order_attributions o
    INNER JOIN {$wpdb->prefix}meta_conversion_events e ON o.order_id = e.order_id
    WHERE e.status = 'sent' AND e.event_name = 'Purchase'
"));

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_delivered_revenue = floatval($wpdb->get_var("
    SELECT SUM(o.order_amount) 
    FROM {$wpdb->prefix}meta_order_attributions o
    INNER JOIN {$wpdb->prefix}meta_conversion_events e ON o.order_id = e.order_id
    WHERE e.status = 'sent' AND e.event_name = 'Purchase'
"));

$meta_conversion_rate = $meta_visitors_count > 0 ? round(($meta_delivered_orders_count / $meta_visitors_count) * 100, 2) : 0.0;

// Campaign Breakdown
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_campaigns = $wpdb->get_results("
    SELECT 
        COALESCE(utm_campaign, campaign, 'Unassigned') as campaign_name,
        COUNT(DISTINCT order_id) as total_orders,
        SUM(order_amount) as total_revenue
    FROM {$wpdb->prefix}meta_order_attributions
    WHERE attribution_source = 'facebook' OR fbclid IS NOT NULL
    GROUP BY campaign_name
");

// CAPI Event Audit Logs
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$meta_conversion_logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}meta_conversion_events ORDER BY id DESC LIMIT 20");
?>

<div class="wrap" style="max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <h1 style="font-size: 1.8rem; font-weight: 700; color: #1d2327; margin-bottom: 8px;">
        Meta Ads Attribution & Delivered Conversions Dashboard
    </h1>
    <p style="color: #646970; font-size: 0.95rem; margin-bottom: 24px;">
        End-to-End WooCommerce Customer Journey Attribution & Qualified Conversions API (CAPI) Dispatches.
    </p>

    <?php 
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['meta_notice']) && sanitize_text_field(wp_unslash($_GET['meta_notice'])) === 'retry_success') : 
    ?>
        <div class="notice notice-success is-dismissible"><p>Event retried and sent to Meta CAPI successfully!</p></div>
    <?php 
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    elseif (isset($_GET['meta_notice']) && sanitize_text_field(wp_unslash($_GET['meta_notice'])) === 'retry_failed') : 
    ?>
        <div class="notice notice-error is-dismissible"><p>Retry failed. Check API credentials or error logs.</p></div>
    <?php endif; ?>

    <!-- Metrics Overview Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #ffffff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; padding: 20px; border-radius: 4px;">
            <div style="font-size: 0.85rem; color: #646970; text-transform: uppercase; font-weight: 600;">Meta Visitors</div>
            <div style="font-size: 2rem; font-weight: 700; color: #2271b1; margin-top: 4px;"><?php echo esc_html(number_format($meta_visitors_count)); ?></div>
            <div style="font-size: 0.75rem; color: #8c8f94; margin-top: 4px;"><?php echo esc_html(number_format($meta_sessions_count)); ?> Total Sessions</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #c3c4c7; border-left: 4px solid #9b51e0; padding: 20px; border-radius: 4px;">
            <div style="font-size: 0.85rem; color: #646970; text-transform: uppercase; font-weight: 600;">Meta Attributed Orders</div>
            <div style="font-size: 2rem; font-weight: 700; color: #9b51e0; margin-top: 4px;"><?php echo esc_html(number_format($meta_total_orders)); ?></div>
            <div style="font-size: 0.75rem; color: #8c8f94; margin-top: 4px;">From Ad Clicks / UTMs</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 20px; border-radius: 4px;">
            <div style="font-size: 0.85rem; color: #646970; text-transform: uppercase; font-weight: 600;">Delivered Meta Orders</div>
            <div style="font-size: 2rem; font-weight: 700; color: #00a32a; margin-top: 4px;"><?php echo esc_html(number_format($meta_delivered_orders_count)); ?></div>
            <div style="font-size: 0.75rem; color: #8c8f94; margin-top: 4px;">Qualified CAPI Conversions</div>
        </div>

        <div style="background: #ffffff; border: 1px solid #c3c4c7; border-left: 4px solid #00a32a; padding: 20px; border-radius: 4px;">
            <div style="font-size: 0.85rem; color: #646970; text-transform: uppercase; font-weight: 600;">Delivered Revenue</div>
            <div style="font-size: 2rem; font-weight: 700; color: #00a32a; margin-top: 4px;">$<?php echo esc_html(number_format($meta_delivered_revenue, 2)); ?></div>
            <div style="font-size: 0.75rem; color: #8c8f94; margin-top: 4px;">Conversion Rate: <?php echo esc_html(number_format($meta_conversion_rate, 2)); ?>%</div>
        </div>
    </div>

    <!-- Campaign Performance Table -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 24px; overflow: hidden;">
        <div style="padding: 14px 20px; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; font-weight: 600; font-size: 1rem;">
            Campaign Performance Breakdown
        </div>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Total Orders</th>
                    <th>Attributed Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($meta_campaigns)) : ?>
                    <?php foreach ($meta_campaigns as $meta_camp) : ?>
                        <tr>
                            <td style="font-weight: 600; color: #2271b1;"><?php echo esc_html($meta_camp->campaign_name); ?></td>
                            <td><?php echo esc_html(number_format($meta_camp->total_orders)); ?></td>
                            <td style="color: #00a32a; font-weight: 600;">$<?php echo esc_html(number_format($meta_camp->total_revenue, 2)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #646970; padding: 20px;">No campaign attribution data recorded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Meta CAPI Audit Log Table -->
    <div style="background: #ffffff; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden;">
        <div style="padding: 14px 20px; background: #f6f7f7; border-bottom: 1px solid #c3c4c7; font-weight: 600; font-size: 1rem;">
            Meta Conversions API (CAPI) Audit Log
        </div>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Order #</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th>HTTP</th>
                    <th>Retries</th>
                    <th>Last Attempt</th>
                    <th>Error / Response Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($meta_conversion_logs)) : ?>
                    <?php foreach ($meta_conversion_logs as $meta_log) : ?>
                        <tr>
                            <td style="font-family: monospace; font-size: 0.85rem;"><?php echo esc_html($meta_log->event_id); ?></td>
                            <td>#<?php echo esc_html($meta_log->order_id); ?></td>
                            <td><?php echo esc_html($meta_log->event_name); ?></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 3px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background-color: <?php echo $meta_log->status === 'sent' ? '#edfaef' : ($meta_log->status === 'failed' ? '#fcf0f1' : '#fcf9e8'); ?>; color: <?php echo $meta_log->status === 'sent' ? '#00a32a' : ($meta_log->status === 'failed' ? '#d63638' : '#dba617'); ?>;">
                                    <?php echo esc_html($meta_log->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($meta_log->http_status ? $meta_log->http_status : '-'); ?></td>
                            <td><?php echo esc_html($meta_log->retry_count); ?></td>
                            <td style="font-size: 0.8rem; color: #646970;"><?php echo esc_html($meta_log->last_attempt_at ? $meta_log->last_attempt_at : '-'); ?></td>
                            <td style="font-size: 0.8rem; color: #50575e; word-break: break-word;">
                                <?php 
                                if ($meta_log->error_message) {
                                    echo '<span style="color: #d63638;">' . esc_html($meta_log->error_message) . '</span>';
                                } else {
                                    echo '<span style="color: #00a32a;">Received OK</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($meta_log->status === 'failed') : ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="meta_attribution_retry_event">
                                        <input type="hidden" name="event_db_id" value="<?php echo esc_attr($meta_log->id); ?>">
                                        <?php wp_nonce_field('meta_retry_event_action', 'meta_retry_nonce'); ?>
                                        <button type="submit" class="button button-secondary button-small">Retry</button>
                                    </form>
                                <?php else : ?>
                                    <span style="color: #8c8f94;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #646970; padding: 20px;">No CAPI conversion events logged yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
