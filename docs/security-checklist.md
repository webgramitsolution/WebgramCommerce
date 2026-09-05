# Security checklist (Phase 8 review)

Reviewed against `docs/architecture.md` section 16 and the BUILD-SPEC security rules. Every row was checked by reading the code path listed; "verified by harness" means `tests/core-harness.php` or `tests/theme-harness.php` exercises the pure part. Nothing in this file was verified against a running WordPress install in this phase (see "Not tested" in the Phase 8 report).

Legend: nonce = WordPress or WooCommerce nonce checked; cap = capability or ownership checked on the server; in = every input sanitized or validated; out = every output escaped; sql = `$wpdb->prepare` or repository API only; secret = credential encrypted with `Support\Crypto` and never localized to JS or written to logs; rate = `Helpers::rate_limit()` bucket.

## Shared infrastructure (Core)

| Item | Status | Where |
|------|--------|-------|
| WC-AJAX handlers require `webgram_core_nonce` before any state change | done | `Abstracts/AjaxHandler.php` |
| REST routes always declare `permission_callback`; public writes use `require_nonce('wp_rest')` plus a rate bucket | done | `Abstracts/RestController.php`, every `Rest/*Controller.php` |
| Repositories are the only classes touching `$wpdb`; every query uses `prepare()` or dbDelta | done | `Abstracts/Repository.php`, `*/Repository.php` |
| Credentials at rest: libsodium secretbox keyed from `AUTH_KEY` + `SECURE_AUTH_KEY`; plain value shown to admins only as a masked hint | done | `Support/Crypto.php`, `Support/Settings.php` secret fields |
| Logger redacts tokens and phone numbers | done | `Support/Logger.php`, `Notifications/Channels/WhatsAppCloudChannel.php` |
| Frontend data (`WebgramCore` object) carries nonces and public settings only, never keys | done | `Support/Assets.php` |
| Uninstall removes data only when the owner opted in | done | `uninstall.php`, Settings > Advanced |
| No `eval`, remote code loading or obfuscated code | done | PHPCS pass, manual grep |

## Theme

| Item | Status | Where |
|------|--------|-------|
| Theme Settings, header and footer builders: `edit_theme_options` + nonce on save and reset; every field sanitized by type | done, harness | `inc/settings/class-settings-page.php`, `class-settings-sanitizer.php`, `inc/builders/class-builder-page.php` |
| Custom CSS cannot close the style tag or inject scripts | done, harness | `class-css-generator.php` |
| Import / Export: capability + nonce, payload validated per section, Core data handed over through `webgram/import_data` | done | `inc/settings/class-import-export.php` |
| Live search and cart update WC-AJAX: `webgram_nonce`, sanitized query, capped result count | done | `inc/woocommerce/class-wc-ajax.php` |
| Login and register forms: WooCommerce nonces, server side validation, errors shown inline, no user enumeration beyond WooCommerce defaults | done | `woocommerce/myaccount/form-login.php`, `class-wc-account.php` |
| Bundled Core installer: `install_plugins` + `activate_plugins` + nonce, installs only the zip inside the theme folder | done | `inc/admin/class-plugin-installer.php` |
| Demo importer: `manage_options` + nonce, reads only files inside `demo/`, never deletes | done | `inc/admin/class-demo-importer.php` |
| Mega menu fields: `edit_theme_options` on save, `wp_kses_post` for HTML content | done | `inc/mega-menu/class-mega-menu-admin.php` |
| Sidebars screen: `edit_theme_options` + nonce on create and delete, ids sanitized, hard cap of 20 areas | done | `inc/admin/class-sidebars.php` |
| Webgram options box: nonce + `edit_post`, layout and title values whitelisted, image id cast | done | `inc/admin/class-page-metabox.php` |
| Layout template swap: only a Core resolved id is rendered; block output comes through the Core filter | done | `inc/class-layouts.php`, `template-parts/layout.php` |
| Cart page auto update reuses WooCommerce's own nonce protected cart form | done | `assets/src/js/cart.js` |
| Output escaping across templates | done, PHPCS zero errors | all `template-parts/`, `woocommerce/` overrides |

## Core modules

