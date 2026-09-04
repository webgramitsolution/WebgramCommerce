# Phase 2: Product card, shop archive, product detail page, WooEnhancements, Badges, Quick View, Coupons (0.3.0)

### A. Implemented

Webgram Theme
- Product card (spec 4.2): white card, radius from tokens, 1:1 or 3:4 image, wave badge (SVG mask with gold underline; pill, rectangle and ribbon shapes via Theme Settings), Core badges slot, quick action buttons (hover, always or hidden), hover effect none / swap / slideshow (gallery URLs printed as data, images created on first hover, auto-cycle at the configured interval, dot row and swipe on touch), title with configurable line clamp, one price line (sale 18px bold, regular strike, green percent) with the rating pill at the right end (or under the title, or hidden), variation chips for one attribute (global setting or per-product choice; text chips, color circles or image thumbs; "+N" chip; clicking updates price, image and turns the loop button into an AJAX add-to-cart for the chosen variation), 44px outline cart icon button plus full-width BUY NOW with optional shine. List variant (200px image, short description) and compact variant (64px thumb, ADD button). `Webgram_WC_Product_Card` prepares price parts, savings, chips JSON and a `rating_pill()` helper reused on the PDP; loop button classes rebuilt so WooCommerce AJAX add-to-cart keeps working.
- Shop archive (spec 4.5), hooks only (no `archive-product.php` override): category banner as the page title band with the term image and description (top, bottom or hidden), subcategory chips (circle / square / rounded), toolbar (filters button, result count, sort, grid/list toggle remembered in a cookie), columns per device via CSS variables, per page count, filters as sticky sidebar or off-canvas drawer with collapsible widgets and the `webgram/shop/filters` hook, pagination as numbers, load more or infinite scroll, AJAX filtering/sorting/pagination with URL state (`shop.js`, `shop.css` loaded only on archives).
- Product detail page (spec 4.4): `woocommerce/single-product.php` override with two columns inside a cream panel section (flat option), sticky gallery released when the summary is shorter or under 992px, custom gallery (main image with cursor-following zoom, arrows, wave badge, gallery action slot, horizontal or vertical thumbnail strip with scroll arrows and visible count, auto slide until interaction with pause on hover, video slide from `_webgram_video_url` for MP4, YouTube and Vimeo, lightbox with keyboard navigation, swipe, dots on mobile), price line (32px sale, strike, green percent, large rating pill linking to reviews), image variant swatches (120px cards with image, name, price, check icon, out-of-stock strike, disabled state) driving the standard variations form, quantity stepper override (`global/quantity-input.php`), Add to cart plus Core Buy now, payment strip (title, processor logo, method icons, caption), trust seals (3 to 6 circular seals), info cards, specifications (Core table or attributes fallback), overview, share row (networks from Social profiles plus copy link), meta, section order and below-fold order from Theme Settings (unknown or disabled Core sections skipped), stacked sections with mobile accordions or WooCommerce tabs, related products with ornament heading, reviews block (WooCommerce default until Phase 4), mobile sticky bar mirroring the form buttons. `product.css` and `product.js` load on product pages only.
- Page templates "Track Order" (centered card with the Core shortcode) and "Bulk Order" (form, benefits list from Core settings, WhatsApp button). Section heading ornament styles, toast, offer progress, quick view, coupon box, contact cards, pincode checker, specifications and timeline styles for Core components.

