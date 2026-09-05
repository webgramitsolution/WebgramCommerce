=== Webgram Core ===
Contributors: webgramitsolution
Tags: woocommerce, reviews, wishlist, invoice, whatsapp, slider, reels, ai
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 0.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Functionality layer for the Webgram WooCommerce ecosystem.

== Description ==

Webgram Core adds modular ecommerce features to WooCommerce: advanced reviews, wishlist, compare, quick view, product badges, coupon UI, sliders, shoppable reels, Instagram feed, voice search, an AI shopping assistant, PDF invoices, branded emails, WhatsApp order notifications and analytics. Each module can be switched off independently and loads no assets when disabled.

The plugin works with any theme and is designed for Webgram Theme.

== Changelog ==

= 0.8.0 =
* Analytics module: first party events table, batched REST collector with sampling, server side events, trending views, retention, privacy export and erase, dashboard with inline SVG bars.
* Invoice module: gap free numbering with financial year formats, normalized order data with CGST, SGST and IGST labels, A4 template rendered by dompdf (Composer) with an HTML fallback, protected storage, REST download with ownership check, admin actions, bulk zip, My Account and thank you buttons, HSN field.
* Emails module: branded WooCommerce header, footer and styles through template hooks, preview and test send page, invoice attachments.
* Notifications module: order events with Shipped and Out for delivery statuses, email and WhatsApp Cloud API channels, consent at checkout and My Account, template mapping with Meta sync, background queue with retries, delivery log with admin page, webhook for delivery states.

= 0.7.0 =
* Reels module: wg_reel post type with categories, upload or external sources (MP4, YouTube Shorts, Vimeo, Cloudflare Stream, Bunny), 9:16 cards with muted autoplay and product mini card, full screen viewer with product sheet and add to cart, analytics events, shortcode, widget and block.
* Voice Search module: microphone button in the theme search and the assistant using the browser Web Speech API, language and auto submit settings, server engine interface for later.
* AI Shopping Assistant module: rule based provider without API key, Anthropic, OpenAI and Gemini providers with tool calling (search products, best sellers, coupons, order status, store info), conversation tables with retention and privacy export and erase, nonce protected REST with rate limit and daily budget, launcher that loads the chat on first click, inline widget, block and shortcode.

= 0.6.0 =
* Slider module: wg_slider post type with per-device images, overlays, CTAs, benefit rows and animations, Swiper renderer with picture sources and priority hint on the first slide, shortcode, widget and block.
* Instagram module: Graph API mode with encrypted token, cached fetch, monthly refresh and test connection, manual gallery fallback, grid or slider renderer.
* Integrations module: one registry of section definitions rendered as Elementor widgets (Webgram category), server-rendered Gutenberg blocks and shortcodes; product grid, slider, trending, best sellers band, mega saver, featured, categories, coupons row, trust badges, testimonials (new wg_testimonial post type) and single product layout widgets.
* ProductQuery helper with cached sources (recent, best selling, trending, on sale, featured, top rated, category, tag, ids) and a daily trending score.

= 0.5.0 =
* Advanced Reviews: rating summary with distribution bars, sort and star filters, load more, photo and video uploads held private until approval, helpful votes, recommendation, review title, admin column, JSON-LD extension, [webgram_reviews] shortcode, stand down when Judge.me, YITH or CusRev is active.
* Wishlist: user meta or signed guest cookie with merge on login, AJAX toggle, header icon count, card and product page buttons, [webgram_wishlist] page with expiring share links.
* Compare: up to 4 products, floating compare bar, [webgram_compare] page with sticky first column and difference highlighting.
* Page setup notices create the wishlist and compare pages on request (never silently).

= 0.4.0 =
* Cart recommendations for the theme slide cart (cross-sells first, then best sellers, cart items excluded) with hook webgram_core/cart/recommendations.
* Help page FAQs (Site Tools > Help page) and contact cards supplied to the theme help page template through webgram/help/faqs and webgram/help/contacts.
* Contact email setting added to WooCommerce Enhancements contact settings.

= 0.3.0 =
* WooCommerce Enhancements completed: Buy Now, recently viewed, specifications table with per-product rows, product video, contact seller cards, bulk inquiry (wg_inquiry) with admin list and email, track order shortcode and REST endpoint, product page delivery check, Webgram product data tab.
* Badges module: rule engine (new, sale percent, best seller, low stock, sold out) plus custom badge per product.
* Quick View module: modal with gallery, price, variations and add to cart.
* Coupons module: product coupon box with copy to clipboard, cart offer progress with milestones and free shipping threshold.

= 0.2.0 =
* Site Tools module: HTML Blocks, Layouts with assignment conditions, promo popup, cookie notice, age verification, maintenance and coming soon modes, white label, custom JS, optional portfolio post type.
* WooCommerce Enhancements: pincode delivery check, delivery location picker with offline pincode table (CSV import) and optional OpenStreetMap reverse geocoding.
* Core admin pages attach under the theme's Webgram menu when the theme provides the settings panel.

= 0.1.0 =
* Foundation release: module framework, settings framework, encrypted credential storage, REST router, admin screens.
