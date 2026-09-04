# Webgram Theme + Webgram Core: Technical Architecture

Version 0.1 (architecture proposal, pre-approval)
Author: Webgram IT Solution
Target: ThemeForest-grade WooCommerce theme and companion plugin

---

## 0. Decisions that shape everything else

These are the decisions I am making up front. Each one has a reason. If you disagree with any of them, tell me before Phase 1, because changing them later is expensive.

| # | Decision | Reason |
|---|----------|--------|
| D1 | Two products, one repo per product: `webgram-theme` and `webgram-core` | ThemeForest and WordPress.org both reject "plugin territory" functionality inside themes. Separation is also what lets Core survive theme switching. |
| D2 | Theme works without Core (degrades gracefully), Core works without Theme (unstyled but functional) | ThemeForest reviewers test the theme with plugins disabled. Also lets you sell Core separately later. |
| D3 | Classic (PHP template) theme, not a Block/FSE theme | WooCommerce customization at the level you want (sticky PDP, slide cart, header builder) is far more mature on classic themes. FSE WooCommerce theming is still unstable for premium-theme feature sets. |
| D4 | Header and Footer Builder are Customizer-based with a custom drag-and-drop control, data stored as JSON in `theme_mod` | Keeps a single source of truth, live preview for free, no Elementor dependency. Same pattern WoodMart and Astra Pro use. |
| D5 | Homepage sections are provided in two forms: Elementor widgets (primary) and Gutenberg blocks/shortcodes (fallback) | ThemeForest buyers expect Elementor. But Elementor must not be a hard dependency (Envato rule). |
| D6 | PHP 8.1+, WordPress 6.4+, WooCommerce 8.5+, HPOS compatible from day one | Declaring HPOS compatibility is now expected. Old PHP support adds maintenance cost with no sales benefit. |
| D7 | Composer PSR-4 autoloading in Core, plain `require` in Theme | Core has 15+ modules and needs structure. Theme is template-heavy and Composer in a theme confuses ThemeForest reviewers. |
| D8 | Reviews extend WordPress comments (comment meta + attachments), no custom table | WooCommerce already stores reviews as comments. Custom tables would break every existing review plugin, SEO schema, and WooCommerce's own rating cache. |
| D9 | AI Assistant, Analytics, Invoice sequence use custom tables | These are high-write, queryable, and not natural fits for post/comment meta. |
| D10 | Design tokens as CSS custom properties generated from Customizer settings, one inline `<style>` block | Zero runtime overhead, works with every caching plugin, Elementor can reference the same variables. |
| D11 | Classic (shortcode) cart/checkout is the primary target, Block cart/checkout is "compatible but not customized" in v1 | The slide cart, coupon UI, and checkout redesign you want are not achievable on Block checkout without React extension work. Revisit in v2. |

---

## 1. Folder structure: Webgram Theme

```
webgram-theme/
├── style.css                       # Theme header only, no real CSS
├── functions.php                   # Bootstraps inc/, nothing else
├── screenshot.png
├── index.php
├── 404.php
├── archive.php
├── single.php
├── page.php
├── search.php
├── header.php                      # Calls the header builder renderer
├── footer.php                      # Calls the footer builder renderer
├── sidebar.php
├── comments.php
├── woocommerce.php                 # Not used; we use hooks + templates/ instead
│
├── inc/
│   ├── setup.php                   # add_theme_support, image sizes, menus
│   ├── enqueue.php                 # Asset registration + conditional loading
│   ├── core-bridge.php             # Detects Webgram Core, declares what theme provides
│   ├── template-functions.php      # Small helpers used by templates
│   ├── template-hooks.php          # All add_action/add_filter for template placement
│   ├── customizer/
│   │   ├── class-customizer.php    # Registers panels/sections/settings
│   │   ├── panels/                 # One file per panel (general, header, footer, shop, product, cart, typography, colors, blog, login)
│   │   ├── controls/               # Custom controls (builder, typography, spacing, color-with-alpha, radio-image, sortable)
│   │   ├── defaults.php            # Single source of default values
│   │   └── output/
│   │       ├── class-css-generator.php   # Customizer settings -> CSS variables
│   │       └── class-google-fonts.php    # Font loading (local or Google)
│   ├── builders/
│   │   ├── class-header-builder.php
│   │   ├── class-footer-builder.php
│   │   ├── class-builder-renderer.php    # Shared: reads JSON layout, renders elements
│   │   └── elements/                     # One class per element: logo, search, account, wishlist, compare, cart, menu, button, html, social, text, topbar-slider
│   ├── mega-menu/
│   │   ├── class-mega-menu-walker.php
│   │   ├── class-mega-menu-admin.php     # Menu item fields in Appearance > Menus
│   │   └── class-mega-menu-frontend.php
│   ├── woocommerce/
│   │   ├── class-wc-setup.php            # Theme support flags, wrappers
│   │   ├── class-wc-shop.php             # Archive hooks, filters sidebar, layout switch
│   │   ├── class-wc-product.php          # Single product hooks, section ordering
│   │   ├── class-wc-cart.php             # Cart page hooks
│   │   ├── class-wc-checkout.php         # Checkout hooks
│   │   ├── class-wc-account.php          # My Account + login/register split layout
│   │   ├── class-wc-product-card.php     # Product card renderer (used everywhere)
│   │   └── class-wc-ajax.php             # Theme-level AJAX only (mini cart fragments)
│   ├── elementor/
│   │   ├── class-elementor-compat.php    # Registers category, theme locations
│   │   └── widgets/                      # Theme-owned presentational widgets only
│   ├── admin/
│   │   ├── class-theme-dashboard.php     # Welcome page, recommended plugins, demo import, license
│   │   ├── class-plugin-installer.php    # Installs bundled webgram-core (TGMPA-style, own implementation)
│   │   └── class-demo-importer.php
│   └── compat/
│       ├── wpml.php
│       ├── polylang.php
│       └── yoast.php
│
├── template-parts/
│   ├── header/                     # topbar.php, main.php, mobile.php, sticky.php
│   ├── footer/
│   ├── content/                    # content.php, content-none.php, content-search.php
│   ├── blog/
│   ├── product/                    # gallery.php, summary-*.php, tabs.php, sticky-summary.php
│   ├── cards/                      # product-card.php, product-card-list.php, category-card.php (circle/square)
│   ├── sections/                   # hero-slider.php, categories.php, trending.php, best-sellers.php, mega-saver.php, reels.php, about.php, blog.php, testimonials.php, instagram.php, benefits.php, coupons.php, banner.php
│   ├── cart/                       # slide-cart.php, offer-progress.php, recommendations.php
│   ├── account/                    # login-register-split.php
│   ├── modals/                     # quick-view.php, search-overlay.php, size-guide.php
│   └── misc/                       # breadcrumb.php, pagination.php, empty-state.php
│
├── woocommerce/                    # WooCommerce template overrides (KEEP MINIMAL, see section 5)
│   ├── single-product.php
│   ├── archive-product.php
│   ├── content-product.php
│   ├── cart/
│   ├── checkout/
│   ├── myaccount/
│   └── global/
│
├── webgram-core/                   # Template overrides for Core modules (mirrors Core's templates/)
│   ├── reviews/
│   ├── reels/
│   ├── ai-assistant/
│   ├── wishlist/
│   └── compare/
│
├── page-templates/
│   ├── template-track-order.php
│   ├── template-bulk-order.php
│   ├── template-full-width.php
│   └── template-blank.php          # For Elementor landing pages
│
├── assets/
│   ├── src/                        # Source (SCSS, ES modules), not shipped to ThemeForest
│   │   ├── scss/
│   │   │   ├── abstracts/          # _tokens.scss (fallback defaults), _mixins.scss, _breakpoints.scss
│   │   │   ├── base/               # reset, typography, forms, buttons
│   │   │   ├── components/         # One file per component
│   │   │   ├── layout/             # header, footer, grid, container
│   │   │   ├── woocommerce/        # shop, product, cart, checkout, account
│   │   │   ├── sections/
│   │   │   ├── main.scss           # Always loaded (base + layout + shared components)
│   │   │   ├── woocommerce.scss    # Only on WC pages
│   │   │   ├── product.scss        # Only on single product
│   │   │   ├── cart-checkout.scss
│   │   │   ├── blog.scss
│   │   │   └── elementor.scss
│   │   └── js/
│   │       ├── main.js             # Header, menu, mobile nav, sticky
│   │       ├── product.js          # Gallery, sticky summary, variation UI, pincode
│   │       ├── slide-cart.js
│   │       ├── shop.js
│   │       ├── search.js           # Overlay, live results, calls Core voice if present
│   │       └── modules/            # Shared ES modules (slider wrapper, ajax helper, modal)
│   ├── css/                        # Compiled + minified, shipped
│   ├── js/                         # Compiled + minified, shipped
│   ├── fonts/                      # Self-hosted default fonts (Inter + Manrope, OFL license)
│   ├── images/
│   └── vendor/                     # swiper (MIT), no jQuery plugins beyond what WC loads
│
├── languages/
│   └── webgram.pot
├── demo/                           # Demo content XML, customizer JSON, widget JSON
├── docs/                           # Buyer documentation (HTML)
├── package.json                    # Build tooling (Vite or esbuild + sass)
├── webgram-child/                  # Child theme, shipped as separate zip
└── readme.txt
```

