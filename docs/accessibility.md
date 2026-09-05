# Accessibility notes

Target: WCAG 2.1 AA for the purchase flow. What the code does today, and what still needs a manual run with a keyboard and a screen reader.

## Implemented

- Skip link to `#primary` as the first focusable element in `header.php`, visible on focus.
- `:focus-visible` outlines using the accent token on links, buttons, inputs, menu items and cards (`base/_reset.scss`, `components/*`).
- `prefers-reduced-motion`: marquee, sliders, hover slideshow, reels autoplay and section animations stop or shorten (`_marquee.scss`, `_sections.scss`, `_product.scss`, Core `slider.js`, `reels.js`).
- Icon-only buttons carry `aria-label` (header icons, quantity, wishlist, compare, quick view, cart drawer close, mic, assistant launcher). Decorative SVGs are `aria-hidden="true"` through `webgram_icon()`.
- Theme drawers (cart drawer, mobile menu, search, filters, off-canvas sidebar): `role="dialog"` with `aria-modal`, focus moved to the first control on open and restored on close, Escape closes, Tab cycles inside the drawer (`assets/src/js/modules/drawer.js`). Core quick view, reels viewer, assistant window and popups do the same through `WebgramCore.trapFocus()`.
- Desktop navigation: `aria-expanded` on parent links, Enter, Space and ArrowDown open, Escape closes, arrows move between items (`assets/src/js/modules/menu.js`). Mobile drawer tabs use `role="tablist"` with `aria-selected` and `aria-controls`.
- Sliders: Swiper a11y module enabled with localized labels, dots are buttons, slides carry `aria-roledescription="slide"`.
- Product gallery thumbnails are a `tablist` with labelled `tab` buttons; product page sections use WooCommerce's own tabs markup.
- Live regions (`aria-live="polite"`): marquee strip, assistant message list, review list, track order result, bulk inquiry result, location modal and pincode checker result.
- Forms: WooCommerce form fields keep their labels; the split login and register page shows server side errors inline under the form; Core forms (reviews, bulk inquiry, track order) label every field and render errors in the live region.
- Color: the default palette (text #1f2937 on white, white on the #a0181f primary and the navy secondary) meets 4.5:1 for body text. There is no contrast warning in the Styles tab; a store owner choosing a light primary must check contrast themselves.
- Reels and product videos autoplay muted only; sound needs a tap.
- RTL: logical properties everywhere and a generated `*-rtl.css` for each stylesheet.

## Done in the final audit pass

- Tab cycling inside the Core quick view, reels viewer, assistant window and popups through a shared `WebgramCore.trapFocus()` helper; popups restore focus to the opener and set `aria-modal`.
- Core form messages (track order, bulk inquiry, review uploads) are linked to their inputs with `aria-describedby` and announced through `role="status"` or `role="alert"`.
- The cart drawer, filters drawer and off-canvas sidebar carry `role="dialog"`, `aria-modal` and a label.
- Footer accordions on mobile expose `aria-expanded`.

## Not tested in this phase

- Keyboard only purchase: home to product to cart drawer to checkout to thank you. Needs a local WordPress run.
- Screen reader pass (NVDA and VoiceOver) on the header, product page and checkout.
- Automated audit (axe or Lighthouse accessibility) on the demo pages.
- Contrast of user chosen palettes beyond the default tokens.

Record findings in the Phase 8 report under "Not tested" until the runs are done.
