=== Webgram Core ===
Contributors: webgramitsolution
Tags: woocommerce, reviews, wishlist, invoice, whatsapp, slider, reels, ai
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Functionality layer for the Webgram WooCommerce ecosystem.

== Description ==

Webgram Core adds modular ecommerce features to WooCommerce: advanced reviews, wishlist, compare, quick view, product badges, coupon UI, sliders, shoppable reels, Instagram feed, voice search, an AI shopping assistant, PDF invoices, branded emails, WhatsApp order notifications and analytics. Each module can be switched off independently and loads no assets when disabled.

The plugin works with any theme and is designed for Webgram Theme.

== Changelog ==

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
