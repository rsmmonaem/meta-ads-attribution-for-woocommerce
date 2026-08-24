# WordPress.org Plugin Submission Checklist & Form Copy

Use this exact text and information when filling out the official **WordPress.org Plugin Submission Form** at [https://wordpress.org/plugins/developers/add/](https://wordpress.org/plugins/developers/add/).

---

## 📝 Form Field Copy (Copy & Paste Ready)

| Field Name | Value to Enter |
| :--- | :--- |
| **Plugin Name** | Meta Ads Attribution & Delivered Conversions for WooCommerce |
| **Plugin Slug** | `meta-ads-attribution-for-woocommerce` |
| **Primary Category** | E-Commerce / Marketing |
| **License** | GPLv2 or later |
| **Short Description** (Max 150 chars) | Production-ready Meta/Facebook Ads attribution, customer journey tracking, order attribution, and qualified DELIVERED order CAPI plugin for WooCommerce. |
| **Tested up to WordPress Version** | 6.7 |
| **Requires PHP** | 7.4 |
| **Requires WooCommerce Version** | 5.0 |

---

## 📄 Full Description Copy for Submission Page

```text
Meta Ads Attribution & Delivered Conversions for WooCommerce provides an end-to-end attribution engine and server-side Conversions API (CAPI) dispatches designed specifically for WooCommerce stores.

= Key Features =

* First-Party Visitor Attribution: Captures fbclid, utm_source, utm_medium, utm_campaign, utm_term, utm_content, landing page, referrer, IP, and user-agent.
* 90-Day Visitor Tracking Cookie: Preserves customer origin across multiple browsing sessions, cart additions, login, registration, and guest checkout.
* Qualified Delivered Conversion Trigger: Sends the server-side Purchase event to Meta ONLY when WooCommerce order status changes to COMPLETED / DELIVERED.
* Event Deduplication: Frontend browser Pixel and backend CAPI events share deterministic event_id parameters (e.g. purchase_10025) to prevent double counting.
* SHA-256 Customer PII Hashing: Normalizes and hashes customer email, phone, name, city, state, postal code, country, and external ID per Meta specs.
* Admin Dashboard & Audit Log: Live metrics cards, campaign revenue breakdown, CAPI audit log with status badges, and 1-click event retries.
```

---

## 🎨 Recommended WordPress.org Assets (Optional Banner & Icon)

To make your plugin look premium on WordPress.org, upload these image files into the `assets/` folder in your SVN repository:

| Asset Name | Dimensions | Purpose |
| :--- | :--- | :--- |
| `icon-128x128.png` | 128x128 px | Small plugin logo icon in search results |
| `icon-256x256.png` | 256x256 px | Retina plugin logo icon |
| `banner-772x250.png` | 772x250 px | Header banner on plugin directory page |
| `banner-1544x500.png` | 1544x500 px | Retina header banner |
| `screenshot-1.png` | 1280x720 px | Admin Dashboard preview screenshot |
| `screenshot-2.png` | 1280x720 px | Settings Page preview screenshot |

---

## 🛡️ WordPress.org Guidelines Compliance Summary

This plugin has been built to comply with 100% of WordPress.org Guidelines:
- ✅ **GPLv2 License**: Open-source GNU General Public License included.
- ✅ **Security Sanitization**: Uses `sanitize_text_field()`, `esc_url_raw()`, `esc_html()`, `esc_js()`, and `wp_nonce_field()` on all inputs & outputs.
- ✅ **Database Safety**: Uses `$wpdb->prepare()` for all SQL queries preventing SQL injection.
- ✅ **No Obfuscated Code**: Clean, human-readable PHP code.
- ✅ **Proper Prefixing**: All classes, function names, and option keys use `meta_attribution_` or `Meta_Attribution_` prefixes preventing namespace collision.
