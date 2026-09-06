# Compatibility matrix

Requirements: WordPress 6.4+, WooCommerce 8.5+, PHP 8.1 to 8.4. HPOS and Cart/Checkout Blocks declared compatible in Core (`Compat\Hpos`), theme styles cover the classic checkout and the block checkout through tokens only.

Status values: **coded** = an explicit code path exists (hook, stand down or override); **not tested** = not run against the plugin in this phase. The Phase 8 environment has no WordPress install, so every row below is "not tested" in the runtime sense; the column says what the code does.

| Category | Plugin | Status | How Webgram behaves |
|----------|--------|--------|---------------------|
| Payments | Razorpay for WooCommerce, Stripe | coded, not tested | Checkout uses standard `woocommerce_checkout_*` hooks and `form-checkout.php` at version 9.4.0; payment box left to WooCommerce; invoice reads `get_payment_method_title()` and `get_transaction_id()` from `WC_Order` |
| Shipping | Shiprocket, any tracking plugin | coded, not tested | Tracking number, carrier and URL read through `webgram_core/track_order/data`, `_tracking_number` meta and the WooCommerce Shipment Tracking meta; Shipped status hooks through `webgram_core/notifications/shipped_statuses` |
| Page builder | Elementor Free and Pro | coded, not tested | Widgets registered under the Webgram category only when Elementor loads; theme sections keep working through blocks and shortcodes without it; Sync tokens to Elementor writes the kit colors and fonts |
| SEO | Rank Math, Yoast | coded, not tested | Theme breadcrumbs stand down when `rank_math_the_breadcrumbs` or `yoast_breadcrumb` exists; review JSON-LD extends WooCommerce's product schema instead of printing a second one |
| Caching | WP Rocket, LiteSpeed Cache | coded, not tested | Cart and wishlist counts render through WooCommerce fragments and WC-AJAX so full page cache stays valid; cookies `wg_*` are readable by JS only where needed; no inline nonce printed in cached HTML except through `WebgramCore.nonce` refreshed by `wc-ajax` |
| Variation swatches | any swatches plugin | coded, not tested | Theme swatches are a presentation layer over the standard `variations_form`; setting Single Product > Variation swatches turns them off so the third party UI stands alone |
| Reviews | Judge.me, YITH Reviews, Customer Reviews for WooCommerce | coded, harness | `Reviews\Compat` disables the Webgram reviews UI when one of these is active (harness check) |
| Membership | WooCommerce Memberships, Paid Memberships Pro | coded, not tested | No pricing or visibility logic in Webgram; product cards read prices from `WC_Product` after membership filters |
| Forms | Contact Form 7, WPForms | coded, not tested | Contact and help pages accept a shortcode in the page content or Theme Settings; no form engine shipped |
| Analytics | Site Kit, MonsterInsights | coded, not tested | Webgram analytics is first party and additive; `webgram_core/event` lets a GA plugin bridge map events |
| Subscriptions | WooCommerce Subscriptions | coded, not tested | Order hooks use `WC_Order` APIs and status transitions; renewal orders trigger the same notification events |
| Multilingual | WPML, Polylang | coded, not tested | `wpml-config.xml` shipped for both products, all strings translatable, CPTs registered with `show_in_rest` and standard labels |
| Object cache | Redis Object Cache | coded, not tested | `Support\Cache` uses `wp_cache_*` with groups and transient fallback |

Browser support targets: last two versions of Chrome, Firefox, Safari and Edge, iOS Safari 15+. Voice search shows the mic only when the browser exposes `SpeechRecognition`.

Template overrides (8 of the allowed 12, all at `@version 9.4.0`): `content-product.php`, `single-product.php`, `cart/cart.php`, `checkout/form-checkout.php`, `checkout/thankyou.php`, `myaccount/form-login.php`, `myaccount/navigation.php`, `global/quantity-input.php`. Compare against the WooCommerce release notes when a new major version changes these files.

Layouts: when a Core Layout replaces the single product page, the theme fires `woocommerce_before_single_product_summary` and `woocommerce_single_product_summary` after the layout with WooCommerce's and the theme's default callbacks removed, so plugin output attached to those hooks still prints. Layout and popup device targeting uses the User-Agent on the server (layouts) or the viewport in the browser (popups); the server side check is not reliable behind a full page cache, so prefer separate layouts by condition rather than by device on cached sites.
