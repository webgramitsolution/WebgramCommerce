# Build progress

Resume instructions: read CLAUDE.md, this file, then the current phase in docs/BUILD-SPEC.md section 7 and Appendix A. Continue from "Next task" without redoing completed work. Repository: webgramitsolution/WebgramCommerce, branch claude/webgram-theme-core-build (PR #1).

## Current phase

Phase 4: reviews (Core Reviews module), wishlist, compare.

## Completed

- Phase 0 foundation (main branch).
- Phase 1: Theme Settings panel, header and footer builders, mega menu, mobile menu, Core site_tools and pincode/location picker. Report: docs/phases/phase-1-settings-header.md.
- Phase 2: product card, shop archive, product detail page, Core WooEnhancements (Buy Now, recently viewed, specifications, video, contact seller, bulk inquiry, track order, product panel), Badges, Quick View, Coupons. Report: docs/phases/phase-2-shop-product.md.
- Phase 3: slide cart drawer with savings line, offer progress and Core recommendations, cart page, checkout with summary coupon and steps, thank you timeline, My Account icons and cards, split login/register with server validation, help page with Core FAQs and contact cards. Report: docs/phases/phase-3-cart-checkout-account.md.

## Next task

Phase 4, task 1: Core `Reviews` module data layer (comment meta `_wg_title`, `_wg_media`, `_wg_recommend`, `_wg_helpful`, `_wg_helpful_voters`, private media until approval), settings tab, then form, summary, list, AJAX endpoints, admin column, schema, shortcode; then Wishlist and Compare storage (`UserMetaStorage`, signed `CookieStorage`), AJAX toggles, header counts, card buttons, pages and shortcodes.