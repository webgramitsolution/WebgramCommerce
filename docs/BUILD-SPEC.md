# Webgram Build Specification for Claude Code (v2, complete)

Status: approved and final. This single document is what Claude Code follows. It supersedes v1. `docs/architecture.md` remains the design rationale; `CLAUDE.md` holds the short rules. Where they differ, this file wins.

Sections:
1. Product and non-negotiables
2. Architecture decisions (including the v2 change: Theme Settings panel)
3. Repository state after Phase 0
4. Exact layout specifications, page by page (from the owner's reference screenshots)
5. Theme Settings panel (WoodMart-inspired), Header builder, Layouts, HTML Blocks
6. Core module specifications
7. Phase plan with tasks and acceptance criteria
8. Data model, REST and AJAX reference
9. Quality gates and report template
10. Claude Code prompts

Reference screenshots (behavior and layout references only; all design assets, copy, demo images and brand names must be original): Prasadam header, Prasadam product card row, Prasadam product detail page, HOUSKASE homepage, reels row, cart drawer, review block, split login page, invoice design, rating pill.

---

## 1. Product and non-negotiables

Webgram Theme (presentation) + Webgram Core (functionality) for WooCommerce, sold on ThemeForest by Webgram IT Solution. WoodMart-level configurability with our own architecture and design.

Non-negotiable (from the owner's approval):
- Theme and Core stay separated. Theme works without Core, WooCommerce and Elementor. Core works with any theme. Elementor optional.
- Standards-based compatibility with third-party WooCommerce plugins (payments, shipping, SEO, caching, swatches, reviews, memberships, forms, analytics). Hooks first, minimal versioned template overrides, never disable or replace other plugins, `WC_Order` APIs only, HPOS compatible, everything namespaced, no global CSS selectors that leak.
- Email delivery external (`wp_mail()`; owner installs any SMTP plugin). Core owns email templates, branding, preview, invoice attachments.
- WhatsApp order notifications built into Core through the store owner's own Meta WhatsApp Cloud API credentials. No Webgram middleman, no Webgram subscription, no hard-coded Meta prices. Async via Action Scheduler, consent required, E.164 phones, template mapping, logs, retries, idempotency.
- Invoice is a clean corporate document: no ribbons, slogans, marketing badges.
- AI assistant optional, provider abstraction, encrypted keys, site works without a key.
- Slider with per-device images, overlays, CTA, autoplay, animation, lazy load.
- Performance: no module assets when the module is off or not rendered. Light and fast is a headline feature.
- Security: encrypted credentials, nonces, capabilities, sanitize, escape, validate external responses, never log secrets.
- No em dashes anywhere.
- Never claim tested when not tested.

---

## 2. Architecture decisions

D1 to D11 from `docs/architecture.md` stand, with one change:

**D4 revised (v2): Theme Settings panel replaces the Customizer as the primary settings UI.** The owner wants a WoodMart-style options panel with a full tab list, Import/Export/Reset, Custom CSS/JS editors, and a header builder page. Implementation:

- Theme provides the panel shell: `Appearance > Webgram` becomes a top-level menu `Webgram` with pages `Theme Settings`, `Header Builder`, `Layouts` (Core), `HTML Blocks` (Core), `Modules` (Core), `Import/Export`, `System status`. When Core is not active, Core pages are hidden and their tabs are absent.
- Storage: one option `webgram_theme_settings` (array, autoloaded, expected under 60 KB) for all theme design settings. `theme_mod` is no longer used except by WordPress for logo and site icon. Phase 0's Customizer panels are removed; `defaults.php`, `webgram_option()` and the CSS token generator are kept and repointed to the new option. The Customizer keeps only Site Identity plus a "Open Webgram Theme Settings" link (ThemeForest requires the theme to be usable without a custom panel; site identity and menus remain native).
- Tabs are registered through `webgram/settings/tabs` filter. Theme registers design tabs; Core registers feature tabs (Promo popup, Age verify, Cookie law, Maintenance, White label, API integrations, Performance extras, Custom CSS/JS storage, Layouts, HTML Blocks). Envato rule satisfied: those features live in the plugin even though they appear in the same panel.
- Field renderer: shared JS-free-as-possible admin UI (vanilla JS for tabs, media picker, color picker, repeaters, sortable, code editor via WordPress `wp_enqueue_code_editor`). Live preview is not required; a "View site" link and unsaved-changes guard are.
- Import/Export/Reset: JSON export of theme settings + header layout + Core settings (per module) + Layouts/Blocks assignments; import validates against field schemas; reset per tab and global with confirmation.

**Layouts (Core, CPT `wg_layout`)**: WoodMart-style. A layout is an Elementor (or Gutenberg) built template for one of: shop archive, single product, cart, checkout, thank you, my account, 404, blog archive, single post, header (alternative), footer. Assignment conditions: all, specific products, categories, tags, brands, pages, post types, logged-in state, device. Priority ordering. When a layout matches, the theme renders it instead of its default template while keeping WooCommerce hooks intact (Elementor widgets like "Product title", "Add to cart", "Gallery" are provided by Core `Integrations` for single product layouts). Without Core or Elementor, the theme's own templates render.

**HTML Blocks (Core, CPT `wg_block`)**: reusable content blocks edited with Elementor or Gutenberg, used in header builder element "HTML Block", footer columns, mega menu columns, product page positions (above summary, below add to cart, after tabs), cart drawer, popups, empty states. Shortcode `[webgram_block id=""]`.

Everything else stays as designed: hooks contract, template resolution, module manager, encrypted settings, Action Scheduler async, custom tables via repositories.

---

## 3. Repository state after Phase 0

See `docs/phases/phase-0-foundation.md`. Phase 1 starts with a refactor task: move settings storage from Customizer to the Theme Settings panel (section 5.1), keeping defaults and the CSS token generator.

---

## 4. Exact layout specifications

All measurements are for the 1320px container at desktop unless stated. Colors and radii are tokens; the values below are defaults. Every element listed has a show/hide and, where sensible, a style choice in Theme Settings or the builder.

### 4.1 Header (three rows)

Row 1, Top bar ("marquee"): height 36px, background `topbar_bg` (default light pink `#fff1f2`), text `topbar_text` (dark red `#7f1d1d`), 14px, weight 600. Content is a continuous horizontal marquee of repeated messages separated by a small dot. Each message: optional icon (gift, truck, tag) + text, e.g. "Flat 10% OFF on Prepaid Orders • Use Code: HYP15", "Free Shipping on Orders Above ₹499". Settings: messages repeater (icon, text, link), speed (px/s), direction, pause on hover, gap, mode (marquee | static centered | slide one at a time), visibility per device. Implementation: CSS animation on a duplicated track for seamless loop, JS only to duplicate content to fill width; `prefers-reduced-motion` switches to static.

Row 2, Main header: height 72px, white, bottom border 1px. Grid left to right:
1. Logo (max height 56px).
2. "Deliver to / Select location" pill: pin icon, small grey label "Deliver to", bold value "Select location" or the saved city + pincode. Opens the Location picker (4.1.1).
3. Search bar: flexible width (min 420px), height 46px, pill radius, light grey background `#f3f4f6`, search icon left, placeholder "Search products...", microphone icon right (Core voice search; hidden if Core off or browser unsupported). Live suggestions dropdown (products with thumb + price, categories, popular searches).
4. Right icon group, each icon 22px stroke with 11px label beneath: Track Order, Bulk Order, Wishlist (count badge), Compare (count badge), Help, Cart (count badge), Account (with small chevron; hover dropdown: Login/Register or Dashboard, Orders, Wishlist, Logout). Any element can be removed or reordered in the builder; labels can be hidden.

Row 3, Secondary bar: height 40px, background primary red, centered menu (`secondary` location), white 14px weight 600 links, 32px gap, optional small badge per item ("New", "Sale") from mega menu fields. On hover, mega menu panels drop from this row or from the main menu if the primary menu is placed in row 2.

Sticky: row 2 sticks (optional row 3), shadow when stuck, shrink to 60px option, hide-on-scroll-down/show-on-scroll-up option.

Mobile (under 992px): Row 1 marquee stays (smaller text). Row 2: hamburger left, logo center, cart and search-toggle right (icons only). Row 3 becomes a full-width search bar under the logo (optional) and the secondary menu becomes a horizontally scrollable chip row. Drawer menu from left with tabs Menu | Categories and account links. Optional Mobile bottom navbar (5.1 General > Mobile bottom navbar): fixed bottom bar with Home, Shop, Search, Wishlist, Cart/Account (configurable items, icons, labels, badge counts), hidden on scroll down option, safe-area padding.

#### 4.1.1 Location picker (no paid API)
Modal "Select delivery location": (a) Pincode input with 6-digit validation; on submit, Core resolves city and state from the offline pincode table imported by the store owner (CSV: pincode, city, state, deliverable, cod, eta_days; a starter CSV with Indian pincodes is shipped in `webgram-core/data/` if licensing allows, otherwise the owner imports). (b) "Use my current location" button: browser Geolocation gives coordinates; conversion to pincode needs a service, so Core offers a reverse-geocoding adapter interface with a default of "none" and an optional free adapter (OpenStreetMap Nominatim, off by default, with attribution and usage notice) plus a hook for paid providers. When no adapter is enabled the button is hidden. (c) Saved location: cookie `wg_location` (pincode, city, state) for 30 days plus user meta for logged-in users. Header pill and PDP pincode checker read the same value. Country code handling from WooCommerce base country; the picker labels "Pincode" or "Postal code" by country.

### 4.2 Product card (exact)

Card: white, radius 12px, 1px border `#eee`, hover shadow `md`. Structure top to bottom:
1. Image box, square (1:1, configurable 3:4), radius follows card, image `object-fit: cover`. Second image slides in on hover; if the product has more gallery images, they auto-cycle every 1.2 s while hovering (Theme Settings: hover effect none | swap | slideshow; touch devices show a dot row instead). Uses `<img>` elements loaded lazily on first hover, never on page load.
2. Wave badge, top-left, overlapping the image top edge: primary red background, white 13px weight 600 text "Save ₹401", shape is a rectangle with a concave wave on the bottom-right corner and a thin gold (`accent`) underline curve. Implemented as an SVG mask so the color follows tokens. Badge styles selectable: wave (default), pill, rectangle, ribbon. Content rule: "Save {amount}" when on sale (variable: max saving), "{percent}% off" alternative, "New", "Best seller", custom text (Core Badges rules). Multiple badges stack vertically with 4px gap.
3. Quick actions (top-right, on hover): wishlist heart, compare, quick view (Core). Icon buttons 36px white circle with shadow.
4. Body, padding 16px:
   - Title: 15px, weight 500, 2 lines clamped, dark heading color, hover primary.
   - Price line: one row. Left: sale price 18px weight 700 ("₹499"), regular price strike grey 14px ("₹900"), green "45% off" 14px weight 600. Right, same line, aligned right: rating pill "★ 4.5 (12)" 12px (star gold, count grey). If no reviews, nothing on the right. Theme Settings: rating position (price line right | under title | hidden).
   - Variation chips row (variable products only): up to 3 chips 11px, dark grey background, white text, radius 4px ("Pack of 1", "Pack of 2", "Pack of 3"); first/selected chip in primary red. Chip click updates price, image and add-to-cart variation id without reload. If the product has more than 3 variations for the chosen attribute, a "+N" chip links to the product page. Attribute chosen per product (metabox) or globally (setting: first attribute). Chips can also render as color circles or image thumbs (attribute type setting).
   - Button row: 8px gap. Left: 44x44 outline button with cart icon (adds to cart via standard WC AJAX). Right: full-width "BUY NOW" button, primary red, white 14px uppercase weight 700, letter-spacing 0.04em, subtle diagonal highlight (a "shine" pseudo-element, can be disabled), goes to checkout with the selected variation. Buttons configurable: show/hide each, labels, style, order.
5. List variant: image left 200px, content right, short description, same actions. Compact variant (chatbot, drawer recommendations): 64px thumb, title 2 lines, price, ADD button.

### 4.3 Homepage sections (HOUSKASE and Prasadam references)

Order and appearance of the default demo homepage, each an Elementor widget and a block:

1. Hero slider: full container width (option: full screen width), 16:6 desktop, 4:5 mobile with separate mobile image, headline left, subtext, 4 benefit icons row inside the slide (optional icon list), CTA "SHOP NOW", dots bottom center, arrows on hover, autoplay 5 s, fade.
2. Marquee strip 2 (dark red background, white text, icons): same component as top bar with different colors. "Free shipping across India on orders above ₹499 • Trusted by 25,000+ homes and businesses".
3. Category circles row: 8 items, circle 120px with image, label shown on a ribbon banner overlay (red ribbon with white uppercase 11px text) or under the circle (setting), shape circle | square | rounded, hover scale 1.04, arrows on overflow, source: selected categories or top-level.
4. "Trending Products": section heading centered with the dot-line ornament on both sides (dot, short line, title, short line, dot; rendered as an SVG ornament, never as text characters), "View All →" link right. Row of 5 product cards (slider on mobile with 2.2 cards visible). Source: Core trending score, fallback most viewed then newest.
5. "Mega Saver Packs": same layout, source: tag or category chosen in widget.
6. "Best Sellers" band: dark navy (`secondary` token) full-width band, radius 16px inside container, left column with large white text "BEST" and script "Sellers" (two-line heading, second line italic display), then 4 product cards on the right, dots below, "View All" under the band. Source: best selling.
7. "Products Reels": 5 reel cards 9:16 (see 4.6).
8. "About Us": image left (40%), text right: heading with ornament, two paragraphs, 3 benefit cards (icon, title, text) in a row, red CTA "Know More →" (arrow icon, not a character).
9. "From Our Blog": 4 post cards with 16:10 image, title, excerpt, "Read more →".
10. "Trusted by Families" (testimonials): dark band, 3-card slider, each card: photo left (40%), product name bold, text, reviewer name and age/label, 5 gold stars; dots.
11. "Instagram Feed": heading with ornament, "Follow Us" outline pill button right, 6 square tiles with hover icon.
12. Benefits row: 5 columns, red outline icon 28px, bold title, small grey text (Great Value, Nationwide Delivery, Secure Payment, Buyer Protection, 365 Days Help Desk).
13. Footer (4.9).

Floating social sidebar (right edge, vertically centered): stacked square buttons 44px in brand colors (Facebook blue, Instagram gradient, WhatsApp green), from Theme Settings > Social profiles, per-device visibility. Optional "Back to top" button bottom-right.

### 4.4 Product detail page (exact)

Breadcrumb above (optional). Two columns: left 50% gallery, right 50% summary with 40px gap, both inside a soft cream background section (`bg_alt`) with white rounded panels (option: flat).

Left (sticky, `top: header height + 16px`, releases when the right column ends):
- Main image 1:1, radius 12px, zoom on hover, arrows, wave badge top-left, wishlist heart top-right.
- Thumbnail strip below: horizontal, 9 visible, 64px squares, active has red border, scroll arrows; option vertical strip left of main image.
- Auto slide: main image auto-advances every 3 s until the user interacts (Theme Settings: on/off, interval, pause on hover). Video thumbnails supported (Core product video).

Right (normal flow):
1. Title 24px weight 600, 2 lines max, then meta row (SKU, category) small grey (optional).
2. Price line: "₹499" 32px weight 700, "₹850" strike grey 18px, "41% OFF" green 18px weight 700; rating pill "★ 4.53/5 | 196 reviews" on the right end of the same line (link scrolls to reviews). Exact style from the rating pill reference: light green background pill, gold stars, bold rating, divider, count.
3. "Select Variant: 1kg" label, then image swatches: cards 120px wide, image on top, name, price and strike price below, selected card has red border and a check icon top-right; out of stock greyed with diagonal line. Works on top of the standard WooCommerce variations form.
4. Coupon box (Core): light green background, green check icon, "FLAT 20% OFF Use code" + code chip "SAVE20" + "Copy Code" outline button right with clipboard icon, toast "Code copied".
5. Divider.
6. Row: quantity stepper (44px, minus, value, plus) + "ADD TO CART" red button with cart icon (50%) + "BUY NOW" dark red button with lightning icon (50%).
7. Payment strip: bordered box, "100% Secure Payment By" + processor logo slot (uploadable image), row of payment method icons (Visa, Mastercard, UPI, RuPay, GPay, PhonePe, Paytm, Amazon Pay, Net banking) selected from a list, caption text.
8. Trust seals row: 5 circular seals 64px (Secure payments, Fast and free shipping, Premium quality, Customer support 24/7, Satisfaction guaranteed): SVG seal style in dark grey, editable icon and text, count 3 to 6.
9. Contact cards row (Core ContactSeller): 3 equal cards with red icon and two-line text: "Call us at +91 ...", "Buy on Chat", "Ask for Bulk Qty Quote" (opens Bulk Inquiry modal).
10. "Check Delivery Details" (Core Pincode): label with pin icon, input (pre-filled from saved location) + red "Check" button; result line green "Delivery by Tue, 9 Sep • COD available" or red "Not deliverable to this pincode".
11. Two info cards: "Returns / As per Brand / 7 days" and "Shipping / Free for bulk orders" with icons; text from settings or per product.
12. "SPECIFICATIONS" heading uppercase 13px letter-spaced, zebra table: label column 35% grey, value column; source attributes + Core key/value repeater.
13. "OVERVIEW": long description, prose styles.
14. Share row (optional) and meta.

Below the two columns, full width:
- "Related Products" with ornament heading, 5 cards slider, dots.
- Reviews summary block (4.7 layout): left big average "0.0" with empty stars and "0 Reviews", middle distribution bars 5 to 1, right dark "Write a review" button. Below, empty state card with icon: "No reviews yet. Be the first to share your experience with this product and help other shoppers decide." When reviews exist, the list (4.7).
- "Trending Reels" row (Core Reels) with ornament heading.
- Recently viewed row.

Section order of the right column and of the below-fold blocks is editable in Theme Settings > Single product (sortable lists), and fully replaceable by a Core Layout.

Mobile: single column, gallery slider with dots, sticky bottom bar with price + ADD TO CART + BUY NOW (setting), sections stacked with accordions for Specifications and Overview.

### 4.5 Shop archive
Page title band with category image and description; toolbar; filters sidebar with collapsible groups and swatches; 5-column grid (2 on mobile); pagination or load more; subcategory chips at top. Product card as 4.2.

### 4.6 Reels (exact)
Card 9:16, radius 16px, dark background with poster; video autoplays muted when in viewport (IntersectionObserver, one at a time on mobile, all visible on desktop), loops, no controls; tap or click opens full-screen viewer (mobile: vertical swipe feed; desktop: centered 9:16 player with side arrows and dark backdrop). Bottom of each card: white rounded mini product card (56px thumb, title 1 line bold 13px, price red bold + strike grey) which adds to cart on the ADD icon or opens product on click. Row: 5 visible desktop, 2.2 mobile, arrows in white circles at row edges. Setting: autoplay on/off, mute toggle button on card (bottom-right), show product card.

### 4.7 Reviews block (exact)
Top panel: left column centered: "4.8" 48px weight 700, 5 red stars, "out of 5", "(256 Reviews)". Right: five rows "5 Star" label, progress bar (light grey track, red fill, radius pill), count. "Write a review" dark button on the right for the top row (or under the rating).
"Customer Reviews" heading with "Showing 1-4 of 256 reviews" grey, "Sort by: Newest ▾" select on the right (Newest, Oldest, Highest, Lowest, Most helpful, With photos). Filter chips: All, 5, 4, 3, 2, 1, With media.
Review item: avatar 48px circle left; name bold 14px; "Verified Buyer" pill (light pink bg, red text) under the name; right column: 5 red stars + "5.0"; bold title; body text; media thumbnails 64px squares in a row (click opens lightbox with video support); "2 days ago" relative date right-aligned; helpful "Was this helpful? 👍 12" (icon SVG, not emoji). Divider between items. "View All Reviews" red button centered opens the full list modal or expands. Form: title, rating stars input, body, media upload with previews, name/email for guests, consent checkbox, submit.

### 4.8 Cart drawer and cart page (exact)
Drawer from right, width 420px, white, header "Your Cart (1 Item)" bold 24px with close icon. Offer progress card (grey rounded): text "Unlocked 15% OFF | Code: HYP15 Or Add 1 More to Buy 2 @ ₹799", then a horizontal progress bar with 3 milestone nodes (achieved node filled brand color with check, upcoming grey); labels under nodes "15% OFF", "Buy 2 @ 799", "Buy 3 @ 1149". Recommendation carousel: single-row cards with 64px image, 2-line title, price, dark "ADD" button, arrows. Line items: 72px image, title 2 lines, price right, qty stepper pill (−, 1, +) and trash icon. Footer: "SUBTOTAL" and amount, note text "Apply coupon at next step | Not applicable on combos & gifts" (editable), dark full-width "PLACE ORDER" button with subline "Get Extra 5% off on prepaid orders" and payment mini icons, chevron right. Coupon field optional in drawer. Free shipping bar variant available.
Cart page: same items in a two-column layout with sticky summary card, coupon, cross-sells row, "Continue shopping".

### 4.9 Footer (exact)
Dark red background (`footer_bg`), light text. Row 1: 5 columns: brand column (logo, 3-line description), "Categories" list, "Policy" list, "Support" list, "Connect" list with circular outline social icons + labels (Facebook, Instagram, LinkedIn, X). Column headings 16px bold with thin divider under (optional). Row 2: centered copyright "© 2026 {Company}. All rights reserved." Payment icons row optional.

### 4.10 Login / Register page (exact)
Two halves. Left (50%): segmented toggle pill (grey track) with "Login" and "Signup" (active segment filled red); Signup form: two-column grid (Full Name, Email; Password, Confirm Password) with icon prefixes and eye toggle, full-width red "Create Account" button; Login form: email/username, password, remember me, forgot link, red "Login" button. Under the form: trust logos strip (uploadable images, e.g. certification marks). Right (50%): full-bleed image from settings (desktop and mobile variants; hidden on mobile by default). Social login plugins render in the standard hook area.

### 4.11 Invoice (exact, from the Webgram reference)
A4 portrait. Header: logo + tagline left; contact block center (address, phone, email, website with small icons); right: dark navy block (`secondary` token or invoice color setting) with white "INVOICE" 28px bold, "#WG-2026-000123", then three rows "Order Date : 04 Sep 2026", "Payment Date", "Order Status". A thin dark rule under the header.
Three columns with icons: Billing Address, Shipping Address, Order Information (Invoice Number, Order Number, Order Date, Payment Method, Shipping Method). Vertical dividers.
Items table: dark navy header row with white labels (#, Product, SKU, Price, Quantity, Total), rows with 56px product thumbnail, bold name and grey variation line, right-aligned money, zebra optional. HSN column optional. Tax columns optional (CGST/SGST/IGST).
Below: left grey box "Payment Information" (Paid via Razorpay (UPI), Transaction ID) and "Notes" (customer note, then a configurable support line "If you have any questions about this invoice, contact support@... or call ..."). Right: totals list (Subtotal, Discount (CODE) in green negative, Shipping, Tax (GST 18%)) and a highlighted "Grand Total" box.
Footer: three columns: company (name, GSTIN, address), "Need Help?" with headset icon, phone, email; social icons and website. Bottom strip: "This is a computer generated invoice and does not require a signature." and "Invoice generated on {datetime}". Colors from settings: accent color, text color; logo upload; all labels translatable; RTL aware.

### 4.12 Track Order, Bulk Order, Help pages
Track Order: centered card with order number + email/phone, result timeline (Placed, Confirmed, Packed, Shipped, Out for delivery, Delivered) with dates, carrier and tracking link if available, items list. Bulk Order: form (name, company, phone, email, product select with search, quantity, message), benefits list on the side, WhatsApp button. Help: FAQ accordion from Core settings + contact cards.

---

## 5. Theme Settings panel, Header builder, Layouts, HTML Blocks

### 5.1 Theme Settings tabs (owner list, mapped to owner: T = theme, C = Core)

General (T): site layout boxed/wide, container width, preloader (T, simple), back to top, RTL, favicon link to Customizer.
Layout (T): default page layouts, sidebar widths, breakpoint columns.
Header banner (T): a promotional banner strip above/below header with image or HTML Block, close button, cookie remember.
Promo popup (C): HTML Block or content, trigger (delay, scroll %, exit intent), frequency (once per day/session), device targeting, pages.
Age verify popup (C): text, minimum age, yes/no or date input, remember days, background image.
Cookie law info (C): text, button labels, position, link to policy, remember days, GDPR "reject" button.
Mobile bottom navbar (T): items repeater (icon, label, link/action: home, shop, search, wishlist, compare, cart, account, custom), style, hide on scroll.
Search (T + C): live search on/off, results count, search in categories, popular searches, voice (C), search page layout.
Sticky navigation (T): rows, shrink, hide on scroll down.
Page title (T): show, height, background, alignment, breadcrumb.
Footer (T): builder link, columns, widgets, copyright, payment icons, bottom bar.
Typography (T): body, heading, menu, button font families (self-hosted list + Google + custom upload), sizes per element with device tabs, weights, line-height, letter-spacing.
Styles and colors (T): all tokens (4.x), button styles, form styles, card styles, badge style, radius scale, shadows.
Blog (T): layouts, card style, meta, sidebar, related posts, share.
Portfolio (C): optional CPT `wg_portfolio` with archive and single templates in theme (off by default).
Shop (T): columns per device, card options (4.2), hover effect, badges positions, toolbar, filters, pagination type, AJAX, quick view, wishlist/compare buttons placement, "Buy now" behavior link to Core.
Product archive (T): category page banner, subcategory chips shape, description position.
Single product (T): layout (default | Core Layout), gallery style, thumbnails position, auto slide, sticky, section order (sortable), tabs vs stacked, sticky mobile bar, related/recently viewed counts, trust seals, payment strip, info cards.
My account (T): login page split image and trust logos, dashboard cards, navigation icons.
Social profiles (T): links repeater, floating sidebar on/off, share buttons.
API integrations (C): Google Maps key (optional, for future), reverse geocoding adapter, Instagram, WhatsApp, AI providers (deep links to module tabs).
Performance (T + C): lazy load, font preload, disable emojis/embeds, defer, critical header CSS, image sizes, CDN prefix for theme assets.
Maintenance (C): mode (off, coming soon, maintenance), page or HTML Block, allowed roles, countdown, 503 header.
White label (C): rename "Webgram" in admin, replace logo/icons in admin panel, hide dashboard sections (does not remove attribution in code or licensing).
Custom CSS (T storage in the same option; editor with syntax highlighting; separate desktop/tablet/mobile fields; printed inline after tokens).
Custom JS (C): header/footer scripts, sanitized to `manage_options` + `unfiltered_html` capability checks, printed with `wp_add_inline_script`.
Other (T): 404 page content or Layout, Coming soon link, maintenance link, RTL toggles.
Import / Export / Reset (T): JSON, includes Core settings when present, demo presets list.

Every field: id, label, type (text, textarea, number, switch, select, radio-image, color, image, file, repeater, sortable, typography, dimensions with device tabs, code, html-block picker, page picker, menu picker), default, description, dependency (show when another field equals). Fields are declared in PHP arrays under `inc/settings/tabs/*.php` (theme) and `webgram_core/settings/tabs` filter (Core). Sanitizers per type.

### 5.2 Header builder (page `Webgram > Header Builder`)
Visual builder with three rows (top, main, bottom) each with left/center/right areas, desktop and mobile tabs, drag elements from a palette, click element to open its settings, row settings (height, background, border, sticky, container), presets (Classic, Minimal, Two-row, Centered logo, Prasadam-style), save as JSON in `webgram_header_layout` option. Elements: logo, primary menu, secondary menu, vertical categories menu, search (style bar/icon, mic), deliver-to, track order, bulk order, help, wishlist, compare, cart (icon or drawer trigger), account, announcement marquee, text, button, HTML Block, social icons, phone, currency/language slot, divider, spacer, mobile menu toggle, search toggle. Core registers wishlist, compare, mic, deliver-to via filter and they disappear cleanly when Core is off. Mobile drawer and bottom navbar settings live here too.

### 5.3 Layouts (Core) and 5.4 HTML Blocks (Core): as in section 2.

---

## 6. Core module specifications

Module list (18): woo_enhancements, badges, quick_view, coupons, wishlist, compare, reviews, slider, instagram, integrations, reels, voice_search, ai_assistant, invoice, emails, notifications, analytics, site_tools (new: promo popup, age verify, cookie law, maintenance, white label, custom JS, layouts, html blocks, portfolio). Add `site_tools` to `ModuleManager::definitions()`.

Module specs from v1 are retained (WooEnhancements incl. BuyNow, RecentlyViewed, Specifications, ProductVideo, ContactSeller, BulkInquiry, PincodeChecker with offline city resolution and location picker support, TrackOrder; Badges; QuickView; Coupons with product coupon box and cart offer progress with milestones; Wishlist and Compare with signed guest cookies and merge; Reviews on WooCommerce comments with media; Slider with per-device images; Instagram Graph API token mode plus manual fallback; Integrations with Elementor widgets and blocks plus Layout widgets for single product; Reels with autoplay muted; VoiceSearch; AiAssistant with providers and tools; Invoice with configurable numbering and dompdf; Emails branding via WooCommerce template hooks; Notifications with Email and WhatsApp Cloud API channels; Analytics). The full text of those specs is in Appendix A below, unchanged except where section 4 adds exact UI.

---

## 7. Phase plan (v2)

Phase 1 (3 weeks): Theme Settings panel shell and field system, migration from Customizer, header builder with marquee top bar and all elements incl. Deliver-to pill, mega menu, mobile drawer, bottom navbar, sticky, live search, footer builder, floating social sidebar, back to top, Import/Export/Reset. Core: site_tools (HTML Blocks, Layouts CPT + conditions engine, promo popup, cookie law, age verify, maintenance, white label, custom JS), pincode location picker in WooEnhancements (moved earlier because the header needs it).
Acceptance: header 4.1 reproduced exactly from the builder; every 5.1 tab present with at least its core fields; export/import round trip restores a site.

Phase 2 (3 weeks): product card 4.2 with wave badge, hover slideshow, chips, buy now; shop archive 4.5; PDP 4.4 with sticky, auto-slide, variant image swatches, rating pill, coupon box, payment strip, seals, contact cards, pincode, info cards, specs, overview, section ordering, mobile sticky bar. Core: woo_enhancements rest, badges, quick_view, coupons.

Phase 3 (2 weeks): cart drawer 4.8 with offer progress and recommendations, cart page, checkout, thank you, account, login split 4.10, track order, bulk order, help pages.

Phase 4 (2 weeks): reviews 4.7 with rating pill sources, wishlist, compare.

Phase 5 (3 weeks): homepage sections 4.3, slider, Instagram, testimonials CPT, Elementor widgets and blocks, Layout widgets for single product, demo content in Elementor and Gutenberg.

Phase 6 (3 weeks): reels 4.6, voice search, AI assistant.

Phase 7 (3 weeks): invoice 4.11, emails, notifications (WhatsApp), analytics dashboard.

Phase 8 (3 weeks): hardening, compatibility matrix, RTL, i18n, accessibility, packaging, docs, demo, staging on Hostinger VPS.

---

## 8. Data model, REST and AJAX reference

CPTs: `wg_slider`, `wg_reel` (+ `wg_reel_category`), `wg_inquiry`, `wg_testimonial`, `wg_layout`, `wg_block`, `wg_portfolio` (optional).
Tables: `wg_invoice_sequence`, `wg_ai_conversations`, `wg_ai_messages`, `wg_events`, `wg_pincodes`, `wg_notification_log` (schemas: architecture.md section 6 and Appendix A Phase 7).
Options: `webgram_theme_settings`, `webgram_header_layout`, `webgram_footer_layout`, `webgram_core_modules`, `webgram_core_settings_{module}`.
Cookies: `wg_location`, `wg_recent`, `wg_wishlist`, `wg_compare`, `wg_ai_session`, `wg_popup_seen`, `wg_age_ok`, `wg_cookie_ok`.
WC-AJAX and REST endpoints: as listed in Appendix A section 8, plus `location_resolve` (pincode to city), `layout_preview` (admin).

---

## 9. Quality gates and report template

Per phase: `php -l` all changed files; `php tests/core-harness.php` and `php tests/theme-harness.php` pass with new checks added for new logic; `npm run build` clean; PHPCS WordPress-Extra + WooCommerce zero errors; manual pass in local WordPress on desktop, tablet, mobile widths; Lighthouse on the affected page; report file `docs/phases/phase-N-name.md` with sections: Implemented, Files and modules changed, Database and API changes, Tests performed (commands and manual steps), Errors found and fixed, Compatibility concerns, Not tested, Known limitations, Ready for next phase.

---

## 10. Claude Code prompts

Phase 1: "Read CLAUDE.md, then docs/BUILD-SPEC.md sections 1 to 5 and Phase 1 in section 7, and docs/phases/phase-0-foundation.md. Inspect webgram-theme/inc, template-parts, assets/src and webgram-core/src. First task: migrate settings storage from the Customizer to the Theme Settings panel described in 5.1 while keeping defaults.php and the CSS token generator. Then build the header builder and everything listed for Phase 1. Reproduce the header in section 4.1 exactly. Ask only if an architectural ambiguity blocks you. Run php -l, both harness tests and npm run build before reporting. Write docs/phases/phase-1-settings-header.md."

Phase 2: "Read CLAUDE.md, BUILD-SPEC sections 4.2, 4.4, 4.5, 6 (WooEnhancements, Badges, QuickView, Coupons in Appendix A) and Phase 2, plus the Phase 1 report. Implement the product card and product detail page exactly as specified, keeping WooCommerce template overrides within the allowed list. Report as specified."

Phases 3 to 8: same shape, citing the relevant 4.x sections, Appendix A modules, and the previous report.

Bug fixing: "Do not rewrite unrelated code. Read the full error, find the root cause, apply the smallest safe fix, rerun the failing check, report what changed."

---

## Appendix A: Detailed module and phase specifications (carried from v1)

### A.5. Theme / Core contract (hooks)

Theme fires, Core listens:

| Hook | Where | Purpose |
|------|-------|---------|
| `webgram/header/icons` (filter, array of HTML strings) | header main | Core wishlist/compare add their icon buttons with counts |
| `webgram/search/input` (action) | inside search form | Core voice search adds mic button |
| `webgram/header/deliver_to` (action) | header | Core pincode module renders "Deliver to" pill |
| `webgram/product_card/badges`, `/actions`, `/after_price`, `/buy_now` (actions, `$product`) | product card | badges, wishlist/compare/quick view buttons, coupon hint, buy now |
| `webgram/product_card/args`, `/classes` (filters) | product card | |
| `webgram/product/summary/{section}` (actions) | PDP right column | Core injects coupon box, pincode checker, contact seller, bulk inquiry, specifications |
| `webgram/product/after_summary`, `/after_tabs`, `/before_related`, `/after_related` (actions) | PDP | reviews block, reels row, recently viewed |
| `webgram/cart/before_items`, `/after_items`, `/before_totals`, `/after_totals`, `/before_checkout_button` (actions) | slide cart and cart page | offer progress, recommendations, coupon form |
| `webgram/checkout/after_order_review`, `/before_payment` (actions) | checkout | trust info |
| `webgram/account/after_order_actions` (action, `$order`) | My Account orders | invoice download button |
| `webgram/thankyou/after_details` (action, `$order`) | thank-you page | invoice download, track link, WhatsApp opt-in confirmation |
| `webgram/tokens` (filter) | CSS generator | Core may add variables |
| `webgram/frontend_data` (filter) | localized JS data | |

Core fires, Theme listens: `webgram_core/loaded`, `webgram_core/register_assets`, `webgram_core/event` (analytics), `webgram_core/notification_sent`.

Every renderable Core component is a template in `webgram-core/templates/{module}/{name}.php`, overridable by the theme at `webgram-theme/webgram-core/{module}/{name}.php`. The theme ships overrides only when Core's default markup does not fit the design; otherwise it styles Core's markup with `.wg-` classes exposed through `wgc_class()` helper that maps to theme classes when theme support is declared.

---

### A.6. Phase specifications

Each phase: Goal, Tasks (ordered), Data, Acceptance criteria, Tests. Files named relative to the product root.

#### Phase 1: Header builder, footer builder, mega menu, mobile menu

Goal: Any header/footer from the reference screenshots must be reproducible from the Customizer alone. Elementor not involved.

Tasks:
1. Header layout data model. `theme_mod` `header_layout` holds JSON:
   ```json
   {"desktop":{"topbar":{"enabled":true,"left":[],"center":["announcement"],"right":[]},"main":{"left":["logo","deliver_to"],"center":["search"],"right":["track_order","bulk_order","help","wishlist","compare","cart","account"]},"bottom":{"left":[],"center":["menu_secondary"],"right":[]}},"mobile":{"main":{"left":["menu_toggle"],"center":["logo"],"right":["search_toggle","cart"]},"bottom":{"left":[],"center":["search"],"right":[]}},"sticky":{"enabled":true,"rows":["main"]}}
   ```
   Defaults in `inc/customizer/defaults.php`. Sanitizer validates element ids against the registry and strips unknown ids.
2. Element registry `inc/builders/class-header-builder.php`: each element is a class in `inc/builders/elements/` implementing `id()`, `label()`, `render(array $settings)`, `settings_fields()` (element-specific Customizer settings, e.g. logo width, search placeholder, icon style with label/without label), `is_available()` (cart/account/wishlist need WooCommerce/Core). Elements: logo, menu_primary, menu_secondary, search, search_toggle, deliver_to, track_order, bulk_order, help, wishlist, compare, cart, account, announcement (rotating messages list with icon, speed, pause on hover), social, button, html, divider, menu_toggle, spacer. Core-provided elements (wishlist, compare, mic in search) register through `webgram/header/elements` filter.
3. Renderer `class-builder-renderer.php`: reads layout JSON, prints rows with `wg-header__row--{name}` and columns `wg-header__col--{left|center|right}`; each element wrapped in `wg-header__el wg-header__el--{id}`. Same renderer for footer with rows/columns of widgets areas plus elements: logo, description, menu, social, newsletter (renders a form hook `webgram/footer/newsletter` for any newsletter plugin; no own mailing list), payment icons (selectable set of SVGs: Visa, Mastercard, UPI, RuPay, PayPal, Amex, Paytm, PhonePe, Google Pay, COD), trust text, copyright, html.
4. Customizer control `inc/customizer/controls/class-control-builder.php`: vanilla JS drag-and-drop between row columns, element settings popover, desktop/mobile tabs, "Reset to preset" button. Presets: 3 desktop (classic search-center, minimal, two-row with secondary bar), 2 mobile. Live preview via `refresh` for layout, `postMessage` for colors/spacing.
5. Per-row settings: background color, text color, height, padding, border, container full/boxed, visibility (desktop/tablet/mobile).
6. Sticky header: rows selectable, shrink option, show-on-scroll-up option. JS in `modules/header.js`.
7. Mega menu (`inc/mega-menu/`): fields on Appearance > Menus items (top-level: mega enabled, width full/container/custom px, columns 2 to 6; child: column heading style, image, badge text and color, icon, description, "promo block" with image + heading + link, hide label). Walker outputs `wg-mega` panels. Hover intent on desktop with keyboard support (Enter/Space open, Esc close, arrow keys). Output cached in transient per menu location, flushed on `wp_update_nav_menu`. Mobile: accordion with the same items.
8. Mobile drawer menu: tabs "Menu" and "Categories" (WooCommerce product categories with thumbnails, only if WooCommerce active), account links, currency/language slots via hooks.
9. Search overlay for `search_toggle`: full-width overlay, live results via `wc-ajax=webgram_live_search` (theme AJAX in `class-wc-ajax.php`, uses `wc_get_products` with `s`, limit 8, cached 5 min, returns compact product cards), popular searches list from Customizer.
10. Styles: `layout/_header.scss` rewritten for row/column model, `_mega-menu.scss`, `_search-overlay.scss`. Keep gz budget.
11. Theme dashboard: "Header presets" quick apply.

Acceptance: Prasadam header (announcement rotating bar, logo, "Deliver to", search with mic slot, Track Order / Bulk Order / Help / Cart / Account icon-with-label, red secondary bar with three links) and HOUSKASE footer (5 columns: brand+description, Categories, Policy, Support, Connect; copyright row) buildable without code. Keyboard navigation of mega menu works. Header height variable updates on resize. Lighthouse accessibility for header 100. No jQuery.

Tests: harness for layout sanitizer (unknown element stripped, malformed JSON falls back to preset), walker output snapshot for a 3-level menu, manual for drag-and-drop.

#### Phase 2: Shop archive and product detail page (+ Core: woo_enhancements, badges, quick_view, coupons)

Goal: reference PDP behavior including sticky gallery release and section ordering.

Theme tasks:
1. Archive: toolbar (result count, sort, grid/list toggle, columns 3/4/5 on desktop), filters sidebar (WooCommerce filter widgets + Core attribute filter widget if present) with AJAX filtering and URL state (`?filter_color=red` standard WC params), mobile filter drawer, load more or pagination (Customizer), category header with image and description, subcategory chips (circle/square per `category_card_shape`), empty state.
2. Product card final: variation swatches for one attribute (Customizer picks which, e.g. "Pack"), selecting swatch updates price and add-to-cart variation id via `data-variations` JSON printed once per card (`wc_get_product`->`get_available_variations` trimmed to id, price html, image), list variant, compact variant.
3. Single product template `woocommerce/single-product.php` + `template-parts/product/*`: two columns 55/45 desktop; left gallery `position: sticky; top: calc(var(--wg-header-height) + 16px)`; right column flows. JS disables sticky under 992px and when right column is shorter than gallery. Gallery: main image with zoom, arrows, vertical or horizontal thumbnails (Customizer), video slide support (product meta `_webgram_video_url` set by Core WooEnhancements), lightbox via WC support flags.
4. Section ordering: Customizer sortable control `product_summary_order` with ids: `breadcrumb, title, rating, price, short_description, variations, coupon, quantity_cart, buy_now, trust_badges, contact_seller, pincode, shipping_returns, specifications, overview, share, meta`. Renderer maps ids to callbacks on `woocommerce_single_product_summary` with priorities 10, 20, 30... Ids whose provider is missing are skipped.
5. Variation UI: image swatches with price under each (reference: 1kg/2kg/5kg with price and strike price), selected state, out-of-stock state; keeps standard `variations_form` markup so variation plugins still work (the swatches are a presentation layer that clicks the hidden select).
6. Quantity minus/plus (`global/quantity-input.php` override), Add to Cart + Buy Now side by side, trust badges row (Customizer repeater: icon, label, sublabel), payment strip (Customizer text + payment icons), shipping/returns cards (two cards with icon, title, text), specifications table, overview (long description), share links, related products (5 columns cards), recently viewed row.
7. Tabs replaced by stacked sections on mobile (accordion) and inline sections on desktop, Customizer choice "tabs" or "stacked".
8. Styles `product.scss` loaded only on `is_product()`.

Core tasks (`WooEnhancements`, `Badges`, `QuickView`, `Coupons`):
1. WooEnhancements: BuyNow (button, `webgram_buy_now` flag, optional empty cart, redirect to checkout), RecentlyViewed (cookie `wg_recent`, max 20, shortcode `[webgram_recently_viewed]`, hook into `webgram/product/after_related`), Specifications (product metabox key/value repeater `_wg_specs` plus WooCommerce attributes merged; setting to choose source), ProductVideo (`_webgram_video_url` metabox), ContactSeller (global settings: phone, WhatsApp number, chat URL; per-product override; renders three cards: Call, Chat on WhatsApp via `wa.me` link with prefilled product name and URL, Ask for bulk quote), BulkInquiry (form modal: name, phone, email, qty, message; honeypot + nonce + rate limit 5/hour/IP; stores `wg_inquiry` CPT private + emails admin via `wp_mail`; admin list with status column), PincodeChecker (Settings: mode "all deliverable" / "CSV list" / "API adapter interface" with adapter classes for future courier plugins; CSV import to `wg_pincodes` table; frontend input with 6-digit validation, AJAX check, remembers pincode in cookie `wg_pincode` and shows in header "Deliver to"), TrackOrder (shortcode `[webgram_track_order]`, page template from theme; form order id + email or phone; AJAX lookup via `wc_get_orders` with billing match; shows timeline of statuses and shipment meta from popular shipping plugins if present via filter `webgram_core/track_order/data`; rate limit 10/hour/IP).
2. Badges: rules in settings (new arrival days, show percentage on sale, best seller threshold by `total_sales`, low stock), per-product custom badge text and color metabox, renderer hooked into `webgram/product_card/badges` and PDP gallery.
3. QuickView: button on card, modal fetching `wc-ajax=webgram_quick_view` which renders `templates/quick-view/modal.php` (gallery, title, price, rating, short description, variations form, add to cart). Enqueues WC variation scripts inside the modal.
4. Coupons: per-product coupon selector metabox (choose an existing WooCommerce coupon to display), renders "FLAT 20% OFF Use code SAVE20 [Copy Code]" box with clipboard JS and toast; cart offer progress (Phase 3 renders it, module built now): calculates milestones from settings (free shipping threshold from WC shipping zones when possible, plus manual milestones: "15% off at 799", "Buy 3 at 1149" mapped to coupon codes) and returns progress data for cart.

Acceptance: PDP matches reference behavior; sticky releases when right column ends; reorder sections from Customizer and see the change; variation swatch click changes price and enables add to cart; Buy Now lands on checkout; pincode check returns deliverable/not; quick view adds to cart; coupon copy works; badges show on card and PDP; all of it works with a third-party swatch plugin disabled Webgram swatches (setting).

Tests: harness for savings math, badge rules, pincode validation and E.164 already covered; manual PDP checks on desktop/tablet/mobile; PHPCS.

#### Phase 3: Cart, slide cart, checkout, account, login, thank-you

Theme tasks:
1. Slide cart drawer (`template-parts/cart/slide-cart.php`) opened from header cart icon and after AJAX add to cart (setting: open drawer / show toast). Contents from WC fragments; quantity and remove via `wc-ajax=webgram_cart_update` returning fragments; coupon input; subtotal; savings line; hooks for Core offer progress and recommendations (cross-sells, else best sellers); "Place order" button with payment icons and note text from Customizer.
2. Cart page override `cart/cart.php`: two columns (items, sticky summary), coupon, cross-sells row, empty cart state with suggested products.
3. Checkout override `checkout/form-checkout.php`: two columns, sticky order review, coupon inside summary, field classes via `woocommerce_checkout_fields` (do not remove fields), progress steps header (Cart > Details > Payment) purely visual, express checkout buttons area via hook `woocommerce_checkout_before_customer_details` untouched so gateway plugins can inject.
4. Thank-you `checkout/thankyou.php`: order summary card, status timeline, `webgram/thankyou/after_details` hook, continue shopping.
5. My Account: navigation icons, dashboard cards, orders table responsive, `webgram/account/after_order_actions` hook.
6. Login/register split page `myaccount/form-login.php`: left form with Login/Signup tabs (tab remembered), right image from Customizer (`login_image`, `login_image_mobile`), trust logo strip from Customizer repeater; registration adds Full Name (maps to first/last) and Confirm Password with server-side validation on `woocommerce_register_post`; password visibility toggle; social login plugin hooks preserved (`woocommerce_login_form_end`).
7. `cart-checkout.scss` conditional bundle.

Core: Coupons offer progress renders in slide cart via hooks; recommendations provider filter `webgram_core/cart/recommendations` default cross-sells then `best_selling`.

Acceptance: full purchase with Razorpay test mode (or any gateway), COD and a coupon; slide cart updates without reload; checkout works with a third-party checkout field editor plugin active; login page matches reference layout; registration validation errors display inline.

#### Phase 4: Reviews, wishlist, compare

Reviews (Core `Reviews`):
- Storage: WooCommerce review comments; comment meta `_wg_title`, `_wg_media` (array of attachment ids, max 5), `_wg_recommend` (bool), `_wg_helpful` (int), `_wg_helpful_voters` (hashed ids). Media attachments `post_status=private` until comment approved, then `inherit`.
- Submission: replaces the default review form output via `comment_form_defaults` filter only when the theme declares support or setting "Use Webgram review form" is on; multipart upload validated (jpg, png, webp, mp4 up to size setting), EXIF stripped on regenerate, guest reviews follow WooCommerce setting, verified owner flag from WC. Rate limit 3 reviews/hour/IP.
- Summary block: average, count, 5 to 1 distribution with bars, cached transient per product, flushed on comment status change.
- List: sort (newest, highest, lowest, most helpful, with media), filter by stars and "with photos", load more via `wc-ajax=webgram_reviews_load` (page size setting), helpful vote AJAX with nonce and one vote per user/IP, media lightbox, "View all reviews" modal.
- Admin: media column in Comments screen, bulk approve, setting to disable when a third-party review plugin (Judge.me, YITH, CusRev) is active.
- Schema: extend `woocommerce_structured_data_review` with `reviewBody` and image URLs; never duplicate.
- Elementor widget "Webgram Reviews" and shortcode `[webgram_reviews product_id=""]`.
- Templates: `templates/reviews/summary.php`, `list.php`, `item.php`, `form.php`. Theme styles them.

Wishlist and Compare (Core `Wishlist`, `Compare`):
- Storage interface with `UserMetaStorage` and signed `CookieStorage` (HMAC using `Crypto` key, max 50 ids), merge on `wp_login`.
- AJAX toggle endpoints; header icon with count via WC fragments (`woocommerce_add_to_cart_fragments` includes `.wg-wishlist-count`); card action buttons; wishlist page shortcode `[webgram_wishlist]` (table with add to cart, remove, share link via signed token, expiry 30 days); compare page `[webgram_compare]` (attributes table with sticky first column, max 4 products, differences highlight).
- Pages created on activation only through a setup notice with a button, not silently.
- Analytics events `wishlist_add`, `compare_add`.

Acceptance: reviews match the reference block (4.8, bars, verified badge, media thumbnails, sort dropdown, View all); review with a photo submitted as guest is held for moderation and its image is not public until approved; switching to Storefront theme keeps reviews visible in WooCommerce's default place; wishlist survives login as guest then user.

#### Phase 5: Homepage sections, slider, Instagram, Elementor and Gutenberg

Core `Slider`:
- CPT `wg_slider` with slides repeater meta `_wg_slides`: desktop image, tablet image, mobile image, heading, subheading, description, CTA text, CTA link, secondary CTA, text align, vertical align, overlay color and opacity, text color, per-slide animation (fade, slide, zoom). Slider settings: autoplay, delay, loop, navigation, pagination, height mode (auto/fixed/viewport), lazy load, effect.
- Renderer with Swiper (registered once, enqueued by renderer), `<picture>` with per-device sources, `fetchpriority="high"` on the first slide, fixed aspect ratio to avoid CLS.
- Shortcode `[webgram_slider id=""]`, Gutenberg block, Elementor widget with slider picker.

Core `Instagram`:
- Settings: source "Access token" (Instagram Graph API for Business/Creator: fields Instagram Business Account ID, long-lived access token stored encrypted, Test connection button) or "Manual" (repeater: image, link, caption). Note in UI: Instagram Basic Display API was retired by Meta in December 2024; a Business or Creator account connected to a Facebook Page is required for API mode.
- Fetch: `GET https://graph.facebook.com/v{version}/{ig-user-id}/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp` with `wp_remote_get`, timeout 10 s, response validated, cached in `Cache` group `instagram` for 12 hours (setting), token refresh via cron every 30 days (`GET /refresh_access_token` for IG login tokens; for FB login tokens document that they are long-lived 60 days and must be regenerated), errors logged and shown in admin, frontend falls back to manual items or hides.
- Renderer: grid or slider, columns, count, show caption on hover, "Follow us" button, lazy images, no external script.
- Shortcode, block, Elementor widget.

Core `Integrations`:
- Elementor category "Webgram"; widgets: Product Grid, Product Slider, Best Sellers, Trending, Mega Saver, Featured, Categories (shape circle/square/rounded, columns, source), Slider, Reviews, Reels (Phase 6), AI Assistant inline (Phase 6), Coupons row, Trust badges, Testimonials (source: Core `wg_testimonial` CPT with name, age/label, text, rating, photo, product link), Instagram, Banner (theme presentational), Section heading, Benefits row, Blog grid. Each widget calls the same renderer as its shortcode and block. Elementor global colors/fonts synced from tokens via a "Sync tokens to Elementor" button in theme dashboard.
- Gutenberg: `block.json` server-rendered blocks with the same attributes.
- Product query helper `Webgram\Core\Support\ProductQuery` (source: recent, best_selling, trending, on_sale, featured, category, ids, tag; cached by args hash; invalidated on product save/stock change). Trending score: daily cron aggregates `product_view` events from Analytics into `_wg_trend_score`.

Theme: section templates and styles for Trending, Mega Saver (with variation "Pack of 1/2/3" swatches on cards), Best Sellers dark band with big title, Reels row (uses Core), About Us split with benefit cards, Blog grid, Testimonials cards, Instagram grid, Benefits row (icon, title, text), Coupon cards, Promotional banners, category circles/squares row. Demo homepage built in Elementor and again in Gutenberg. Demo content export (WXR + Customizer JSON + Elementor kit) with original placeholder images.

Acceptance: reference homepage reproduced with Webgram widgets only; same page rebuilt with blocks when Elementor is deactivated; Instagram grid renders from a test token and from manual mode; slider images switch by device; LCP element is the first slide with priority hint.

#### Phase 6: Reels, voice search, AI assistant

Core `Reels`:
- CPT `wg_reel`, taxonomy `wg_reel_category`, meta: `_wg_video_source` (upload|external), `_wg_video_id`, `_wg_video_url` (mp4, YouTube Shorts, Vimeo, Cloudflare Stream, Bunny adapters via `webgram_core/reels/sources` filter), `_wg_poster_id` (required), `_wg_products` (ids, max 5), `_wg_cta`.
- Frontend: grid/slider of 9:16 cards with poster and play icon; tap opens full-screen vertical viewer (swipe/keys between reels, mute toggle, product pill sheet with price and Add to Cart via AJAX, View product). Autoplay muted when in viewport (IntersectionObserver), `preload="none"`.
- Events to Analytics: `reel_impression`, `reel_play`, `reel_complete`, `reel_product_click`, `reel_add_to_cart`.
- Shortcode `[webgram_reels category="" count="" layout=""]`, block, Elementor widget. Admin list with poster and products columns; upload size limit setting.

Core `VoiceSearch`:
- `EngineInterface { supported(): bool; script_handle(): string }`; `WebSpeechEngine` (browser `SpeechRecognition`, language from settings default `en-IN`, plus `hi-IN` option), `ServerSttEngine` stub with interface for later.
- Mic button injected into `webgram/search/input` and the assistant input only when the engine reports support (JS feature detection hides it in Firefox). Interim results into the input, submit on final. Setting: auto-submit on result.

Core `AiAssistant`:
- Settings: provider (none/rule_based, openai, gemini, anthropic), API key (secret), model name, system prompt additions, assistant name and avatar, greeting, suggested questions (repeater), visibility (all pages / shop pages / selected), position, colors default from tokens, logged-in only toggle, daily message budget, per-session rate limit (default 10/min), data retention days (default 90), consent text.
- `ProviderInterface { name(); complete(array $messages, array $tools, array $options): CompletionResult; supports_tools(): bool }` with `OpenAiProvider`, `GeminiProvider`, `AnthropicProvider`, `RuleBasedProvider` (keyword product search + coupon answers + FAQ from settings; no API key). Providers registered via filter. All HTTP through `wp_remote_post` with 20 s timeout and response shape validation.
- Tools (function calling): `search_products(query, max_price, min_price, category, sort)`, `best_sellers(limit, category)`, `active_coupons()`, `order_status(order_id)` (logged-in owner only), `store_info()` (shipping, returns, contact from settings). Tools registered via filter. Product results always pass through `wc_get_products` with `status=publish` and catalog visibility.
- Storage: tables `wg_ai_conversations`, `wg_ai_messages` (schema in architecture.md). Session key random 64 hex in cookie `wg_ai_session`, HttpOnly, SameSite=Lax. Retention cron. WordPress privacy exporter/eraser registered for logged-in users.
- REST: `POST /assistant/message` (nonce + rate limit + budget), `GET /assistant/conversation` (session-bound). Returns message text plus structured `products[]` (id, title, price html, image, url, add to cart url) and `suggestions[]`. Never returns raw provider errors; logs them.
- Frontend: floating launcher (3 KB) that loads the chat bundle on first click; window with header (name, avatar, online dot, mute, close), message list with timestamps, product card carousel, suggestion chips, input with mic and send, typing indicator, error state with retry. Elementor widget "Webgram AI Assistant" for inline embedding.
- Analytics events: `chat_open`, `chat_message`, `chat_product_click`, `chat_add_to_cart`.

Acceptance: "Show me wall decor under 2000" returns matching products from the store via the tool; with no API key the rule-based provider still answers product and coupon questions; mic works in Chrome and is hidden in Firefox; reel viewer adds a product to the cart; disabling any of the three modules removes their assets from the page.

#### Phase 7: Invoice, emails, notifications, analytics

Core `Invoice`:
- Numbering: table `wg_invoice_sequence` (schema in architecture.md), format setting with placeholders `{prefix}{number}{suffix}`, `{yyyy}`, `{yy}`, `{mm}`, `{fy}` (Indian financial year e.g. 2026-27), padding, starting number, reset yearly option. Assigned on `woocommerce_payment_complete` or `woocommerce_order_status_processing` (setting), inside a transaction, never regenerated once assigned; manual "Generate" action for admins on unpaid orders.
- Data builder `InvoiceData::from_order( WC_Order )` normalized array: store (logo attachment, name, address, phone, email, website, GSTIN, PAN, CIN optional, footer note, support line), customer (billing, shipping), order (number, date, payment method, transaction id from `get_transaction_id()`, payment status), items (image, name, SKU, variation attributes, HSN from product meta `_wg_hsn` if set, qty, unit price, discount, tax lines split by rate with CGST/SGST/IGST labels when WC tax names contain them, line total), totals (subtotal, discount, shipping, taxes, fees, total, refund lines), notes (customer note), disclaimer text "This is a computer generated invoice and does not require a signature" (setting).
- Template `templates/invoice/invoice.php`: clean corporate layout, table-based for dompdf, A4, restricted CSS subset, no marketing elements, no ribbons, no badges. Fonts: DejaVu Sans (dompdf bundled) with rupee symbol support.
- PDF: `PdfGeneratorInterface` with `DompdfGenerator` (dompdf via Composer in `vendor/`, pinned). Files saved to `uploads/webgram-invoices/{yyyy}/{mm}/invoice-{number}.pdf` with `index.html` and `.htaccess` deny; served only via REST `GET /invoice/{order_id}` after ownership or `manage_woocommerce` check. "Regenerate" admin action.
- Admin: order row action, order edit metabox (number, date, download, regenerate), bulk action "Download invoices" (zip). Customer: My Account orders action button via `webgram/account/after_order_actions` plus WooCommerce `woocommerce_my_account_my_orders_actions` filter for other themes. Thank-you page hook.
- Settings tab with all store fields, format, trigger, HSN toggle, disclaimer, layout options (show images, show SKU, show HSN, show shipping address).

Core `Emails`:
- Branding settings: logo, header background, header text color, body background, text color, link color, button color and radius, footer text, social links, layout width, font family (email-safe stack).
- Implementation: filter `woocommerce_locate_template` for `emails/email-header.php`, `email-footer.php`, `email-styles.php` only when "Use Webgram email templates" is on; template order button and product table styled via `woocommerce_email_styles` filter; all WooCommerce email hooks (`woocommerce_email_header`, `woocommerce_email_footer`, `woocommerce_email_order_details` and so on) preserved so gateway and shipping plugins keep injecting content.
- Preview: admin page with sample order rendering any WC email type; test send to admin via `wp_mail`.
- Invoice attachment: setting listing WC email ids (customer_completed_order, customer_invoice, customer_processing_order); attaches via `woocommerce_email_attachments` calling `Invoice` module to fetch or generate. No invoice code duplicated here.

Core `Notifications` (Email + WhatsApp):
- Structure exactly: `Module.php`, `Channels/ChannelInterface.php`, `EmailChannel.php`, `WhatsAppCloudChannel.php`, `WhatsAppBspChannel.php` (interface + config stub, no provider implemented in v1), `SmsChannel.php` (interface + stub), `Events.php`, `Templates.php`, `OptIn.php`, `Queue.php`, `Log.php`, `Settings.php`, `PhoneNumber.php`, `Rest/WhatsAppController.php` (test connection, template sync), `Admin/LogPage.php`.
- Events (each independently on/off per channel): `order_placed` (`woocommerce_new_order` + `woocommerce_checkout_order_processed`), `payment_successful` (`woocommerce_payment_complete`), `processing`, `shipped` (custom status `wc-shipped` registered by this module when enabled, plus mapping to third-party shipment statuses through filter `webgram_core/notifications/shipped_statuses`), `out_for_delivery` (custom status optional), `completed` as delivered, `cancelled`, `failed`, `refunded`. Admin can also trigger "Resend" per order.
- EmailChannel: does not send its own emails for events WooCommerce already covers; it exposes the on/off matrix by enabling/disabling the corresponding WooCommerce email via `woocommerce_email_enabled_{id}` filter, and sends a lightweight `wp_mail` only for events WooCommerce has no email for (shipped, out for delivery) using the Emails module branding. Never builds SMTP.
- WhatsAppCloudChannel: settings Phone Number ID, WABA ID, Access Token (secret), Graph API version (default `v21.0`, editable), Test connection (GET `/{phone-number-id}` with Bearer token, shows display number and quality rating), status badge CONNECTED / NOT CONFIGURED / ERROR with last error message (token redacted). Send: `POST /{phone-number-id}/messages` with `type: template`, template name, language code, components with body parameters in declared order; headers with document (invoice PDF link via signed temporary URL, optional) supported. Response `messages[0].id` stored as provider message id. Webhook endpoint `POST /webgram/v1/whatsapp/webhook` (verify token setting, HMAC `X-Hub-Signature-256` check with app secret setting) to record delivered/read/failed statuses into the log.
- Templates.php: per event, a mapping row: Meta template name, language, ordered parameter list chosen from variables `{customer_name}, {order_number}, {order_total}, {currency}, {payment_method}, {order_status}, {billing_address}, {shipping_address}, {tracking_number}, {tracking_url}, {carrier}, {store_name}, {invoice_number}, {invoice_url}, {order_url}, {items_summary}, {eta}`. "Sync templates from Meta" button lists approved templates (GET `/{waba-id}/message_templates`) so the owner picks from a dropdown; shows approval status. Provide a documentation page with recommended template texts to submit to Meta for each event.
- OptIn.php: checkout checkbox (label from settings, default unchecked or checked per setting, only shown when WhatsApp channel is connected), saves order meta `_wg_whatsapp_optin` (yes/no) and timestamp, user meta for logged-in users, My Account preference toggle; admin can see consent on order. No WhatsApp send without consent. Consent is per event type "transactional" only; marketing is out of scope.
- PhoneNumber.php: uses `Helpers::to_e164` with billing country calling code from WooCommerce, fallback to General setting default country. Invalid numbers are logged as `skipped_invalid_phone`.
- Queue.php: every send is an Action Scheduler job (`as_enqueue_async_action( 'webgram_core_notification_send', [ $log_id ] )`), idempotency key `{order_id}:{event}:{channel}` unique in log table; duplicate event fire is a no-op. Retries: 3 with backoff 1, 5, 30 minutes on network/5xx; no retry on 4xx template or recipient errors. Order creation never waits on a send; failures never throw into WooCommerce hooks.
- Log.php: table `wg_notification_log` (id, order_id, event, channel, recipient_hash sha1, recipient_masked, status enum queued|sent|delivered|read|failed|skipped, provider_message_id, attempts, error_code, error_message (redacted), payload_summary JSON without personal data, created_at, updated_at; UNIQUE(order_id, event, channel); indexes on status, created_at). Retention setting. Admin log page with filters and "Retry" and "Resend" actions; order edit metabox with per-event status.
- Settings copy must not state any per-message price. Text: "Message charges, if any, are billed by Meta to your WhatsApp Business account according to Meta's pricing."
- ContactSeller (Phase 2) remains untouched and separate.

Core `Analytics`:
- Table `wg_events` (architecture.md). Collector REST `POST /events` accepting batched events with nonce and sampling setting; server-side events via `webgram_core/event` action. Daily cron aggregates trend scores and purges by retention. Admin dashboard: cards (assistant conversations, reel plays, wishlist adds, notification delivery rate) and simple tables for last 7/30 days, using `chart.js` from WordPress-bundled assets not allowed; use inline SVG bars to avoid a dependency. Privacy: no IPs stored, session hashed, export/erase hooks.

Acceptance: invoice PDF for a paid order downloads from admin and My Account, numbering increments without gaps across two simultaneous payments (simulate with two CLI runs), email preview renders every WC email with branding and third-party gateway text still appears; WhatsApp: Test connection succeeds with valid credentials and fails gracefully with invalid, an order with consent produces a queued then sent log entry with provider message id, an order without consent produces `skipped_no_consent`, Meta API unreachable produces retries and the order still completes; webhook updates status to delivered.

#### Phase 8: Hardening and ThemeForest preparation

- PHPCS zero errors (WordPress-Extra, WordPress-Docs light, WooCommerce), Theme Check and Envato Theme Check clean, WooCommerce template versions current, PHP 8.3 deprecation scan.
- Security review against section 3.2 with a written checklist per module; dependency audit (dompdf, Swiper pinned).
- Performance: Lighthouse on demo home, shop, product, cart, checkout on throttled mobile; targets LCP < 2.5 s, CLS < 0.05, INP < 200 ms; fix regressions.
- RTL build, `wpml-config.xml`, POT files for both products, accessibility pass with keyboard-only run through the purchase flow.
- Compatibility matrix tested and documented in `docs/compatibility.md`: at least one plugin per category from section 3.1 (Razorpay/Stripe, Shiprocket or similar, Elementor, Rank Math, WP Rocket or LiteSpeed, a variation swatches plugin, a review plugin, a membership plugin, Contact Form 7 or WPForms, a GA plugin, WooCommerce Subscriptions or a common extension).
- Packaging: theme zip via `git archive` (src excluded), Core zip bundled inside theme at `webgram-theme/plugins/webgram-core.zip` with dashboard installer (own implementation, not TGMPA) and "update bundled Core" flow, child theme zip, demo import (WXR, Customizer JSON, Elementor kit, widget JSON), buyer documentation HTML in `docs/`, developer hooks reference generated by script, licensing text (GPL code; original images CC0 or self-made), screenshots and preview images.
- Staging deployment guide for Hostinger KVM VPS with Nginx, PHP-FPM, Redis object cache, and the nginx snippet protecting `uploads/webgram-invoices/`.

---

### A.7. Data model reference

Custom tables (all via Repository classes, dbDelta, prefix `wg_`): `wg_invoice_sequence`, `wg_ai_conversations`, `wg_ai_messages`, `wg_events`, `wg_pincodes`, `wg_notification_log`. Full column definitions: `docs/architecture.md` section 6 plus `wg_notification_log` in Phase 7 above.

CPTs: `wg_slider`, `wg_reel` (+ taxonomy `wg_reel_category`), `wg_inquiry` (private), `wg_testimonial`.

Meta and options: see the module specs. Options are `webgram_core_settings_{module}`; only `general` autoloads.

---

### A.8. REST and AJAX reference

WC-AJAX (`wc-ajax=webgram_{action}`, nonce `webgram_core_nonce` or theme `webgram_nonce`): `live_search` (theme), `cart_update` (theme), `quick_view`, `wishlist_toggle`, `compare_toggle`, `reviews_load`, `review_helpful`, `pincode_check`, `bulk_inquiry`, `coupon_progress`, `reel_add_to_cart`.

REST `webgram/v1`: `system/status` (admin), `assistant/message` (POST), `assistant/conversation` (GET), `search/products` (GET), `track-order` (POST), `events` (POST), `reviews/{product_id}` (GET), `reviews` (POST multipart), `invoice/{order_id}` (GET), `whatsapp/test` (POST admin), `whatsapp/templates` (GET admin), `whatsapp/webhook` (GET verify, POST events), `instagram/test` (POST admin).

Every route declares `permission_callback`; public write routes add nonce and rate limit middleware from `Rest/Middleware/`.

---

### A.9. Settings ownership

| Concern | Owner |
|---------|-------|
| Colors, fonts, spacing, radius, container, buttons | Customizer > Webgram > Colors / Typography / Layout |
| Header, footer, mega menu, sticky, mobile menu | Customizer > Webgram > Header / Footer; Appearance > Menus for mega menu fields |
| Shop archive, product card style, category shape, PDP sections and gallery, cart drawer, checkout layout, login image, blog, 404 | Customizer > Webgram > Shop / Product / Cart and Checkout / Account / Blog |
| Module on/off | Webgram > Modules |
| Feature options and API credentials | Webgram > Settings > {module tab} |
| Per-product: coupon, badge, video, specs, HSN, contact override | Product edit screen metaboxes (Core) |
| Per-page layout and section content | Page metabox (theme) and Elementor/Gutenberg |

---

### A.10. Copy and UX writing rules

Sentence case, plain verbs, active voice, no filler. Buttons say what happens: "Add to cart", "Buy now", "Copy code", "Check delivery", "Save changes", "Test connection". Errors say what went wrong and what to do. Empty states invite action. No marketing slogans in transactional surfaces (invoices, emails, notifications). No em dashes.

---

### A.11. Phase report template (`docs/phases/phase-N-name.md`)

```
# Phase N: Name (version)
### A.Implemented
### A.Files and modules changed
### A.Database and API changes
### A.Tests performed (exact commands and manual steps)
### A.Errors found and fixed
### A.Compatibility concerns
### A.Not tested
### A.Known limitations
### A.Ready for next phase
```

---

### A.12. Claude Code prompts per phase

Use these verbatim as the first message of a session, after opening the repo root.

Phase 1:
"Read CLAUDE.md, docs/BUILD-SPEC.md section 6 Phase 1, docs/architecture.md sections 4 and 10, and docs/phases/phase-0-foundation.md. Inspect webgram-theme/inc, template-parts and assets/src. Then implement Phase 1 task by task. Ask me only if an architectural ambiguity blocks you. Run php -l, the harness tests and npm run build before reporting. Finish by writing docs/phases/phase-1-header-footer.md."

Phase 2:
"Read CLAUDE.md, docs/BUILD-SPEC.md Phase 2, docs/architecture.md section 5, and the Phase 1 report. Inspect webgram-theme/inc/woocommerce, template-parts/cards, and webgram-core/src/Modules/{WooEnhancements,Badges,QuickView,Coupons}. Implement Phase 2. Keep WooCommerce template overrides within the allowed list. Report as specified."

Phase 3 through 8: same shape, naming the phase, the modules involved, and the previous report.

Bug fixing prompt:
"Do not rewrite unrelated code. Read the full error, find the root cause in the relevant files, apply the smallest safe fix, rerun the failing check, and tell me what changed."

---

### A.13. Open decisions carried forward

1. Header builder drag-and-drop in Phase 1 (default) unless the owner asks for presets-first.
2. Core standalone sale and license server: not in v1; bundled only.
3. Pincode: CSV import in v1, courier API adapters through the interface later.
4. AI demo provider: rule-based for the ThemeForest preview unless a key is supplied.
5. Reels: upload plus external URL adapters (YouTube Shorts, Vimeo, Cloudflare Stream, Bunny) in v1.
6. Specifications: WooCommerce attributes plus optional key/value repeater, one table.
7. Demo brand name and niche: to be chosen before Phase 5; must be original.