| Module | Write paths | nonce | cap | in | out | sql | secret | rate | Notes |
|--------|-------------|:-----:|:---:|:--:|:---:|:---:|:------:|:----:|-------|
| woo_enhancements | pincode check, location resolve and geocode, bulk inquiry, track order, product panel save, pincode CSV import | x | x | x | x | x | n/a | 60/min, 30/min, 10/min, 5/h, 10/h | Track order requires order id plus billing email or phone match; CSV import limited to `manage_woocommerce`; geocoding provider URL fixed to OpenStreetMap, response validated |
| badges | product metabox save | x | x | x | x | n/a | n/a | n/a | |
| quick_view | read only WC-AJAX | x | public | x | x | n/a | n/a | n/a | Product must be published and visible |
| coupons | product metabox save, cart progress WC-AJAX (read), coupon apply WC-AJAX | x | x | x | x | n/a | n/a | n/a | Coupon codes shown only when the coupon is published and applicable; apply goes through `WC_Cart::apply_coupon()` so WooCommerce validates usage limits and restrictions, and a pending code lives in the WooCommerce session |
| reviews | submission (multipart), helpful vote, load more, admin bulk approve | x | x | x | x | x | n/a | submission per IP setting, helpful 60/h | Uploads: `wp_check_filetype_and_ext`, allowed mimes and size from settings, attachments private until approval, one vote per hashed IP and review |
| wishlist, compare | toggle, share | x | ownership | x | x | n/a | n/a | n/a | Guest cookie is HMAC signed with `Crypto::sign()`; tampering yields an empty list; share tokens expire |
| slider | slide metabox save | x | x | x | x | n/a | n/a | n/a | Image ids cast to int, URLs `esc_url_raw` |
| instagram | settings save, test connection | x | x | x | x | n/a | x | n/a | Token encrypted, refreshed monthly, response fields validated before caching |
| integrations | Elementor widgets, blocks, shortcodes (read only) | n/a | n/a | x | x | n/a | n/a | n/a | Block attributes sanitized in `Registry::sanitize_args()` |
| reels | reel metabox save, add to cart WC-AJAX, view events | x | x | x | x | n/a | n/a | via analytics | External video URLs limited to the listed providers |
| voice_search | none (browser API) | n/a | n/a | n/a | x | n/a | n/a | n/a | Server engine interface only, no provider shipped |
| ai_assistant | message, conversation, settings | x | session ownership | x | x | x | x | per minute setting + daily budget | Provider keys encrypted; browser talks only to `webgram/v1/assistant/*`; tools expose public catalog data, order status only for the session owner; conversations retained per setting with privacy export and erase |
| invoice | generate, regenerate, bulk zip, HSN save, download | x | manager, owner, order key or signed token | x | x | x | n/a | n/a | Files under `uploads/webgram-invoices/` with `index.html` and `.htaccess` deny, nginx snippet in `docs/deploy-hostinger.md`; dompdf remote images limited to the site host |
| emails | settings, preview, test send | x | x | x | x | n/a | n/a | n/a | Preview only for `manage_woocommerce`; delivery through `wp_mail()` |
| notifications | settings, test, template sync, resend, webhook | x | x | x | x | x | x | n/a | Webhook verifies `hub.verify_token` and `X-Hub-Signature-256` HMAC with the app secret; consent required for every WhatsApp send; recipients masked in the log |
| analytics | events collector, dashboard, server side purchase, add to cart, search and checkout events | x | public (nonce) | x | x | x | n/a | 60/min per IP | No IP stored, session hashed, personal keys stripped from meta, allow list of events; the purchase event stores totals only and is written once per order through order meta |
| site_tools | HTML blocks, layouts, popups (post type metabox), floating blocks, cookie, age verify, maintenance, white label, custom JS | x | x | x | x | n/a | n/a | n/a | Custom JS is gated by `unfiltered_html` on both the theme panel and the Core fallback save path; popup metabox uses a nonce and `edit_post`, click selectors are capped at 120 characters and used only by `closest()`; maintenance bypass key compares with `hash_equals` and sets an HMAC cookie, IP allowlist matches exact addresses; white label menu rename escapes the label |

## Dependency audit

| Dependency | Version | Where | Notes |
|------------|---------|-------|-------|
| dompdf/dompdf | 3.1.6 (pinned in `webgram-core/composer.lock`) | Invoice module, Composer `vendor/` bundled at package time | `isRemoteEnabled` limited to the site host, `chroot` set to uploads; update with `composer update dompdf/dompdf` and rerun the invoice check |
| Swiper | 11.2.10 (vendored) | `webgram-core/assets/vendor/swiper/` | MIT, loaded only by slider, reels, testimonials and product carousels |
| esbuild, sass, rtlcss | dev only | `webgram-theme/package.json` | Not shipped |

No other runtime dependency. `composer audit` and `npm audit` were not run in this phase (no network access to the advisory databases in the build environment); run both before release.