Theme intentionally has NO: custom post types, custom tables, REST endpoints beyond mini-cart fragments, email code, PDF code, AI code. All of that is Core.

---

## 2. Folder structure: Webgram Core

```
webgram-core/
├── webgram-core.php                # Plugin header, version constants, autoloader, boot
├── uninstall.php                   # Cleans tables/options only if "delete data" option is on
├── composer.json                   # PSR-4: "Webgram\\Core\\" => "src/"
├── vendor/                         # Shipped: dompdf (PDF), nothing else if avoidable
│
├── src/
│   ├── Plugin.php                  # Singleton, boots Container + ModuleManager
│   ├── Container.php               # Minimal DI container (no external library)
│   ├── Activator.php               # Creates tables via dbDelta, sets version option
│   ├── Deactivator.php
│   ├── Upgrader.php                # Runs versioned migrations on version change
│   │
│   ├── Abstracts/
│   │   ├── Module.php              # Base: id, name, dependencies, boot(), is_enabled(), assets(), settings_fields()
│   │   ├── AjaxHandler.php         # Nonce + capability + sanitization wrapper
│   │   ├── RestController.php      # Base REST controller with permission callbacks
│   │   ├── Provider.php            # Base for AI/speech providers
│   │   └── Repository.php          # Base for custom-table access ($wpdb prepared)
│   │
│   ├── Modules/
│   │   ├── ModuleManager.php       # Registers modules, resolves dependencies, honors enable/disable
│   │   ├── Registry.php            # Public: webgram_core()->modules()->get('reviews')
│   │   │
│   │   ├── AiAssistant/
│   │   │   ├── Module.php
│   │   │   ├── Providers/          # ProviderInterface, OpenAiProvider, GeminiProvider, AnthropicProvider, NullProvider
│   │   │   ├── Tools/              # ProductSearchTool, BestSellersTool, CouponTool, OrderStatusTool (function-calling schema)
│   │   │   ├── ConversationRepository.php
│   │   │   ├── MessageRepository.php
│   │   │   ├── RestController.php  # POST /webgram/v1/assistant/message
│   │   │   ├── RateLimiter.php
│   │   │   ├── Settings.php
│   │   │   └── Frontend.php        # Enqueues, renders floating button + window
│   │   │
│   │   ├── VoiceSearch/
│   │   │   ├── Module.php
│   │   │   ├── Engines/            # EngineInterface, WebSpeechEngine (browser), ServerSttEngine (future: Whisper/Google STT)
│   │   │   └── Frontend.php
│   │   │
│   │   ├── Reviews/
│   │   │   ├── Module.php
│   │   │   ├── Fields.php          # Title, media, recommend flag saved to comment meta
│   │   │   ├── Media.php           # Upload validation, attachment creation, thumbnails
│   │   │   ├── Summary.php         # Rating distribution with transient cache
│   │   │   ├── Query.php           # Sort/filter/paginate via WP_Comment_Query
│   │   │   ├── Ajax.php            # load more, sort, filter, submit
│   │   │   ├── Admin.php           # Admin columns, moderation of media
│   │   │   ├── Schema.php          # Extends WC's JSON-LD review schema
│   │   │   └── Shortcode.php       # [webgram_reviews product_id=""]
│   │   │
│   │   ├── Reels/
│   │   │   ├── Module.php
│   │   │   ├── PostType.php        # wg_reel CPT + wg_reel_category taxonomy
│   │   │   ├── Meta.php            # video source (attachment / external URL), poster, products[], CTA
│   │   │   ├── Query.php
│   │   │   ├── Renderer.php        # Grid, slider, full-screen viewer
│   │   │   ├── Tracking.php        # view/play/click events -> Analytics
│   │   │   ├── Shortcode.php
│   │   │   ├── Block.php
│   │   │   └── ElementorWidget.php
│   │   │
│   │   ├── Wishlist/
│   │   │   ├── Module.php
│   │   │   ├── Storage/            # StorageInterface, UserMetaStorage, CookieStorage (guest), merges on login
│   │   │   ├── Ajax.php
│   │   │   ├── Shortcode.php       # [webgram_wishlist] page
│   │   │   └── Fragments.php       # Header count via WC fragments
│   │   │
│   │   ├── Compare/                # Same shape as Wishlist
│   │   │
│   │   ├── QuickView/
│   │   │   ├── Module.php
│   │   │   └── Ajax.php            # Returns rendered product summary HTML
│   │   │
│   │   ├── Coupons/
│   │   │   ├── Module.php
│   │   │   ├── ProductCoupon.php   # "Show coupon on product page" meta + auto-apply on click
│   │   │   ├── OfferProgress.php   # Free shipping / Buy X get Y progress calculation for cart
│   │   │   └── Ajax.php
│   │   │
│   │   ├── Badges/
│   │   │   ├── Module.php          # Rule engine: new (days), sale %, best seller (sales count), custom text
│   │   │   └── Renderer.php
│   │   │
│   │   ├── Slider/
│   │   │   ├── Module.php
│   │   │   ├── PostType.php        # wg_slider + wg_slide (parent/child) OR single CPT with repeater meta
│   │   │   ├── Renderer.php
│   │   │   ├── Shortcode.php
│   │   │   └── ElementorWidget.php
│   │   │
│   │   ├── Invoice/
│   │   │   ├── Module.php
│   │   │   ├── NumberSequence.php  # Atomic sequence via custom table + transaction
│   │   │   ├── InvoiceData.php     # Builds a normalized array from WC_Order (HPOS-safe)
│   │   │   ├── PdfGenerator.php    # dompdf, template from templates/invoice/
│   │   │   ├── Storage.php         # Saves PDF to uploads/webgram-invoices/{year}/{month}/ with index.html + .htaccess deny
│   │   │   ├── AdminActions.php    # Order list action, order page metabox, bulk download
│   │   │   ├── AccountActions.php  # Download from My Account > Orders
│   │   │   ├── EmailAttachment.php # Attach to selected WC emails
│   │   │   └── Settings.php
│   │   │
│   │   ├── Emails/
│   │   │   ├── Module.php
│   │   │   ├── TemplateOverrides.php   # Hooks into woocommerce_locate_template for emails/ only
│   │   │   ├── Customizer.php          # Logo, colors, header/footer text, social links
│   │   │   ├── Preview.php             # Admin preview with sample order
│   │   │   └── Inliner.php             # CSS inlining (uses WC's Emogrifier already bundled)
│   │   │
│   │   ├── WooEnhancements/
│   │   │   ├── Module.php
│   │   │   ├── PincodeChecker.php      # Pincode -> deliverable? ETA? Data via CSV import or API adapter
│   │   │   ├── BulkInquiry.php         # Form -> wg_inquiry CPT + email
│   │   │   ├── ContactSeller.php       # WhatsApp/phone/chat links per product or global
│   │   │   ├── RecentlyViewed.php      # Cookie-based, with WC's own cookie if present
│   │   │   ├── BuyNow.php              # Add to cart + redirect to checkout, replaces cart contents optionally
│   │   │   ├── TrackOrder.php          # Shortcode + AJAX lookup by order id + email/phone
│   │   │   └── Specifications.php      # Product "Specifications" table from attributes + custom meta
│   │   │
│   │   ├── Analytics/
│   │   │   ├── Module.php
│   │   │   ├── EventRepository.php     # wg_events table
│   │   │   ├── Collector.php           # REST endpoint, batched, sampled
│   │   │   ├── Reports.php             # Admin dashboard: reel views, chat conversions, wishlist adds
│   │   │   └── Retention.php           # Cron cleanup
│   │   │
│   │   └── Integrations/
│   │       ├── Module.php
│   │       ├── Elementor/          # Registers all Core widgets under "Webgram" category
│   │       ├── Gutenberg/
│   │       └── Webhooks.php        # Outgoing webhooks for events (future)
│   │
│   ├── Admin/
│   │   ├── SettingsPage.php        # Webgram > Settings (React or plain WP settings API; see section 10)
│   │   ├── ModulesPage.php         # Enable/disable toggles
│   │   ├── LicensePage.php
│   │   └── Notices.php
│   │
│   ├── Rest/
│   │   ├── Router.php              # Namespace webgram/v1, registers controllers
│   │   └── Middleware/             # Nonce, capability, rate limit
│   │
│   ├── Support/
│   │   ├── Assets.php              # register + conditional enqueue helpers
│   │   ├── Template.php            # locate_template with theme override support
│   │   ├── Sanitizer.php
│   │   ├── Cache.php               # Transient/object cache wrapper with group invalidation
│   │   ├── Logger.php              # WC_Logger wrapper
│   │   └── Helpers.php
│   │
│   └── Compat/
│       ├── Hpos.php
│       ├── Wpml.php
│       └── CachePlugins.php        # Excludes AJAX endpoints and cookies from page cache
│
├── templates/                      # Overridable by theme: yourtheme/webgram-core/{path}
│   ├── reviews/
│   ├── reels/
│   ├── ai-assistant/
│   ├── wishlist/
│   ├── compare/
│   ├── quick-view/
│   ├── invoice/                    # invoice.php (HTML for PDF)
│   ├── emails/                     # WC email overrides
│   └── track-order/
│
├── assets/
│   ├── src/                        # SCSS + JS source per module
│   ├── css/                        # One file per module: reviews.css, reels.css, assistant.css, ...
│   ├── js/                         # One file per module
│   └── admin/
│
├── languages/
├── docs/
└── readme.txt
```

