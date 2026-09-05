# Build progress

Resume instructions: read CLAUDE.md, this file, then the current phase in docs/BUILD-SPEC.md section 7 and Appendix A. Continue from "Next task" without redoing completed work. Repository: webgramitsolution/WebgramCommerce, branch claude/webgram-theme-core-build (PR #1).

## Current phase

Phase 6: reels, voice search, AI assistant.

## Completed

- Phase 0 foundation (main branch).
- Phase 1: Theme Settings panel, header and footer builders, mega menu, mobile menu, Core site_tools and pincode/location picker. Report: docs/phases/phase-1-settings-header.md.
- Phase 2: product card, shop archive, product detail page, Core WooEnhancements (Buy Now, recently viewed, specifications, video, contact seller, bulk inquiry, track order, product panel), Badges, Quick View, Coupons. Report: docs/phases/phase-2-shop-product.md.
- Phase 3: slide cart drawer with savings line, offer progress and Core recommendations, cart page, checkout with summary coupon and steps, thank you timeline, My Account icons and cards, split login/register with server validation, help page with Core FAQs and contact cards. Report: docs/phases/phase-3-cart-checkout-account.md.
- Phase 4: Core Advanced Reviews (summary, sort and filters, load more, media uploads, helpful votes, admin column, schema, shortcode, third-party stand down), Wishlist and Compare (signed cookie or user meta storage, merge on login, header icons, card and product buttons, pages with share link and difference highlight, compare bar, page setup notices). Report: docs/phases/phase-4-reviews-wishlist-compare.md.
- Phase 5: ProductQuery and trending score, Slider module (wg_slider, Swiper renderer), Instagram module (Graph API and manual), Integrations registry with Elementor widgets, Gutenberg blocks and shortcodes for all sections, testimonials CPT, product layout widgets, theme presentational sections, carousel module, Sync tokens to Elementor, demo homepage content. Report: docs/phases/phase-5-homepage-integrations.md.

## Next task

Phase 6, task 1: Core `Reels` module (post type `wg_reel`, taxonomy `wg_reel_category`, meta `_wg_video_source`, `_wg_video_id`, `_wg_video_url`, `_wg_poster_id`, `_wg_products`, `_wg_cta`, source adapters filter `webgram_core/reels/sources`, admin columns), then the 9:16 card row and full screen viewer with product sheet, analytics events, shortcode and registry definition; then VoiceSearch (engine interface, Web Speech engine, mic in `webgram/search/input`); then AiAssistant (settings, providers, tools, tables `wg_ai_conversations` and `wg_ai_messages`, REST, launcher and chat window).