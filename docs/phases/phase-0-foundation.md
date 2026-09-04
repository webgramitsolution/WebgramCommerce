# Phase 0: Foundation (0.1.0)

## Implemented

Webgram Core
- Plugin bootstrap with PHP version guard, constants, own PSR-4 autoloader (Composer autoloader picked up if present).
- Container, Plugin singleton with service accessors, Activator, Deactivator, Upgrader with versioned migrations (`src/Migrations/V*.php`).
- Abstracts: Module, AjaxHandler (nonce + login + capability + declarative sanitization + standard JSON), RestController (explicit permission callbacks enforced), Repository ($wpdb only here, dbDelta install, retention purge).
- Support: Settings (one option per module, lazy load), Cache (object cache or transients, group invalidation), Crypto (libsodium secretbox, key from salts or WEBGRAM_CORE_ENCRYPTION_KEY, masking), Logger (WooCommerce logger with secret redaction), Sanitizer, Template (theme override resolution), Assets (register everywhere, enqueue where rendered), Helpers (E.164 phone normalization).
- ModuleManager: discovery via filter, dependency graph incl. virtual `woocommerce` and `elementor`, enable/disable option, per-module `webgram_core/{id}/enabled` filter, `webgram_core/loaded` action.
- Admin: Modules screen with toggles, Settings screen driven by module field schemas (text, textarea, number, select, checkbox, color, image, secret with encryption and clear), Notices (WooCommerce missing, Sodium missing, theme version handshake, activation).
- REST: Router + admin-only `/webgram/v1/system/status`.
- HPOS and cart/checkout blocks compatibility declared.
- 18 module registrations (stubs marked "Coming in phase N", never booted, never reported active).

Webgram Theme
- Bootstrap, setup (supports, menus, sidebars, image sizes, editor palette from tokens), `webgram-core` theme support declaration.
- Core bridge (`webgram_has_core()`, version notice, `--wgc-*` to `--wg-*` token mapping).
- Customizer: panel framework reading panel files, sections Colors (20 tokens), Typography, Layout and shape, Header, Footer; defaults in one file; live preview JS for tokens.
- CSS generator: Customizer values to `:root` variables, transient cached, value sanitization.
- Enqueue: conditional bundles (main always, woocommerce on WC pages), self-hosted Inter and Manrope (OFL) with preload, optional Google Fonts, WooCommerce default styles removed.
- Templates: header/footer with hookable regions, index/archive/search/page/single/404/sidebar/comments/searchform, content parts, pagination, empty state.
- WooCommerce: setup wrappers, product card renderer (one markup for all listings, hooks for Core badges/actions/buy now), `content-product.php` override.
- SCSS design system (tokens, reset, typography, forms, buttons, badge, rating, price, card, drawer, empty, search, pagination, post card, container, header, footer) and WooCommerce loop/notices. Build: Sass + esbuild.
- JS: component registry with rescan on `wg:content-updated`, accessible drawer with focus trap, header height variables and sticky state, jQuery bridge for WooCommerce cart events (the only jQuery usage).
- Theme dashboard (system status), child theme.

## Tests performed

- `php -l` on every PHP file in theme, core and child theme: 0 errors.
- Core harness (`wgtest/harness.php`, WordPress functions stubbed): 26 checks pass. Covers module discovery, default states, third-party module registration, dependency blocking, settings read/write/filter, encryption round trip and tamper detection, sanitizer, phone normalization, cache group flush, template path traversal.
- Theme harness (`wgtest/theme-harness.php`): 10 checks pass. Covers CSS generator output, Customizer overrides, radius scale, malicious token value rejection, SVG icon helper, path traversal in icon names, layout default.
- Build: `npm run build` clean (no Sass warnings). Bundle sizes gzipped: main.css 4.7 KB, woocommerce.css 0.8 KB, main.js 1.3 KB.
- Text domain scan: no cross-contamination between `webgram` and `webgram-core`.
- Em dash scan: none in code or docs.

## Not tested

- Running inside a real WordPress install (harness only). Activation, Customizer UI, admin screens and front-end rendering need a manual pass on your local WordPress before Phase 1 starts.
- PHPCS with WordPress rulesets and Theme Check plugin (to be added to CI in Phase 1 once the local install exists).
- Visual rendering of header, card and drawer in a browser.

## Known limitations

- Header and footer are preset layouts; the drag-and-drop builder lands in Phase 1 and reads the same settings.
- All 17 Core modules are stubs. The Modules screen shows them as "Coming in phase N".
- `woocommerce_enqueue_styles` is emptied, so shop pages look unstyled beyond loop and notices until Phase 2.

## Ready for Phase 1

Header builder, footer builder, mega menu, mobile menu, sticky header.