---

## 3. Module architecture (Core)

Every module extends `Webgram\Core\Abstracts\Module`:

```php
abstract class Module {
    abstract public function id(): string;            // 'reviews'
    abstract public function name(): string;          // 'Advanced Reviews'
    public function dependencies(): array { return []; }   // ['woocommerce'] or other module ids
    public function default_enabled(): bool { return true; }
    abstract public function boot(): void;            // Register hooks. Called only if enabled AND deps satisfied.
    public function register_assets(): void {}        // wp_register_* only. Enqueue happens where needed.
    public function settings_fields(): array { return []; }
    public function activate(): void {}               // Table creation, defaults
    public function uninstall(): void {}
}
```

ModuleManager lifecycle:

1. `plugins_loaded` (priority 5): Core loads autoloader, container, reads `webgram_core_modules` option (array of id => enabled).
2. `plugins_loaded` (priority 10): ModuleManager instantiates every module class, builds dependency graph, calls `boot()` on enabled modules whose dependencies are met. Missing dependency = module silently disabled + admin notice.
3. `init`: modules register CPTs, taxonomies, shortcodes.
4. `rest_api_init`: Router registers controllers from booted modules.
5. `wp_enqueue_scripts`: modules register assets; enqueue is conditional (section 14).
6. `do_action('webgram_core/loaded', $plugin)` fires after step 2. Theme listens to this.

Enable/disable is a simple option. No module writes to another module's tables directly; cross-module calls go through the Registry (`webgram_core()->module('analytics')->track(...)`) with a null-check, so one module can be disabled without fatals.

Third-party modules: `add_filter('webgram_core/modules', fn($m) => $m + ['my_module' => MyModule::class])`. That is the "Future modules framework" in one filter.

---

## 4. Theme / Core communication

Rule: Theme never calls a Core class directly. Theme only uses (a) hooks, (b) template overrides, (c) one guarded helper.

**Detection (theme side, `inc/core-bridge.php`):**

```php
function webgram_has_core(string $module = ''): bool {
    if ( ! function_exists('webgram_core') ) return false;
    return $module === '' || webgram_core()->modules()->is_active($module);
}
```

**Theme declares support (Core reads this to decide whether to load its fallback CSS):**

```php
add_theme_support('webgram-core', [
    'styles'   => true,              // theme styles Core components itself; Core skips its fallback CSS
    'header_icons' => ['wishlist','compare','cart'],
    'templates' => true,             // theme overrides live in /webgram-core/
]);
```

**Hook contract (all prefixed `webgram/`):**

| Hook | Fired by | Consumed by |
|------|----------|-------------|
| `webgram_core/loaded` | Core | Theme (registers integrations) |
| `webgram/product/summary/{position}` | Theme (single product) | Core modules inject coupon box, pincode, contact seller, bulk inquiry |
| `webgram/product/after_summary` | Theme | Core: reviews summary, reels |
| `webgram/product/tabs` (filter) | Theme | Core: adds "Reviews" tab renderer |
| `webgram/header/icons` (filter) | Theme | Core: wishlist/compare add themselves if module active |
| `webgram/product_card/badges` | Theme card | Core Badges module |
| `webgram/product_card/actions` | Theme card | Core: wishlist, compare, quick view buttons |
| `webgram/cart/before_items`, `webgram/cart/after_totals` | Theme slide cart | Core Coupons: offer progress, recommendations |
| `webgram/search/input` | Theme search | Core VoiceSearch: mic button |
| `webgram/footer/before_copyright` | Theme | Anything |
| `webgram/tokens` (filter) | Theme CSS generator | Core: may add module-specific variables |

**Template resolution (Core side, `Support/Template.php`):**
1. `{child-theme}/webgram-core/{path}`
2. `{theme}/webgram-core/{path}`
3. `{plugin}/templates/{path}`

Same mechanism WooCommerce uses, so every theme developer already understands it.

**Fallback behavior:**
- Theme without Core: header shows only logo, search (no mic), account, cart. Product page shows WooCommerce default reviews. No reels/chat/wishlist. Everything still works.
- Core without Theme: modules render with `assets/css/{module}.css` fallback styles, neutral colors using its own `--wgc-*` variables with defaults.

