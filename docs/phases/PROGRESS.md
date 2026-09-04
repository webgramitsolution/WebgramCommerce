# Build progress

Resume instructions: read CLAUDE.md, this file, then the current phase in docs/BUILD-SPEC.md section 7 and Appendix A. Continue from "Next task" without redoing completed work.

## Current phase

Phase 2: product card, shop archive, product detail page; Core woo_enhancements (rest), badges, quick_view, coupons.

## Completed

- Phase 0 foundation imported and verified (commit 1f1625c).
- Phase 1 complete: Theme Settings panel, migration, Import/Export, header builder with all elements and presets, marquee, mega menu, mobile drawer, bottom navbar, sticky header, live search and overlay, footer builder, floating social sidebar, back to top, header banner, page title band; Core site_tools (HTML Blocks, Layouts, promo popup, cookie notice, age verify, maintenance, white label, custom JS, portfolio) and woo_enhancements pincode/location picker. Report: docs/phases/phase-1-settings-header.md.

## Next task

Phase 2, task 1: product card 4.2 (wave badge SVG mask, hover slideshow, rating pill on price line, variation chips, cart icon + BUY NOW) in `inc/woocommerce/class-wc-product-card.php`, `template-parts/cards/product-card*.php`, `assets/src/scss/components/_card.scss`, `assets/src/js/modules/product-card.js`.
