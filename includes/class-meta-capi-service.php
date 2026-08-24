<?php

if (!defined('ABSPATH')) {
    exit;
}

class Meta_CAPI_Service
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function send_event($event_name, array $user_data, array $custom_data = array(), $event_id = null, $event_source_url = null, $order_id = null)
    {
        if (get_option('meta_attribution_enabled', 'yes') !== 'yes' || get_option('meta_enable_capi', 'yes') !== 'yes') {
            return array('success' => false, 'status' => 'disabled', 'message' => 'CAPI disabled in settings.');
        }

        $pixel_id = get_option('meta_pixel_id', '');
        $access_token = get_option('meta_access_token', '');
        $test_event_code = get_option('meta_test_event_code', '');

        if (empty($pixel_id) || empty($access_token)) {
            return array('success' => false, 'status' => 'missing_credentials', 'message' => 'Pixel ID or Access Token is missing.');
        }

        $event_id = $event_id ? $event_id : wp_generate_uuid4();
        $event_source_url = $event_source_url ? $event_source_url : home_url();

        global $wpdb;
        $table_events = $wpdb->prefix . 'meta_conversion_events';

        // 1. Check Idempotency (Skip if already sent successfully)
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meta_conversion_events WHERE event_id = %s LIMIT 1", $event_id));
        if ($existing && $existing->status === 'sent') {
            return array('success' => true, 'status' => 'already_sent', 'event_id' => $event_id);
        }

        // 2. Hash Customer Data
        $hashed_user_data = $this->normalize_and_hash_user_data($user_data);

        $event_payload = array(
            'event_name' => $event_name,
            'event_time' => time(),
            'event_id' => $event_id,
            'event_source_url' => $event_source_url,
            'action_source' => 'website',
            'user_data' => $hashed_user_data,
            'custom_data' => $custom_data,
        );

        $request_body = array(
            'data' => array($event_payload),
        );

        if (!empty($test_event_code)) {
            $request_body['test_event_code'] = $test_event_code;
        }

        // Log pending event record
        if (!$existing) {
            $wpdb->insert(
                $table_events,
                array(
                    'order_id' => $order_id,
                    'event_id' => $event_id,
                    'event_name' => $event_name,
                    'action_source' => 'website',
                    'status' => 'pending',
                    'user_data_hashed' => wp_json_encode($hashed_user_data),
                    'custom_data' => wp_json_encode($custom_data),
                    'first_attempt_at' => current_time('mysql'),
                    'last_attempt_at' => current_time('mysql'),
                    'retry_count' => 1,
                )
            );
        } else {
            $wpdb->update(
                $table_events,
                array(
                    'last_attempt_at' => current_time('mysql'),
                    'retry_count' => intval($existing->retry_count) + 1,
                ),
                array('event_id' => $event_id)
            );
        }

        // 3. Send HTTP Request to Meta Graph API v19.0
        $endpoint = "https://graph.facebook.com/v19.0/{$pixel_id}/events?access_token={$access_token}";

        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($request_body),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            $wpdb->update(
                $table_events,
                array('status' => 'failed', 'http_status' => 500, 'error_message' => $error_msg),
                array('event_id' => $event_id)
            );
            return array('success' => false, 'status' => 'failed', 'error' => $error_msg);
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($http_code >= 200 && $http_code < 300) {
            $wpdb->update(
                $table_events,
                array(
                    'status' => 'sent',
                    'http_status' => $http_code,
                    'meta_response' => wp_json_encode($response_body),
                    'sent_at' => current_time('mysql'),
                    'error_message' => null,
                ),
                array('event_id' => $event_id)
            );
            return array('success' => true, 'status' => 'sent', 'http_status' => $http_code, 'event_id' => $event_id, 'meta_response' => $response_body);
        } else {
            $error_msg = isset($response_body['error']['message']) ? $response_body['error']['message'] : wp_remote_retrieve_body($response);
            $wpdb->update(
                $table_events,
                array(
                    'status' => 'failed',
                    'http_status' => $http_code,
                    'meta_response' => wp_json_encode($response_body),
                    'error_message' => $error_msg,
                ),
                array('event_id' => $event_id)
            );
            return array('success' => false, 'status' => 'failed', 'http_status' => $http_code, 'error' => $error_msg);
        }
    }

    public function send_delivered_purchase($order_id)
    {
        if (!class_exists('WooCommerce')) {
            return array('success' => false, 'message' => 'WooCommerce is not active.');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return array('success' => false, 'message' => 'Invalid order ID.');
        }

        $event_id = "purchase_{$order_id}";

        // Gather Order & Customer Metadata
        $user_data = array(
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postal_code' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
            'external_id' => (string) ($order->get_user_id() ? $order->get_user_id() : $order_id),
            'client_ip_address' => $order->get_customer_ip_address(),
            'client_user_agent' => $order->get_customer_user_agent(),
            'fbp' => $order->get_meta('_meta_fbp'),
            'fbc' => $order->get_meta('_meta_fbc'),
        );

        $contents = array();
        foreach ($order->get_items() as $item) {
            $contents[] = array(
                'id' => (string) $item->get_product_id(),
                'quantity' => (int) $item->get_quantity(),
                'item_price' => (float) $item->get_subtotal(),
            );
        }

        $custom_data = array(
            'value' => (float) $order->get_total(),
            'currency' => strtoupper($order->get_currency()),
            'content_type' => 'product',
            'contents' => $contents,
            'order_id' => (string) $order->get_order_number(),
        );

        return $this->send_event('Purchase', $user_data, $custom_data, $event_id, home_url(), $order_id);
    }

    public function normalize_and_hash_user_data(array $user_data)
    {
        $hashed = array();

        if (!empty($user_data['email'])) {
            $hashed['em'] = hash('sha256', strtolower(trim($user_data['email'])));
        }
        if (!empty($user_data['phone'])) {
            $phone = preg_replace('/[^\d]/', '', $user_data['phone']);
            $hashed['ph'] = hash('sha256', $phone);
        }
        if (!empty($user_data['first_name'])) {
            $hashed['fn'] = hash('sha256', strtolower(trim($user_data['first_name'])));
        }
        if (!empty($user_data['last_name'])) {
            $hashed['ln'] = hash('sha256', strtolower(trim($user_data['last_name'])));
        }
        if (!empty($user_data['city'])) {
            $city = preg_replace('/[^a-z]/', '', strtolower(trim($user_data['city'])));
            $hashed['ct'] = hash('sha256', $city);
        }
        if (!empty($user_data['state'])) {
            $state = preg_replace('/[^a-z0-9]/', '', strtolower(trim($user_data['state'])));
            $hashed['st'] = hash('sha256', $state);
        }
        if (!empty($user_data['postal_code'])) {
            $zp = preg_replace('/[\s-]/', '', strtolower(trim($user_data['postal_code'])));
            $hashed['zp'] = hash('sha256', $zp);
        }
        if (!empty($user_data['country'])) {
            $hashed['country'] = hash('sha256', strtolower(trim($user_data['country'])));
        }
        if (!empty($user_data['external_id'])) {
            $hashed['external_id'] = hash('sha256', (string)$user_data['external_id']);
        }

        // Unhashed Technical Identifiers
        if (!empty($user_data['client_ip_address'])) {
            $hashed['client_ip_address'] = $user_data['client_ip_address'];
        }
        if (!empty($user_data['client_user_agent'])) {
            $hashed['client_user_agent'] = $user_data['client_user_agent'];
        }
        if (!empty($user_data['fbp'])) {
            $hashed['fbp'] = $user_data['fbp'];
        }
        if (!empty($user_data['fbc'])) {
            $hashed['fbc'] = $user_data['fbc'];
        }

        return $hashed;
    }
}