---

## 5. WooCommerce integration architecture

**Principle: hooks first, template overrides last.** Every overridden template is a maintenance liability on every WooCommerce release. Target: fewer than 12 overridden templates.

Overrides we do need (justified):

| Template | Reason |
|----------|--------|
| `single-product.php` | Two-column sticky layout wrapper |
| `content-product.php` | Product card is fully custom |
| `archive-product.php` | Toolbar, filters sidebar, layout switcher |
| `cart/cart.php` | Two-column cart with recommendations column |
| `cart/mini-cart.php` | Slide cart markup |
| `checkout/form-checkout.php` | Two-column layout with sticky summary |
| `myaccount/form-login.php` | Split login/register layout (form left, image right) |
| `myaccount/navigation.php` | Icon sidebar |
| `global/quantity-input.php` | Minus/plus buttons |

Everything else (price, rating, meta, tabs, upsells, related) is repositioned via `remove_action` / `add_action` in `inc/template-hooks.php`, not overridden.

**Product page section ordering (admin-controllable):**
A Customizer sortable control produces an ordered array of section ids. `class-wc-product.php` maps ids to callbacks and adds them to `woocommerce_single_product_summary` with incrementing priorities. Sections: `title, rating, price, short_description, variations, coupon (core), add_to_cart, buy_now (core), trust_badges, contact_seller (core), pincode (core), shipping_returns, specifications (core), overview, share, meta`. Unknown ids (from a disabled Core module) are skipped.

**Sticky PDP behavior (your specific requirement):**
Left column (gallery) is `position: sticky; top: var(--wg-header-height)`. Right column (summary + specifications + overview) flows normally. The browser releases the sticky gallery automatically when the right column's content ends, then the whole page scrolls into Related Products, Reviews, Reels. This is pure CSS, no scroll-jacking. Only JS involved: measuring sticky header height into a CSS variable, and disabling sticky under 992px. If the right column is shorter than the gallery, sticky is turned off by a class so nothing jumps.

**Cart:**
- Slide cart uses WooCommerce fragments (`woocommerce_add_to_cart_fragments`) for count and contents, so it stays cache-safe.
- Quantity update and remove go through `wc-ajax=` endpoints defined in `class-wc-ajax.php` (theme) using `WC_AJAX` pattern, returning fresh fragments.
- Offer progress bar and recommendations are injected by Core Coupons module via `webgram/cart/*` hooks. The bar calculates from cart totals server-side; never trusts client math.

**Checkout:** No functional change. Theme restyles fields via `woocommerce_checkout_fields` (classes, placeholders, priorities) and layout via template override. Order review is sticky on desktop. Coupon form is moved into the summary column. Thank-you page (`checkout/thankyou.php`) gets a designed layout with order timeline, invoice download (Core), and track order link.

**Buy Now:** Core adds a second submit button with `name="webgram_buy_now"`. On `woocommerce_add_to_cart` with that flag, the module optionally empties cart first (setting), then redirects to checkout. Standard WC validation still runs.

**Login/Register split page:** `form-login.php` override renders tabs (Login / Signup) left, Customizer-selected image right. Registration adds "Full Name" and "Confirm Password" via `woocommerce_register_form` + `woocommerce_register_post` validation. Nothing bypasses WC's own registration handler.

**HPOS:** All order reads use `WC_Order` methods and `wc_get_orders()`. Invoice meta stored via `$order->update_meta_data()`. Declared via `FeaturesUtil::declare_compatibility('custom_order_tables', ...)`.

---

## 6. Data model

**Reuse WordPress/WooCommerce storage:**

| Entity | Storage |
|--------|---------|
| Reviews | `wp_comments` (type `review`), `wp_commentmeta`: `_wg_title`, `_wg_media` (attachment ids[]), `_wg_recommend`, `_wg_helpful_count`; `verified` already set by WC |
| Review media | `wp_posts` attachments, `post_parent` = product id, meta `_wg_review_comment_id` |
| Reels | CPT `wg_reel`, meta `_wg_video_source` (attachment|external), `_wg_video_id`, `_wg_video_url`, `_wg_poster_id`, `_wg_products` (ids[]), `_wg_cta_text`; taxonomy `wg_reel_category` |
| Sliders | CPT `wg_slider`, meta `_wg_slides` (JSON array of {image, mobile_image, heading, sub, cta_text, cta_url, align, overlay}) and `_wg_settings` |
| Wishlist / Compare (logged in) | `wp_usermeta`: `_wg_wishlist` (ids[]), `_wg_compare` (ids[]) |
| Wishlist / Compare (guest) | Cookie `wg_wishlist` (ids, signed with HMAC to prevent tampering, max 50), merged into user meta on login |
| Bulk inquiries | CPT `wg_inquiry` (private), meta: product id, qty, name, phone, email, message, status |
| Pincodes | Option-based for small lists; custom table `wg_pincodes` when a CSV import exceeds 2000 rows |
| Invoice per order | Order meta: `_wg_invoice_number`, `_wg_invoice_date`, `_wg_invoice_file` |
| Module settings | `wp_options`: `webgram_core_modules`, `webgram_core_settings_{module}` (one option per module, autoload only where read on every request) |
| Theme settings | `theme_mods` (Customizer), header/footer layout as JSON inside theme_mod |

**Custom tables (created by Activator with dbDelta, prefixed `{$wpdb->prefix}wg_`):**

```sql
wg_invoice_sequence
  id            BIGINT UNSIGNED PK AUTO_INCREMENT
  order_id      BIGINT UNSIGNED NOT NULL UNIQUE
  invoice_no    VARCHAR(40) NOT NULL UNIQUE
  created_at    DATETIME NOT NULL
-- AUTO_INCREMENT is the sequence. Number format (prefix/year/padding) is derived from id.
-- Insert is done inside a transaction; UNIQUE(order_id) makes regeneration idempotent.

wg_ai_conversations
  id            BIGINT UNSIGNED PK AUTO_INCREMENT
  session_key   CHAR(64) NOT NULL          -- random, cookie-bound; never the WC session id
  user_id       BIGINT UNSIGNED NULL
  provider      VARCHAR(32) NOT NULL
  status        VARCHAR(16) NOT NULL DEFAULT 'open'
  created_at    DATETIME NOT NULL
  updated_at    DATETIME NOT NULL
  INDEX (session_key), INDEX (user_id), INDEX (updated_at)

wg_ai_messages
  id              BIGINT UNSIGNED PK AUTO_INCREMENT
  conversation_id BIGINT UNSIGNED NOT NULL
  role            ENUM('user','assistant','tool') NOT NULL
  content         TEXT NOT NULL
  payload         JSON NULL                -- product ids shown, tool calls, tokens used
  created_at      DATETIME NOT NULL
  INDEX (conversation_id, created_at)

wg_events
  id            BIGINT UNSIGNED PK AUTO_INCREMENT
  event         VARCHAR(48) NOT NULL       -- reel_view, reel_play, reel_click, chat_open, chat_product_click, wishlist_add, ...
  object_type   VARCHAR(24) NULL
  object_id     BIGINT UNSIGNED NULL
  user_id       BIGINT UNSIGNED NULL
  session_hash  CHAR(40) NULL              -- sha1 of session key, not reversible
  meta          JSON NULL
  created_at    DATETIME NOT NULL
  INDEX (event, created_at), INDEX (object_type, object_id)

wg_pincodes (created lazily on first CSV import)
  pincode       CHAR(6) PK
  city          VARCHAR(80) NULL
  state         VARCHAR(80) NULL
  deliverable   TINYINT(1) NOT NULL DEFAULT 1
  cod           TINYINT(1) NOT NULL DEFAULT 1
  eta_days      TINYINT UNSIGNED NULL
```