Webgram Core
- WooEnhancements completed: BuyNow (product form button and card link, optional empty cart, redirect to checkout through `woocommerce_add_to_cart_redirect`), RecentlyViewed (cookie `wg_recent`, shortcode, product page row through the standard `content-product` template so any theme's card renders), Specifications (attributes merged with per-product rows, source setting, shortcode), ProductVideo (in the product panel), ContactSeller (call, WhatsApp `wa.me` with prefilled product name and URL or chat URL, bulk quote card; global settings with per-product overrides), BulkInquiry (modal on product pages and full form for the Bulk Order page, honeypot, nonce, rate limit 5 per hour per IP hash, private `wg_inquiry` posts with status, admin columns and details metabox, `wp_mail` notification), TrackOrder (shortcode, `POST webgram/v1/track-order` with REST nonce and rate limit 10 per hour, lookup by order number and billing email or phone, status timeline, items, shipment data from common tracking metas and `webgram_core/track_order/data`), PincodeChecker product page block, Webgram product data tab (chip attribute, video, specs repeater, contact overrides) with extension hooks.
- Badges: pure rule engine (custom text first, sold out, sale percent or text, best seller threshold, new arrival days, low stock; maximum count) rendered on cards and the gallery; product fields in the Webgram tab or a fallback metabox.
- QuickView: card action button, `wc-ajax=webgram_quick_view`, modal shell printed once, content with gallery thumbnails, rating, price, short description, variations form (WooCommerce variation script initialised inside the modal), add to cart, closes after adding.
- Coupons: per-product coupon selector (or default code), live check (published, not expired, usage limit), headline from type or description, box template with copy to clipboard and toast, shortcode `[webgram_coupon]`; OfferProgress with milestones from settings (amount or quantity thresholds with optional codes) plus the free shipping minimum from shipping zones, pure `compute()`, rendering on `webgram/cart/before_items` and as a cart fragment, `wc-ajax=webgram_coupon_progress`, optional auto apply and removal of milestone coupons.

### B. Files and modules changed

Theme: `functions.php`, `style.css`, `package.json` (shop and product bundles), `inc/enqueue.php`, `inc/template-hooks.php`, `inc/woocommerce/class-wc-product-card.php`, `class-wc-setup.php`, `class-wc-shop.php` (new), `class-wc-product.php` (new), `woocommerce/single-product.php`, `woocommerce/global/quantity-input.php`, `template-parts/cards/product-card{,-list,-compact}.php`, `template-parts/shop/*`, `template-parts/product/*`, `template-parts/misc/share.php`, `page-templates/*`, `assets/src/scss/{shop,product}.scss`, `woocommerce/_shop.scss`, `woocommerce/_product.scss`, `components/_card.scss`, `_badge.scss`, `_rating.scss`, `_section-heading.scss`, `_core.scss`, `assets/src/js/{shop,product}.js`, `modules/product-card.js`, `modules/woocommerce.js`, compiled assets.
Core: `webgram-core.php`, `readme.txt`, `src/Modules/WooEnhancements/{Module,BuyNow,RecentlyViewed,Specifications,ContactSeller,BulkInquiry,TrackOrder,PincodeChecker}.php`, `Admin/ProductPanel.php`, `src/Modules/{Badges,QuickView,Coupons}/Module.php`, `Coupons/OfferProgress.php`, `templates/woo-enhancements/*`, `templates/badges/list.php`, `templates/quick-view/*`, `templates/coupons/*`, `assets/js/{woo-enhancements,quick-view,coupons}.js`, `assets/css/{woo-enhancements,quick-view,coupons}.css`, `assets/admin/admin.{js,css}`.
Tests: `tests/core-harness.php` (73 checks), `tests/theme-harness.php` (48 checks).

### C. Database and API changes

- Post type `wg_inquiry` (private) with meta `_wg_name`, `_wg_company`, `_wg_phone`, `_wg_email`, `_wg_product_id`, `_wg_product`, `_wg_quantity`, `_wg_message`, `_wg_status`, `_wg_ip_hash`.
- Product meta: `_wg_chip_attribute`, `_webgram_video_url`, `_wg_specs`, `_wg_contact`, `_wg_badge_text`, `_wg_badge_color`, `_wg_coupon`.
- Cookies: `wg_recent`, `wg_shop_view`.
- WC-AJAX: `webgram_bulk_inquiry`, `webgram_quick_view`, `webgram_coupon_progress` (plus `webgram_pincode_check` from Phase 1). REST: `POST webgram/v1/track-order`.
- Hooks added: `webgram/product_card/data`, `/variations`, `/buy_now`, `webgram/product/summary_providers`, `webgram/product/summary/{id}`, `webgram/product/below/{id}`, `webgram/product/gallery_badges`, `/gallery_actions`, `/specifications`, `/reviews`, `/info_cards`, `/use_swatches`, `/after_columns`, `/after_summary`, `/after_related`, `/bulk_inquiry_modal`, `webgram/shop/filters`, `/filters_before`, `/toolbar_end`, `webgram/bulk_order/benefits`, `/whatsapp_url`, `/sidebar`; Core: `webgram_core/product_panel/fields`, `/save`, `webgram_core/badges/facts`, `/list`, `webgram_core/specifications/rows`, `webgram_core/contact_seller/values`, `webgram_core/bulk_inquiry/created`, `webgram_core/track_order/data`, `/order_id`, `/reached_step`, `webgram_core/coupons/product_code`, `/milestones`, `webgram_core/quick_view/before_cart`, `webgram_core/recently_viewed/count`.
- WooCommerce template overrides now: `content-product.php`, `single-product.php`, `global/quantity-input.php` (3 of the 12 allowed), all with `@version 9.4.0`.

### D. Tests performed

- `php -l` on every changed PHP file in theme and Core: 0 errors.
- `php tests/core-harness.php`: 73 checks pass (Phase 0 and 1 sets plus badge rules, specifications merge and sanitizing, WhatsApp link, track order contact matching and timeline, milestone parsing and progress computation, coupon headlines, recently viewed list logic, bulk inquiry validation including honeypot and E.164).
- `php tests/theme-harness.php`: 48 checks pass (adds savings percent, rating pill markup, shop and product classes loading without WooCommerce).
- `npm run build`: clean. Gzipped sizes: main.css 13.9 KB, shop.css 1.4 KB, product.css 4.0 KB, woocommerce.css 0.8 KB, main.js 5.8 KB, shop.js 2.6 KB, product.js 4.0 KB.
- Em dash scan: none. Text domain scan: none.

### E. Errors found and fixed

- Passing a custom `class` to `woocommerce_template_loop_add_to_cart()` replaced WooCommerce's default classes, which would have disabled AJAX add-to-cart on cards (a Phase 0 behaviour). `cart_button_class()` now rebuilds the defaults.
- Slideshow module had an unused timer helper; removed.

### F. Compatibility concerns

- Variation swatches are a presentation layer over the standard form; a third-party swatch plugin can disable them with `add_filter( 'webgram/product/use_swatches', '__return_false' )`.
- The theme gallery replaces WooCommerce's flexslider markup on product pages; plugins that inject into `woocommerce_product_thumbnails` will not render there. The `webgram/product/gallery_actions` and `gallery_badges` hooks are the replacement points.
- Grid/list view is a cookie; full-page caches should exclude `wg_shop_view` or accept that the cached view wins.
- Shop AJAX replaces the loop area and the filters widgets by parsing the target page; filter widgets that depend on their own JS must re-initialise on `wg:content-updated`.
- Track order matching accepts the billing email or phone; stores that do not collect phone numbers rely on the email match only.

### G. Not tested

- None of this phase ran inside WordPress. Untested in a browser: card hover slideshow and chips, AJAX add-to-cart from cards, shop AJAX filtering, load more and infinite scroll, grid/list toggle, PDP gallery (auto slide, zoom, lightbox, video), sticky release, swatches with WooCommerce variation events, quantity stepper, mobile sticky bar, section ordering output, quick view modal with variations, coupon copy and toast, bulk inquiry modal and page form, track order form against a real order, product panel saving, pincode checker on the PDP, Buy Now redirect with a real cart, recently viewed row.
- PHPCS with WordPress and WooCommerce rulesets, Lighthouse, accessibility.

### H. Known limitations

- Reviews use WooCommerce's default comments template until the Core Reviews module ships in Phase 4.
- The card hover slideshow uses the first four gallery images; the PDP gallery has no limit.
- Coupon progress currently renders only where the theme fires `webgram/cart/before_items`; the cart drawer itself arrives in Phase 3.
- Track order timeline reaches "Packed", "Shipped" and "Out for delivery" only for stores using those custom statuses (`packed`, `shipped`, `out-for-delivery`) or a plugin hooking `webgram_core/track_order/reached_step`; the Notifications module in Phase 7 registers `wc-shipped`.
- Bulk Order product picker is a datalist of up to 200 products (no AJAX search).

### I. Ready for next phase

Phase 3: cart drawer with offer progress and recommendations, cart page, checkout, thank you, My Account, split login/register, help page.
