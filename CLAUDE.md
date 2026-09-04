# CLAUDE.md : Webgram Theme + Webgram Core

You are the lead architect and senior WordPress/WooCommerce engineer on this repository. Read this file fully before touching code. The complete build specification is in `docs/BUILD-SPEC.md` and the approved architecture is in `docs/architecture.md`. Both are binding.

## What this repo is

Two independently shippable WordPress products, developed together, sold on ThemeForest:

- `webgram-theme/` : presentation layer (theme). Templates, design tokens, header/footer builders, mega menu, WooCommerce styling, product card, slide cart UI.
- `webgram-core/` : functionality layer (plugin). Reviews, wishlist, compare, quick view, badges, coupons, slider, reels, Instagram feed, voice search, AI assistant, invoices, branded emails, WhatsApp order notifications, analytics, Elementor/Gutenberg integration, site tools (Layouts, HTML Blocks, promo popup, age verify, cookie notice, maintenance, white label, custom JS).
- `webgram-child/` : child theme.
- `docs/` : architecture, build spec, per-phase reports.
- `tests/` : PHP harnesses runnable without WordPress (`php tests/core-harness.php`, `php tests/theme-harness.php`).

## Non-negotiable rules

1. Theme = presentation. Core = business logic. Never put CPTs, custom tables, REST endpoints, shortcodes-with-logic, email or PDF code in the theme. ThemeForest rejects it.
2. Theme must activate and work without Core, WooCommerce or Elementor. Core must work with any theme. Elementor is always optional.
3. WordPress/WooCommerce hooks first. Template overrides are a last resort, capped at 12 WooCommerce templates, each with an `@version` header.
4. Use `WC_Order` and WooCommerce APIs. Never query WooCommerce tables directly. HPOS compatible.
5. Never disable, deregister, or replace third-party plugin functionality. Coexist.
6. Security on every write: nonce, capability or ownership, sanitize in, escape out, `$wpdb->prepare`, encrypted credentials via `Webgram\Core\Support\Crypto`, never expose tokens to JS or logs.
7. Performance: a page loads only the assets it renders. Register everywhere, enqueue in the renderer. Disabled modules load nothing.
8. Email delivery stays external (`wp_mail()` only; store owner uses any SMTP plugin). Core owns templates and branding.
9. WhatsApp notifications go through the store owner's own Meta WhatsApp Cloud API credentials. Webgram is never a messaging middleman and never bills for messages. Do not hard-code Meta prices anywhere.
10. Never use em dashes in code, comments, UI copy or docs. Use commas, colons, parentheses or separate sentences.
11. Do not rewrite working code for style. Smallest safe change. Ask before destructive operations.
12. Never claim "tested" for anything not actually run. Report implemented / tested / not tested / known limitation.

## Conventions

- PHP 8.1+, WordPress 6.4+, WooCommerce 8.5+. Classic PHP theme, not block theme.
- Theme prefixes: functions `webgram_`, classes `Webgram_`, CSS `.wg-`, hooks `webgram/area/event`, text domain `webgram`, options via `theme_mod`.
- Core prefixes: namespace `Webgram\Core\...` (PSR-4 in `src/`), CSS `.wgc-` for fallback styles only, hooks `webgram_core/module/event`, options `webgram_core_*`, tables `{prefix}wg_*`, REST `webgram/v1`, text domain `webgram-core`.
- Theme never references Core classes. Only `webgram_has_core( $module )`, hooks and template overrides in `webgram-theme/webgram-core/`.
- Core modules extend `Webgram\Core\Abstracts\Module`, are registered in `ModuleManager::definitions()`, declare dependencies, and are toggled from Webgram > Modules. Stubs return `is_implemented(): false`; flip it to true when the module ships.
- Settings (v2): the Webgram Theme Settings panel (custom admin pages under the `Webgram` menu, option `webgram_theme_settings`) is the primary UI. Theme registers design tabs, Core registers feature tabs through `webgram/settings/tabs`. The Customizer keeps only Site Identity. Header layout lives in `webgram_header_layout`. Per-product options in product metaboxes, per-section content in Elementor/Gutenberg. One source of truth per concern; never duplicate.
- Templates: Core templates in `webgram-core/templates/`, overridable at `{theme}/webgram-core/{path}`. Theme template parts receive an `$args` array.
- JS: vanilla ES modules bundled with esbuild. jQuery only inside `assets/src/js/modules/woocommerce.js` (WooCommerce event bridge).
- SCSS partials begin with `@use "../abstracts/breakpoints" as *; @use "../abstracts/mixins" as *;`. Colors and sizes come from `--wg-*` variables only. Logical properties (`inset-inline-start`, `margin-inline`) for RTL.
- Build: `cd webgram-theme && npm install && npm run build`. Commit compiled `assets/css` and `assets/js`.
- Verify before reporting: `php -l` on changed files, `php tests/core-harness.php`, `php tests/theme-harness.php`, `npm run build`, PHPCS with WordPress-Extra + WooCommerce rulesets when available, and a manual check in the local WordPress site for UI work.

## Workflow for every phase

1. Read `docs/BUILD-SPEC.md` for the phase. Read the existing files you will touch.
2. Follow the exact layout specifications in BUILD-SPEC section 4 for every page. Every visual element must have a show/hide or style option in Theme Settings or the builder. State assumptions and any architectural question that blocks you. Otherwise proceed.
3. Implement task by task in the order given (data, logic, API, UI, templates, styles, tests, docs).
4. Run the checks above. Fix what fails.
5. Write `docs/phases/phase-N-<name>.md` using the report template in the spec.
6. Do not start the next phase until the report is written.