Retention: `wg_events` and `wg_ai_*` rows older than N days (setting, default 90) are deleted by daily cron. Uninstall drops tables only if the "Remove all data on uninstall" setting is checked.

---

## 7. Hooks / actions / filters strategy

Naming: `webgram/{area}/{event}` for theme, `webgram_core/{module}/{event}` for Core. Slash-separated namespaces are readable and grep-friendly. Every public hook is documented in `docs/hooks.md` with signature and version added.

Rules:
- Every rendered component has `before` / `after` actions and one `_args` filter. Example: `webgram/product_card/before`, `webgram/product_card/args`, `webgram/product_card/after`.
- Every setting value passes through a filter before use: `apply_filters('webgram/setting/{id}', $value)`. Lets developers override per-page without touching the Customizer.
- Every module has `webgram_core/{module}/enabled` (filter, bool) so hosting-level code can force-disable.
- No hook is removed or renamed after v1.0 without a two-minor-version deprecation using `_deprecated_hook()`.
- WooCommerce hooks are never removed globally in Core; only the theme repositions them, because Core must not change how other themes look.

---

## 8. REST / AJAX architecture

Two transport types, used deliberately:

**WC-AJAX (`?wc-ajax=webgram_{action}`)** for cart-related, session-dependent, high-frequency actions: add to cart, quantity update, remove, apply coupon, wishlist toggle, compare toggle, quick view, load more reviews, live search. Reason: it is faster than admin-ajax (no admin bootstrap), sends the WC session, and works with fragments. All handlers extend `AjaxHandler` which enforces: nonce check (`webgram_nonce`), optional `is_user_logged_in`, sanitization map, and a standardized JSON response `{success, data, message}`.

