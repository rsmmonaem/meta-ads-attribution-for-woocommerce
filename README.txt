=== Meta Ads Attribution & Delivered Conversions for WooCommerce ===
Contributors: RsmMonaem
Tags: woocommerce, meta, facebook, conversions-api, attribution
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 5.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready Meta/Facebook Ads attribution, order tracking, and delivered Conversions API (CAPI) system for WooCommerce stores.

== Description ==

Meta Ads Attribution & Delivered Conversions for WooCommerce provides an end-to-end attribution and server-side Conversions API (CAPI) engine designed specifically for WooCommerce stores.

= Key Features =

* **First-Party Visitor Attribution**: Captures `fbclid`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, landing page, referrer, IP, and user-agent.
* **90-Day Visitor Tracking Cookie**: Preserves customer origin across multiple browsing sessions, cart additions, login, registration, and guest checkout.
* **Qualified Delivered Conversion Trigger**: Sends the server-side `Purchase` event to Meta **ONLY** when WooCommerce order status changes to COMPLETED / DELIVERED.
* **Event Deduplication**: Frontend browser Pixel and backend CAPI events share deterministic `event_id` parameters (e.g. `purchase_10025`) to prevent double counting.
* **SHA-256 Customer PII Hashing**: Normalizes and hashes customer email, phone, name, city, state, postal code, country, and external ID per Meta specs.
* **Admin Dashboard & Audit Log**: Live metrics cards, campaign revenue breakdown, CAPI audit log with status badges, and 1-click event retries.

== Installation ==

1. Upload the `meta-ads-attribution-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Meta Attribution -> Settings** in WP Admin and enter your Meta Pixel ID and Conversions API Access Token.
4. Save settings and monitor performance under **Meta Attribution -> Dashboard**.

== Changelog ==

= 1.0.0 =
* Initial release.
