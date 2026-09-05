# Phase 3: Cart drawer, cart page, checkout, thank you, My Account, login split, help page (0.4.0)

### A. Implemented

Webgram Theme
- Theme Settings tab "Cart and checkout" (`cart_checkout`, priority 145) with sections Drawer, Cart page, Checkout, Thank you. Every visual element in this phase has a show/hide or text option: drawer on/off, after add to cart behaviour (drawer / toast / none), offer progress slot, recommendations slot, coupon field, savings line, note text, button text, button subline, payment mini icons (multicheck), View cart link, sticky cart summary, cross-sells, empty cart suggestions, checkout steps header, sticky order review, coupon placement (summary / above form), trust text under Place order, thank you timeline, Continue shopping button.
- Slide cart drawer (spec 4.8): shell `template-parts/cart/slide-cart.php` printed once on `webgram/after_page`, content `template-parts/cart/drawer-content.php` refreshed as a WooCommerce fragment (`[data-wg-drawer-content]`, plus `.wg-cart-count` for header badges). Header "Your Cart (N Items)" with close icon; hooks `webgram/cart/before_items` (Core offer progress lands here), `webgram/cart/recommendations` (Core carousel), `after_items`, `before_totals`, `after_totals`, `before_checkout_button`, `webgram/cart/empty`; line items with 72px image, two line title, price, qty stepper pill and trash; footer SUBTOTAL, "You save" line (sale discounts plus coupons, `Webgram_WC_Cart::savings()` with filter `webgram/cart/savings`), editable note, dark full width PLACE ORDER with subline, payment mini icons and chevron, View cart link, optional coupon form. Empty state with suggested products (cross-sells, else best sellers) through `webgram/cart/empty_products`.
- `Webgram_WC_Cart`: `wc-ajax=webgram_cart_update` (nonce, item key, quantity; zero removes; returns fragments, cart hash, count and message), fragments filter, add to cart redirect suppressed when the drawer handles it, compact `items()` array for templates. `cart.js` (loaded site wide only when the drawer is on): quantity and remove via fetch, fragment application, `wg:added_to_cart` opens the drawer or shows a toast per setting, coupon submit fallback to the cart page, shared toast helper exposed as `WebgramCore.toast` when Core has not defined one.
- Cart page override `woocommerce/cart/cart.php`: two columns (items table, sticky summary card with totals and hooks), coupon, Continue shopping and Update cart, cross-sells row below, empty state suggestions on `woocommerce_cart_is_empty`.
- Checkout override `woocommerce/checkout/form-checkout.php`: visual steps header (Cart, Details, Payment, Done), two columns with sticky order review, coupon block inside the summary (non-form block posting to WooCommerce's own `apply_coupon` wc-ajax with its nonce, then `update_checkout`) or the standard coupon form above when placement is "top", all WooCommerce hooks intact including `woocommerce_checkout_before_customer_details` for express checkout and gateway injection, field classes added through `woocommerce_checkout_fields` without removing fields, trust text under Place order, hooks `webgram/checkout/before_payment` and `after_order_review`.
- Thank you override `woocommerce/checkout/thankyou.php`: failed state kept, steps header on "Done", head with icon and order number, overview card (number, date, email, total, payment method) with `webgram/thankyou/after_details`, status timeline card (`Webgram_WC_Checkout::timeline()`, pure), gateway `woocommerce_thankyou_{method}` and `woocommerce_thankyou` actions preserved, Continue shopping.
- My Account: `myaccount/navigation.php` with icons (filter `webgram/account/nav_icons`), dashboard cards on `woocommerce_account_dashboard` (orders, addresses, account details plus `webgram/account/dashboard_cards`), responsive orders table, `webgram/account/after_order_actions` hook after the standard order buttons.
- Login and register split page `myaccount/form-login.php` (spec 4.10): left column with segmented Login / Signup toggle (remembered in sessionStorage, register pane forced after a registration error), login form with icon prefixes, eye toggle, remember me and forgot link, register form with two column grid (Full name, Email, Password, Confirm password), optional username when WooCommerce requires it, trust logo strip from the `login_trust_logos` repeater, right column image (`login_image`, `login_image_mobile`, show on mobile switch). `Webgram_WC_Account::validate_registration()` on `woocommerce_registration_errors` requires the full name and matching passwords (only when the Webgram form posted), `save_registration()` maps full name to first and last name. `woocommerce_login_form_end`, `woocommerce_register_form_end` and the other WooCommerce hooks stay in place for social login plugins.
- Help page template `page-templates/template-help.php` (spec 4.12): FAQ accordion from `webgram/help/faqs`, contact cards from `webgram/help/contacts`, hook `webgram/help/after`.
- Styles: `components/_cart-drawer.scss` in the main bundle; conditional `cart-checkout.css` bundle (`woocommerce/_cart-page.scss`, `_checkout.scss`, `_account.scss` covering account, login, form grid, help and FAQ) enqueued only on cart, checkout and account pages. `checkout.js` (summary coupon, login toggle, password eye) loads on the same pages.

Webgram Core
- WooEnhancements `CartRecommendations`: pure `pick()` (cross-sells first, then best sellers, cart items excluded, deduped, capped), `products()` with filter `webgram_core/cart/recommendations`, carousel template `templates/woo-enhancements/cart-recommendations.php` (64px image, two line title, price, dark ADD button with AJAX add to cart, arrows) rendered on `webgram/cart/recommendations`; arrow scrolling added to `woo-enhancements.js`, fallback styles in `woo-enhancements.css`.
- Help page content: Site Tools tab "Help page" with an FAQ textarea (blank line separated blocks, first line is the question) parsed by `SiteTools\Module::parse_faqs()` and supplied to `webgram/help/faqs`; contact cards (call, WhatsApp, email) from the contact seller settings on `webgram/help/contacts`; new `contact_email` field.
- Coupons offer progress from Phase 2 now renders inside the drawer through `webgram/cart/before_items` and refreshes as a fragment.

### B. Files and modules changed

Theme: `style.css`, `functions.php`, `package.json`, `inc/enqueue.php`, `inc/settings/defaults.php`, `inc/settings/tabs/cart-checkout.php` (new), `inc/woocommerce/class-wc-cart.php`, `class-wc-checkout.php`, `class-wc-account.php` (new), `template-parts/cart/slide-cart.php`, `drawer-content.php` (new), `woocommerce/cart/cart.php`, `checkout/form-checkout.php`, `checkout/thankyou.php`, `myaccount/navigation.php`, `myaccount/form-login.php` (new overrides), `page-templates/template-help.php` (new), `assets/src/js/cart.js`, `checkout.js` (new), `assets/src/scss/main.scss`, `cart-checkout.scss`, `components/_cart-drawer.scss`, `woocommerce/_cart-page.scss`, `_checkout.scss`, `_account.scss` (new), compiled `assets/css/main.css`, `cart-checkout.css`, `assets/js/cart.js`, `checkout.js`.
Core: `webgram-core.php`, `readme.txt`, `src/Modules/WooEnhancements/Module.php`, `CartRecommendations.php` (new), `src/Modules/SiteTools/Module.php`, `Settings.php`, `templates/woo-enhancements/cart-recommendations.php` (new), `assets/js/woo-enhancements.js`, `assets/css/woo-enhancements.css`.
Tests: `tests/theme-harness.php` (55 checks), `tests/core-harness.php` (76 checks).

### C. Database and API changes

- No new tables, post types or options. Theme settings keys added under `webgram_theme_settings`: `cart_drawer`, `cart_after_add`, `cart_drawer_progress`, `cart_drawer_recommend`, `cart_drawer_coupon`, `cart_drawer_savings`, `cart_drawer_note`, `cart_drawer_button`, `cart_drawer_subline`, `cart_drawer_payments`, `cart_drawer_view_cart`, `cart_sticky_summary`, `cart_cross_sells`, `cart_empty_products`, `checkout_steps`, `checkout_sticky`, `checkout_coupon_place`, `checkout_trust_text`, `thankyou_timeline`, `thankyou_continue`. Core `webgram_core_settings_site_tools` gains `help_faqs`; `webgram_core_settings_woo_enhancements` gains `contact_email`.
- WC-AJAX: `webgram_cart_update` (theme, nonce `webgram_nonce`). Checkout coupon uses WooCommerce's own `apply_coupon` endpoint.
- Hooks added: `webgram/cart/before_items`, `/recommendations`, `/after_items`, `/before_totals`, `/after_totals`, `/before_checkout_button`, `/empty`, `/empty_products`, `/savings`, `webgram/checkout/before_payment`, `/after_order_review`, `webgram/thankyou/after_details`, `webgram/account/nav_icons`, `/dashboard_cards`, `/after_order_actions`, `webgram/help/faqs`, `/contacts`, `/after`; Core `webgram_core/cart/recommendations`.
- WooCommerce template overrides now 8 of the 12 allowed, all `@version 9.4.0`: `content-product.php`, `single-product.php`, `global/quantity-input.php`, `cart/cart.php`, `checkout/form-checkout.php`, `checkout/thankyou.php`, `myaccount/navigation.php`, `myaccount/form-login.php`.

### D. Tests performed

- `php -l` on every changed PHP file in theme and Core: 0 errors.
- `php tests/theme-harness.php`: 55 checks pass (adds: cart, checkout and account classes load without WooCommerce; thank you timeline for processing, on-hold paid and unpaid, completed; full name split; drawer defaults; tab count now 21).
- `php tests/core-harness.php`: 76 checks pass (adds: recommendation picking order, exclusion, dedupe and cap; FAQ parsing with blank line blocks, CRLF and single line rejection; empty input).
- `cd webgram-theme && npm run build`: clean. `cart.js` 5.3 KB, `checkout.js` 2.1 KB, `cart-checkout.css` 20.4 KB (uncompressed).
- Em dash scan on theme, Core, docs and tests: none. Text domain scan: no `webgram-core` strings in the theme and no `webgram` strings in Core.

### E. Errors found and fixed

- The drawer footer was missing the savings line required by the Phase 3 task list; added with a show/hide option and a pure helper.
- No other failures during this phase's checks.

### F. Compatibility concerns

- The checkout coupon block inside the summary is not a `<form>` (nesting a form inside the checkout form is invalid HTML). It posts to WooCommerce's `apply_coupon` endpoint and triggers `update_checkout`; the standard coupon form above the checkout remains available through the placement setting for plugins that hook `woocommerce_before_checkout_form`.
- The drawer suppresses WooCommerce's "redirect to cart after add" only when the drawer is enabled; stores wanting the redirect should turn the drawer off.
- Registration validation only runs when the Webgram form posted `webgram_register`, so third-party registration forms and social login flows are untouched.
- The login page keeps every WooCommerce hook, but plugins that replace `form-login.php` themselves win over the theme override in WooCommerce's template lookup order only when they load it through their own path.

### G. Not tested

- Nothing in this phase ran inside WordPress or a browser: drawer open and close, fragment refresh after AJAX add to cart, quantity and remove requests, toast mode, coupon application in the drawer, savings line values, offer progress fragment inside the drawer, recommendations carousel and ADD buttons, cart page sticky summary, checkout steps and sticky review, summary coupon with a real nonce, gateway injection areas, thank you timeline against real orders (including failed and on-hold), account navigation icons and dashboard cards, login and signup toggle memory, registration validation messages and first/last name mapping, trust logos and right image on mobile, help page FAQs and contact cards.
- Acceptance scenarios from the spec (full purchase with a gateway, COD and a coupon; a checkout field editor plugin active; social login plugin) not run.
- PHPCS with WordPress and WooCommerce rulesets, Lighthouse, accessibility audits.

### H. Known limitations

- Free shipping bar variant in the drawer relies on the Core offer progress milestones (free shipping threshold from shipping zones); there is no separate theme only bar.
- Recommendation carousel ADD buttons use WooCommerce's loop AJAX classes and only work for simple products; variable products link to the product page.
- Login tab memory uses sessionStorage, so it resets per browser session by design.
- Track order and bulk order pages were delivered in Phase 2 (templates and Core logic); this phase only adds the help page.

### I. Ready for next phase

Phase 4: Core Reviews module (form, media uploads, summary, list, helpful votes, admin column, schema), Wishlist and Compare with user meta and signed cookie storage, header counts, card buttons, pages and shortcodes.