**REST (`/wp-json/webgram/v1/`)** for stateless or external-facing endpoints:

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/assistant/message` | POST | Public + nonce + rate limit (10/min/session, 100/day/IP) | Chat turn |
| `/assistant/conversation/{key}` | GET | Session key match | History reload |
| `/search/products` | GET | Public | Live search + voice search results |
| `/track-order` | POST | Public + rate limit | Order id + email/phone lookup, returns limited fields |
| `/events` | POST | Public + nonce, batched | Analytics collector |
| `/reviews/{product_id}` | GET | Public | Paginated reviews (used by Elementor editor preview) |
| `/reviews` | POST | Logged in or guest allowed by WC setting; nonce; multipart | Submit with media |
| `/invoice/{order_id}` | GET | Customer owns order OR `manage_woocommerce` | PDF download |
| `/admin/settings/{module}` | GET/POST | `manage_options` + nonce | Settings page backend |

Every REST controller has an explicit `permission_callback`; `__return_true` is only allowed on read endpoints that expose already-public data. Product search results go through `wc_get_products()` with `status=publish` and respect catalog visibility, so hidden products never leak via the API.

Frontend JS gets one localized object `webgramData` (theme) and `webgramCore` (core) with nonces, endpoints, i18n strings, and feature flags. Nothing else is inlined.

---

## 9. Elementor integration strategy

- Core registers the widget category "Webgram" and owns functional widgets: Reels, Reviews, AI Assistant (inline), Slider, Product Grid, Product Slider, Best Sellers, Trending, Mega Saver, Categories (with circle/square toggle), Coupons, Trust Badges, Testimonials, Instagram Feed.
- Theme registers only presentational widgets: Banner, Section Heading (with the decorative divider style), Benefits Row, Blog Grid.
- Each widget is a thin wrapper: controls map to the same args array the shortcode/template-part uses. `render()` calls `webgram_core()->view('sections/trending', $args)`. One renderer, three entry points (Elementor, Gutenberg, shortcode). No duplicated markup.
- Widget styling controls expose the design tokens (`--wg-color-primary` etc.) as defaults so a site stays consistent even when someone styles inside Elementor.
- Theme Builder locations (`header`, `footer`, `single`, `archive`) are registered so Elementor Pro users can replace the theme header if they want. Not required.
- If Elementor is not installed, the same sections are available as Gutenberg blocks (server-side rendered, `block.json`) and shortcodes. Demo import includes both an Elementor homepage and a Gutenberg homepage.

---

## 10. Customizer / settings architecture

**Single source of truth per concern:**

| Concern | Lives in | Never duplicated in |
|---------|----------|---------------------|
| Colors, typography, spacing, buttons, container width | Customizer > Design Tokens | Core settings, Elementor globals (Elementor reads our variables) |
| Header/footer layout, sticky, mobile menu | Customizer > Header / Footer Builder | Core |
| Shop archive layout, columns, filters, product card style | Customizer > Shop | Core |
| Product page section order, gallery style, sticky | Customizer > Product | Core |
| Cart/slide cart behavior, checkout layout | Customizer > Cart & Checkout | Core |
| Login page image, blog layout, 404 | Customizer | Core |
| Module enable/disable, AI provider + key, review options, reels options, invoice format, email branding, pincode data, analytics retention | Webgram Core > Settings | Customizer |
| Per-product: coupon, contact seller override, specs | Product edit screen (Core metabox) | Customizer |
| Per-section content on a page | Elementor / Gutenberg controls | Customizer |

Customizer implementation: native `WP_Customize_Manager` with custom controls written in vanilla JS (no Kirki, no external framework; Kirki is a common ThemeForest reviewer complaint and a moving target). Live preview via `postMessage` for token changes (CSS variable update, no reload) and `refresh` for layout changes.

Core settings page: WP Settings API with a small React-free admin layer (plain JS tabs). Reason: React admin pages look good in demos but double the maintenance surface; the settings are simple key/value. Revisit if the settings count exceeds ~150.

Import/export: Customizer JSON and Core settings JSON exportable from Theme Dashboard, used by demo import.

---

## 11. Design token system

Tokens are CSS custom properties on `:root`, generated by `class-css-generator.php` from Customizer values, cached in a transient, printed inline in `<head>` (roughly 2 KB). Fallback defaults exist in `_tokens.scss` so the compiled CSS works even if the inline block is missing.

```
Color
  --wg-color-primary            (default #A0181F, deep red seen in your references)
  --wg-color-primary-hover
  --wg-color-secondary
  --wg-color-accent             (gold, used for badges/highlights)
  --wg-color-success / warning / danger / info
  --wg-color-text, --wg-color-text-muted, --wg-color-heading
  --wg-color-bg, --wg-color-bg-alt, --wg-color-surface
  --wg-color-border
  --wg-color-header-bg, --wg-color-header-text, --wg-color-topbar-bg, --wg-color-topbar-text
  --wg-color-footer-bg, --wg-color-footer-text
  --wg-color-price, --wg-color-sale, --wg-color-star

Typography (modern, Wix/Shopify feel)
  --wg-font-body                (default "Inter", self-hosted)
  --wg-font-heading             (default "Manrope", self-hosted)
  --wg-font-size-base           (16px), fluid scale --wg-fs-xs ... --wg-fs-4xl using clamp()
  --wg-lh-tight / --wg-lh-normal / --wg-lh-relaxed
  --wg-fw-regular / medium / semibold / bold
  --wg-letter-spacing-heading

Spacing (4px base)
  --wg-space-1 ... --wg-space-16
  --wg-section-padding-y        (fluid)
  --wg-container-max            (default 1320px), --wg-container-padding

Shape
  --wg-radius-sm / md / lg / xl / pill
  --wg-shadow-sm / md / lg
  --wg-border-width

Component-level (derived, overridable)
  --wg-btn-radius, --wg-btn-padding-x, --wg-btn-padding-y, --wg-btn-font-weight
  --wg-card-radius, --wg-card-padding, --wg-card-shadow
  --wg-input-height, --wg-input-radius, --wg-input-bg
  --wg-header-height (set by JS), --wg-topbar-height
  --wg-badge-radius

Motion
  --wg-transition-fast (150ms), --wg-transition-base (250ms), --wg-ease
```

Core modules reuse these when the theme declares support; otherwise they define `--wgc-*` fallbacks mapped to neutral values.

Font loading: default fonts self-hosted in `assets/fonts/` with `font-display: swap` and preload for the two primary weights. Google Fonts is an opt-in setting (GDPR consideration for EU buyers). Customizer offers a curated list (Inter, Manrope, DM Sans, Plus Jakarta Sans, Poppins, Outfit, Playfair Display for headings) plus "custom font upload".

---

## 12. Component system

Each component is: one PHP renderer (template part with an args array), one SCSS partial, optional one JS module, one BEM block class prefixed `wg-`. Components never read globals directly; the caller passes args, so the same component can be used in a loop, an Elementor widget, an AJAX response, or the chatbot.

| Component | BEM root | Notes |
|-----------|----------|-------|
| Product card | `.wg-card` | Variants: grid, list, compact (chatbot/cart recs), with badge slot, action slot (wishlist/compare/quick view), variation swatches (Pack of 1/2/3 style), price with save amount, Buy Now + cart icon |
| Category card | `.wg-cat-card` | Modifiers `--circle`, `--square`, `--rounded`; Customizer sets the global default, widget can override |
| Section heading | `.wg-heading` | Decorative dividers (the dot-line style in your references), align, "View all" link |
| Button | `.wg-btn` | primary, secondary, outline, ghost, icon; sizes sm/md/lg |
| Badge | `.wg-badge` | sale, new, bestseller, custom; position control |
| Price | `.wg-price` | Regular, sale, save amount, percent, per-unit |
| Rating | `.wg-rating` | Stars with fill percentage, count |
| Slider | `.wg-slider` | Swiper wrapper with a single init function reading `data-` attributes |
| Modal / Drawer | `.wg-modal`, `.wg-drawer` | Used for quick view, search overlay, slide cart, mobile menu, reel viewer; one focus-trap implementation |
| Form controls | `.wg-field` | Input with icon, password toggle, select, quantity, pincode |
| Tabs | `.wg-tabs` | Accessible, used on product page and account |
| Accordion | `.wg-accordion` | Mobile filters, specs on mobile, footer columns on mobile |
| Empty state | `.wg-empty` | Icon, title, text, CTA (no reviews yet, empty cart, empty wishlist) |
| Toast | `.wg-toast` | Added to cart, wishlist added, coupon copied |
| Progress bar | `.wg-progress` | Cart offer progress with milestones |
| Chat window | `.wg-chat` | Header, message list, product carousel, suggestions, input with mic |
| Review card | `.wg-review` | Avatar, name, verified, stars, title, body, media grid, date, helpful |
| Rating summary | `.wg-rating-summary` | Big number, stars, distribution bars |
| Reel card / viewer | `.wg-reel` | Poster, play, product pill; viewer is vertical swipe |

JS architecture: no jQuery for theme JS (WooCommerce still loads jQuery for its own scripts, we do not add to it). ES modules bundled per entry (main, product, slide-cart, shop, search). Components initialize by `data-wg-component` attribute scan, and re-scan after AJAX content is injected (`document.dispatchEvent(new CustomEvent('wg:content-updated'))`).

---

## 13. Template hierarchy

WordPress hierarchy is preserved. Theme adds page templates for Track Order, Bulk Order, Full Width, Blank. WooCommerce templates resolve: child theme > theme `/woocommerce/` > WooCommerce plugin. Core templates resolve as in section 4.

Page-level layouts (from Customizer or per-page metabox): `container`, `full-width`, `sidebar-left`, `sidebar-right`. Per-page metabox also allows: hide header, hide footer, transparent header, hide breadcrumb.

Pages created on theme activation (with a confirmation, not silently): Wishlist, Compare, Track Order, Bulk Order. Each holds the corresponding shortcode so a buyer can move it or restyle it.

---

## 14. Asset loading strategy

Rule: a page loads only what it renders.

Theme:
- `main.css` + `main.js` everywhere (target < 60 KB CSS, < 25 KB JS gzipped).
- `woocommerce.css` on any `is_woocommerce() || is_cart() || is_checkout() || is_account_page()`.
- `product.css/js` on `is_product()` only.
- `cart-checkout.css` on cart/checkout only. Slide cart JS loads everywhere WooCommerce is active (needed for AJAX add to cart) but is deferred.
- `blog.css` on blog views.
- `elementor.css` only when Elementor renders the page.
- Swiper is registered once; enqueued on demand by any component that outputs a slider (`wp_enqueue_script('wg-swiper')` inside the slider renderer).
- All scripts `defer`; inline critical CSS for header only (optional setting, off by default).

Core:
- Each module has its own CSS/JS handle. Enqueued from the renderer that needs it (reviews on product page and wherever the shortcode is used; reels wherever rendered; assistant globally if enabled, but its JS is split: a 3 KB launcher loads the full chat bundle on first click).
- Assets are versioned with the plugin version for cache busting.
- Image optimization: `loading="lazy"` on everything below the fold, `fetchpriority="high"` on hero, responsive `srcset` via WordPress, WebP served if the host supports it (no conversion code in the theme; that is plugin territory).
- Reels video: `preload="none"`, poster image always, play on intersection or tap.

Caching plugins: Core registers its AJAX URLs and cookies (`wg_wishlist`, `wg_compare`, `wg_ai_session`) with WP Rocket, LiteSpeed Cache, and W3TC compat filters so pages stay cacheable and only fragments are dynamic.

---

## 15. Performance strategy

- Query budget: homepage sections share one `WC_Product_Query` per section with `fields => ids` then `wc_get_products` in batch; results cached in a transient keyed by args hash, invalidated on `woocommerce_update_product` and `woocommerce_product_set_stock`.
- Best sellers use `total_sales` meta with an index-friendly `orderby => 'popularity'` (WC handles this via lookup table). Trending uses `wc_product_meta_lookup` with a "views in last 7 days" counter stored in `wg_events` aggregation (daily cron writes a `_wg_trend_score` meta so the frontend query stays simple).
- Reviews summary (distribution) is cached per product in a transient; invalidated on comment insert/approve/delete for that product.
- Mega menu output cached per menu location in a transient; invalidated on `wp_update_nav_menu`.
- Customizer CSS cached in a transient; regenerated on `customize_save_after`.
- Object cache aware: all caching goes through `Support/Cache.php` which uses `wp_cache_*` when a persistent object cache exists, transients otherwise.
- AI chat: streaming responses if the provider supports it (SSE through REST is possible but fragile on shared hosting; v1 uses non-streaming with a typing indicator and provider timeout of 20 s).
- Core Web Vitals targets on demo: LCP < 2.5 s on 4G, CLS < 0.05 (fixed aspect-ratio boxes for all images and sliders), INP < 200 ms (no long tasks on load; sliders initialize on intersection).

---

## 16. Security strategy

- Every AJAX/REST write: nonce + capability or ownership check + sanitization through `Support/Sanitizer.php` maps + validation before any DB write.
- Output escaping: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for allowed HTML fields. Theme Check and PHPCS with `WordPress-Extra` + `WooCommerce` rulesets run in CI; zero errors is the merge gate.
- SQL: all custom-table queries through `$wpdb->prepare`; repositories are the only classes that touch `$wpdb`.
- File uploads (review media): `wp_check_filetype_and_ext`, allowed types from a setting (jpg, png, webp, mp4 with size limits), `wp_handle_upload`, attachments created with `post_status = private` until the review is approved, images regenerated via WP so original EXIF is stripped if the setting is on.
- Invoice PDFs stored outside the public listing (`index.html`, `.htaccess`/nginx snippet in docs), served only through the REST endpoint with ownership check, never by direct URL.
- AI provider keys: stored in `wp_options` encrypted with `sodium_crypto_secretbox` using a key derived from `AUTH_KEY` + `SECURE_AUTH_KEY`; never localized to the frontend; the browser only talks to our REST endpoint. The assistant's system prompt and tool outputs never include cost prices, customer data of other users, or order data unless the session is logged in and owns the order.
- Rate limiting: per-session and per-IP counters in transients (object cache when available) on assistant, track order, review submission, bulk inquiry.
- CSRF on forms: WooCommerce and WordPress nonces; Track Order requires order id plus billing email or phone match.
- Guest wishlist cookie is HMAC-signed; tampering yields an empty list rather than an error.
- No `eval`, no remote code, no base64-obfuscated anything (ThemeForest rejects it).
- Dependency policy: dompdf pinned and audited; Swiper pinned; no other runtime dependencies.

---

## 17. Compatibility strategy

- WordPress 6.4+, WooCommerce 8.5+ (tested against latest two majors before each release), PHP 8.1 to 8.3.
- HPOS declared compatible; Cart/Checkout Blocks declared compatible (unstyled beyond tokens in v1).
- Elementor free (widgets) and Pro (theme builder locations). Not required.
- Gutenberg: blocks for every Core section, theme.json only for editor color palette and font sizes (so the editor shows the right tokens), not for FSE.
- WPML/Polylang: all strings via `__()`, CPTs registered translatable, `wpml-config.xml` shipped.
- SEO: Yoast/Rank Math breadcrumbs honored if active; review schema extends WC's, never duplicates it.
- Caching: WP Rocket, LiteSpeed, W3TC, Cloudflare APO tested.
- Browsers: last 2 versions of Chrome, Firefox, Safari, Edge; iOS Safari 15+. Voice search shows the mic only when `window.SpeechRecognition || window.webkitSpeechRecognition` exists (Firefox does not support it; the button hides gracefully).
- RTL: `rtlcss` generated stylesheet shipped (ThemeForest expects it).
- Accessibility: WCAG 2.1 AA targets for color contrast in defaults, keyboard-navigable menus, drawers, modals, tabs; ARIA on sliders and chat.

---

## 18. Update / versioning strategy

- Semantic versioning for both products. Theme and Core have a compatibility matrix: Core declares `min_theme_version`, Theme declares `min_core_version`; mismatch shows an admin notice, never a fatal.
- Core `Upgrader.php` keeps `webgram_core_db_version`; migrations are numbered classes (`Migrations/V1_1_0.php`) run once, idempotent, wrapped in try/catch with logging.
- Theme updates: ThemeForest buyers update via the Envato Market plugin or by uploading the zip. Core is bundled inside the theme zip (`webgram-theme/plugins/webgram-core.zip`) and installed through the theme dashboard; when the theme updates and the bundled Core is newer, the dashboard offers a one-click update. Later, when you sell Core standalone, add your own license server (EDD Software Licensing or a small Laravel/Node endpoint) and a `PluginUpdater` class in Core. The architecture does not depend on which one you pick.
- Changelog maintained in both `readme.txt` files. Breaking changes only in major versions.
- Template override versioning: every overridden WooCommerce template keeps the `@version` header so WooCommerce's "outdated templates" status screen works for buyers.

---

## 19. Extension / API architecture

For third-party developers and your own future modules:

- `webgram_core()` global returns the Plugin instance; `->modules()->get($id)`, `->view($template, $args)`, `->settings($module)->get($key)`.
- Module registration filter (section 3).
- AI Assistant: `ProviderInterface { complete(array $messages, array $tools, array $options): Response }` plus `add_filter('webgram_core/ai/providers', ...)` to add one. Tools: `ToolInterface { name(), schema(), execute(array $args, Context $ctx) }` and `webgram_core/ai/tools` filter, so a store can add "check loyalty points" without touching Core.
- Voice: `EngineInterface` with `webgram_core/voice/engines` filter.
- Wishlist/Compare storage: `StorageInterface` with filter, so a headless or multi-site setup can swap it.
- Invoice: `webgram_core/invoice/data` filter on the normalized array, `webgram_core/invoice/number_format` filter, template override.
- Emails: standard WC template overrides plus `webgram_core/email/branding` filter.
- Events: `do_action('webgram_core/event', $event, $payload)` so any module or plugin can listen (this is also how Analytics collects).
- Outgoing webhooks (v1.2): subscribe URLs to events, HMAC-signed payloads.
- Documentation: `docs/developer.md` with every hook and interface, auto-generated hook list via a small script.

---

## 20. Development roadmap

Estimates assume one senior developer full-time; adjust for your team. Each phase ends with a testable build.

**Phase 0: Foundation (2 weeks)**
- Repos, build tooling (Vite + Sass), PHPCS + Theme Check in CI, local WP with WooCommerce sample data, coding standards doc.
- Core skeleton: bootstrap, Container, ModuleManager, Abstracts, Settings page with module toggles, Activator/Upgrader, one dummy module proving the lifecycle.
- Theme skeleton: functions bootstrap, Customizer framework with custom controls, token generator, base SCSS with tokens, product card component, buttons, forms.
- Deliverable: theme activates, passes Theme Check, tokens change live in Customizer, Core shows module toggles.

**Phase 1: Header, footer, navigation (2 weeks)**
- Header builder (Customizer JSON control, renderer, elements: topbar announcement slider, logo, deliver-to location, search with mic slot, track order, bulk order, help, cart, account), sticky header, mobile header, mobile drawer menu.
- Footer builder (rows/columns, widgets, payment icons, copyright).
- Mega menu (admin fields, walker, columns, images, banners, badges).
- Deliverable: your reference header/footer reproducible from Customizer alone.

**Phase 2: Shop and product page (3 weeks)**
- Archive: toolbar, filters sidebar with AJAX, grid/list, pagination/load more, product card final with swatches and Buy Now.
- Single product: two-column sticky layout, gallery with thumbnails and zoom, variation UI (image swatches like your 1kg/2kg/5kg), quantity, add to cart, buy now, trust badges row, shipping/returns cards, specifications table, overview, share, related, recently viewed, section ordering control.
- Core: Badges, QuickView, RecentlyViewed, BuyNow, Specifications, ContactSeller, PincodeChecker, ProductCoupon with copy.
- Deliverable: product page matches reference behavior including sticky release.

**Phase 3: Cart, checkout, account (2 weeks)**
- Slide cart with fragments, quantity, remove, coupon, recommendations, offer progress (Core Coupons), free shipping bar.
- Cart page, checkout layout, thank-you page, My Account styling, split login/register page with image, Track Order and Bulk Order page templates (Core provides logic).
- Deliverable: full purchase flow, tested with Razorpay test mode and COD.

**Phase 4: Reviews, wishlist, compare (2 weeks)**
- Core Reviews: fields, media upload, summary, sort/filter/load more, submission, schema, admin moderation. Theme: review presentation.
- Wishlist and Compare with guest storage and merge on login, pages, header counts.
- Deliverable: review UI matches reference; reviews still visible if theme is switched.

**Phase 5: Homepage sections and Elementor (3 weeks)**
- Core Slider module. Sections: categories (circle/square), trending, best sellers (dark band style), mega saver, featured, coupons, banners, about, blog, testimonials, trusted customers, Instagram feed, benefits row.
- Elementor widgets + Gutenberg blocks + shortcodes for each. Demo import (Elementor and Gutenberg variants).
- Deliverable: your reference homepage rebuilt in Elementor with only Webgram widgets.

**Phase 6: Reels, voice search, AI assistant (3 weeks)**
- Reels CPT, admin, grid/slider/viewer, product attachment, analytics events, widget/block/shortcode.
- VoiceSearch with Web Speech engine, mic in header search and chat.
- AI Assistant: floating button, window, product cards carousel, suggestions, provider abstraction with OpenAI + Gemini + Anthropic + Null (rule-based fallback that searches products by keywords and answers "best offer" from active coupons; works with no API key so the demo never breaks). Tools: product search, best sellers, active coupons, order status (logged in only). Rate limiting, conversation storage, retention.
- Deliverable: chat answers "Show me wall decor under ₹2000" via product search tool with real products.

**Phase 7: Invoice, emails, analytics (2 weeks)**
- Invoice numbering, PDF, storage, admin/customer actions, email attachment.
- Email template system with branding customizer and preview.
- Analytics dashboard (reel performance, chat metrics, wishlist trends).
- Deliverable: invoice downloads from admin and account; branded emails on all WC triggers.

**Phase 8: Hardening and ThemeForest prep (3 weeks)**
- Security review against section 16, PHPCS zero errors, Theme Check, Envato Theme Check, WooCommerce template version audit.
- Performance pass against section 15 targets on the demo server.
- RTL stylesheet, WPML config, translation POT files, accessibility pass.
- Child theme, documentation (buyer HTML docs + developer hooks doc), demo content, screenshots, item preview images, licensing text (GPL for code, separate license for images).
- Staging on Hostinger VPS with Nginx + PHP-FPM + Redis object cache, real-device testing.
- Deliverable: submission-ready package.

Total: roughly 22 weeks for a one-person build. Phases 5 and 6 can run in parallel with a second developer, which brings it under 16.

---

## 21. Potential architectural problems (identified before coding)

1. **ThemeForest "plugin territory" rejection risk.** Envato reviewers reject themes with CPTs, shortcodes for content, or SEO/analytics features in the theme. Mitigation: the split above keeps every CPT, shortcode, table, and REST endpoint in Core. Theme dashboard uses its own installer, not TGMPA (TGMPA is no longer accepted by Envato).

2. **Two settings surfaces confusing buyers.** Customizer for design, Core Settings for features. Mitigation: table in section 10 is enforced in code review; Core settings page links to the relevant Customizer section and vice versa; documentation has one "Where is the setting for X?" page.

3. **Elementor vs Customizer conflict on homepage styling.** A buyer sets primary color in Customizer, then Elementor's global colors override it. Mitigation: on Elementor activation, Core writes our tokens into Elementor's global color/font kit once (with a "sync tokens to Elementor" button), and widgets reference CSS variables as defaults.

4. **Review media on WordPress comments.** Comments were not designed for attachments; large media counts can slow `wp_commentmeta` queries. Mitigation: store attachment ids as one serialized meta, load lazily, cap at 5 files per review, and cache the summary. If a store hits tens of thousands of media reviews, v2 can add an index table without changing the public API.

5. **Block cart/checkout.** New WooCommerce installs default to Block cart/checkout. Our slide cart works, but the styled classic checkout will not be used unless the buyer switches to the shortcode. Mitigation: the setup wizard offers to switch with one click and explains why; Block checkout receives token-based styling so it is not broken, just less customized.

6. **Page caching vs personalized fragments.** Wishlist counts, cart counts, recently viewed, and chat session are per-user. Mitigation: all of these render via fragments or after-load AJAX, never in cached HTML. Documented cookie exclusions for cache plugins.

7. **AI cost and abuse.** An open chatbot with an API key can be abused into a large bill. Mitigation: rate limits, daily token budget setting with a hard stop, provider timeouts, Null provider fallback, and a "logged-in users only" option. Conversation logs are personal data: retention setting, export/erase hooks registered with WordPress privacy tools.

8. **Voice search browser support.** Web Speech API is Chrome/Edge/Safari only, sends audio to the browser vendor, and has no Firefox support. Mitigation: mic appears only when supported; engine abstraction allows a server STT engine later; documentation states the limitation clearly so buyers do not file bugs.

9. **Invoice number gaps and races.** Two orders paid at the same second must not share a number, and some jurisdictions (including GST in India) expect sequential numbering. Mitigation: AUTO_INCREMENT sequence table inside a transaction with `UNIQUE(order_id)`; numbers are assigned on payment complete (setting: or on order creation), never regenerated once assigned.

10. **PDF generation on shared hosting.** dompdf needs memory and does not support all CSS. Mitigation: invoice template uses a restricted CSS subset, generation is on-demand with caching to disk, and a "regenerate" action exists. mPDF or a remote renderer can be swapped via `PdfGeneratorInterface` if needed.

11. **Reels video hosting.** Self-hosted MP4 in `wp-content/uploads` will hit upload limits and bandwidth on cheap hosting. Mitigation: support external URLs (Cloudflare Stream, Bunny, YouTube Shorts embed as a fallback renderer), enforce size limits, and always require a poster image.

12. **WooCommerce template drift.** Overridden templates break on WC major releases. Mitigation: cap at 12 overrides, keep `@version` headers, add a CI job that diffs our overrides against the WooCommerce version we declare support for.

13. **Header builder complexity.** Drag-and-drop in the Customizer is the single most complex UI piece. Mitigation: Phase 1 ships a fixed set of well-designed presets (3 desktop, 2 mobile) driven by the same JSON format; the drag-and-drop control is built on top of that format in Phase 1 or deferred to 1.1 if it slips. Buyers still get full element toggles, ordering, and spacing either way.

14. **jQuery dependency creep.** WooCommerce's own scripts need jQuery, and it is tempting to write theme JS with it. Mitigation: theme and Core JS are vanilla ES modules; the only jQuery usage is listening to WC events (`$(document.body).on('added_to_cart')`), isolated in one adapter file.

15. **Translation and RTL debt.** Adding i18n and RTL at the end is expensive. Mitigation: `__()` from the first line, logical CSS properties (`margin-inline-start`) from the first SCSS partial, POT generated in CI.

16. **Demo dependency on your client brands.** Reference screenshots are from Prasadam and HOUSKASE. ThemeForest demo content must be original. Mitigation: build a neutral demo brand ("Aurelia" or similar) with licensed or self-made images; never ship client assets.

---

## 22. Open questions for you before Phase 0

1. Header builder: drag-and-drop in Phase 1, or presets first and drag-and-drop in 1.1? (Affects Phase 1 length by about a week.)
2. Do you want Core sold standalone from day one (needs a license server in Phase 7), or bundled only in v1?
3. Pincode data source: CSV import by the store owner, or an API adapter to a courier (Shiprocket, Delhivery) in v1?
4. AI provider for the demo: which key will you use for the ThemeForest live preview, or rely on the Null rule-based provider?
5. Reels video: self-hosted only in v1, or include Bunny/Cloudflare Stream adapters?
6. Product page: should "Specifications" come from WooCommerce attributes only, or a separate key/value repeater on the product (your reference has free-text rows like "Best Offer: 1kg, 5kg, 2kg")? My recommendation: attributes by default plus an optional repeater, both rendered by one table.
7. Demo brand name and product niche for the ThemeForest preview.

Once these are answered and the architecture is approved, Phase 0 starts.
