# Phase 4: Reviews, wishlist, compare (0.5.0)

### A. Implemented

Webgram Core
- Advanced Reviews (`Reviews` module, WooCommerce review comments plus comment meta, no custom table): summary panel (spec 4.7: 48px average, five stars, "out of 5", "(N Reviews)", five rows with pill bars and counts that double as star filters, dark "Write a review" button), "Customer Reviews" heading with "Showing 1-4 of N reviews", sort select (Newest, Oldest, Highest, Lowest, Most helpful, With photos), filter chips (All, 5 to 1, With media), review items (48px avatar, bold name, "Verified Buyer" pill from WooCommerce's verified flag, stars plus numeric rating, bold title, body, recommendation line, 64px media thumbnails with a lightbox that supports video and keyboard navigation, relative date, "Was this helpful?" thumbs-up vote with count), "View All Reviews" button that loads the next pages inline through `wc-ajax=webgram_reviews_load`, review form (star radio input, title, body, multi-file upload with previews and client checks, recommend yes/no, consent checkbox, guest name and email) posting through the standard `wp-comments-post.php` flow so WooCommerce's rating validation and verified owner logic stay in charge.
- Submission handling: `preprocess_comment` validates (rate limit 3 per hour per IP hash by default, title length, consent, file count, size and mime through `wp_check_filetype_and_ext`) and rejects before insert; `comment_post` stores `_wg_title`, `_wg_recommend`, `_wg_helpful` and uploads media as attachments (`post_parent` product, meta `_wg_review_comment_id`, `post_status` private until the review is approved, then `inherit`; kept in step on `transition_comment_status`, deleted with the comment), JPEG and WEBP originals re-saved through the image editor to drop EXIF.
- Helpful votes: `wc-ajax=webgram_reviews_helpful`, one vote per user or IP hash stored in `_wg_helpful_voters`, count in `_wg_helpful`, optional guest voting, rate limited.
- Query builder: sorts map to `WP_Comment_Query` meta clauses (rating, helpful with EXISTS or NOT EXISTS so unvoted reviews stay, media first), star and media filters, page and per page from settings, `webgram_core/reviews/query_args` filter. Summary numbers come from WooCommerce's own cached rating counts (no second cache to keep in sync).
- Any theme: when the theme does not fire `webgram/product/reviews`, Core enhances WooCommerce's default `single-product-reviews.php` in place (summary appended through `woocommerce_reviews_title`, title before the text, recommendation, media and helpful vote after it, extra form fields through `woocommerce_product_review_comment_form_args`). Reviews stay visible on Storefront and any other theme because they remain WooCommerce comments.
- Admin: "Review extras" column on the Comments screen (title, recommendation, votes, media thumbnails including private ones), settings tab (use Webgram form, per page, default sort, show summary, filters, sort, helpful, guest votes, recommend, media, max files, max size, allow video, consent, rate limit, stand down with third-party plugins). Third-party detection (Judge.me, YITH Advanced Reviews, Customer Reviews for WooCommerce) makes the module stand down with an admin notice unless the setting is turned off.
- Schema: `woocommerce_structured_data_review` gains `name` (title), `reviewBody` and `image` URLs only when absent. Shortcode `[webgram_reviews product_id=""]`; Elementor widget definition registered through `webgram_core/elementor/widgets` for the Phase 5 Integrations module.
- Wishlist (`Wishlist` module): storage interface with `UserMetaStorage` (`_wg_wishlist`) and `CookieStorage` (`wg_wishlist`, HMAC signed with `Crypto::sign()`, tampered cookies read as empty, max 50 ids), merge on `wp_login` then cookie cleared, `wc-ajax=webgram_wishlist_toggle`, header element `wishlist` for the theme builder with label, show label and count badge options, `.wg-wishlist-count` fragment, `webgram/header/link_url` for the theme bottom navbar, card button on `webgram/product_card/actions`, product page button (next to Add to cart, gallery corner in the Webgram theme, or hidden), `[webgram_wishlist]` page (count, share button copying an expiring signed link that carries the ids so guests can share too, table with image, name, price, stock, add to cart or view, remove; shared view read-only; invalid or expired token notice; empty state), optional "require login", analytics event `wishlist_add` through `webgram_core/analytics/event`.
- Compare (`Compare` module): same storage with max 4, `wc-ajax=webgram_compare_toggle` with "full" response, header element `compare`, `.wg-compare-count` fragment, card and product page buttons, floating compare bar (thumbnails with remove, "N of 4 selected", Compare link; also refreshed as a fragment), `[webgram_compare]` page with sticky first column, product header cells with remove, rows for price, rating, availability, SKU, short description, weight and dimensions, union of visible product attributes, add to cart row, "Highlight differences" toggle (rows whose normalized values differ get `is-different`), row visibility settings, `webgram_core/compare/rows` filter, analytics event `compare_add`.
- Page setup: `Admin\PageSetup` shows one notice per missing page with a "Create page" button (nonce, `manage_options`) that inserts the page with the shortcode and stores the id in the module settings. Pages are never created silently. `Settings::set()` used for the id.
- `Crypto::sign()` added (HMAC over the encryption key material, auth salt fallback). Fallback assets `assets/js/lists.js`, `assets/js/reviews.js`, `assets/css/lists.css`, `assets/css/reviews.css` load only where a button, header icon, block or page renders.

Webgram Theme
- Product page reviews section now always prints the `#reviews-anchor` wrapper (rating pill target) and hands the inside to Core when the Reviews module is active, otherwise WooCommerce's comments template.
- Styles for the reviews block, star rows, chips, review items, lightbox, form fields, star input, wishlist and compare buttons (card action, product page outline button, gallery corner), wishlist table (responsive card layout under 768px), compare table with sticky first column and difference highlight, floating compare bar (offset above the mobile bottom navbar). Colors and sizes from `--wg-*` tokens only.

### B. Files and modules changed

Core: `webgram-core.php`, `readme.txt`, `src/Plugin.php`, `src/Support/Crypto.php`, `src/Support/Lists/{StorageInterface,UserMetaStorage,CookieStorage,ProductList,ShareToken,ListModuleTrait}.php` (new), `src/Admin/PageSetup.php` (new), `src/Modules/Reviews/{Module,Summary,Query,Media,Submission,Compat,Schema,AdminColumn}.php`, `src/Modules/Wishlist/Module.php`, `src/Modules/Compare/{Module,Table}.php`, `templates/reviews/{block,summary,stars,list,item,item-extras,form,form-fields}.php`, `templates/wishlist/{button,table}.php`, `templates/compare/{button,bar,table}.php`, `assets/js/{lists,reviews}.js`, `assets/css/{lists,reviews}.css`.
Theme: `style.css`, `functions.php`, `package.json`, `inc/woocommerce/class-wc-product.php`, `assets/src/scss/components/_core.scss`, compiled `assets/css/main.css`.
Tests: `tests/core-harness.php` (94 checks), `tests/theme-harness.php` (55 checks).

### C. Database and API changes

- Comment meta: `_wg_title`, `_wg_media` (attachment ids), `_wg_recommend`, `_wg_helpful`, `_wg_helpful_voters`. Attachment meta `_wg_review_comment_id`. User meta `_wg_wishlist`, `_wg_compare`. Cookies `wg_wishlist`, `wg_compare` (signed). Module settings `webgram_core_settings_reviews`, `_wishlist`, `_compare` (page ids stored there).
- WC-AJAX: `webgram_reviews_load`, `webgram_reviews_helpful`, `webgram_wishlist_toggle`, `webgram_compare_toggle`. Admin post: `webgram_core_create_page`.
- Hooks: `webgram_core/reviews/submitted`, `/summary`, `/item`, `/query_args`, `/after_block`, `webgram_core/wishlist/page_url`, `/after_table`, `webgram_core/compare/page_url`, `/rows`, `/after_table`, `webgram_core/page_setup/pages`, `/created`, `webgram_core/analytics/event`, `webgram_core/elementor/widgets`. Theme hooks consumed: `webgram/product/reviews`, `webgram/product_card/actions`, `webgram/product/gallery_actions`, `webgram/header/elements`, `webgram/header/link_url`.
- No new tables. WooCommerce template overrides unchanged (8 of 12).

### D. Tests performed

- `php -l` on every changed PHP file in theme and Core: 0 errors.
- `php tests/core-harness.php`: 94 checks pass (adds product list add, dedupe, toggle, cap and merge; signed cookie round trip, tamper and foreign key rejection; share token expiry and tamper; summary averages, percents and showing labels; query parameter normalization and argument building for star filter, rating sort, helpful sort and plain newest; media validation for extension, size, mime and no file; third-party detection; schema merge without overwrite; compare difference and attribute union; Phase 4 modules implemented and blocked only by WooCommerce).
- `php tests/theme-harness.php`: 55 checks pass.
- `cd webgram-theme && npm run build`: clean. JS files syntax-checked with Node.
- Em dash scan: none. Text domain scan: clean.

### E. Errors found and fixed

- Harness stub for `number_format_i18n` was missing in the Core harness (used by media validation messages); added.
- Compare bar used a CSS variable the theme never defines; replaced with the theme's `body.wg-has-bottom-nav` rule.

### F. Compatibility concerns

- Review uploads rely on the comment form being submitted with `enctype="multipart/form-data"`; `comment_form()` has no argument for this, so Core's JS sets it. With JavaScript disabled the review still posts but files are dropped (the text is saved).
- Media validation happens in `preprocess_comment`, which runs after WordPress's own duplicate and flood checks; a rejected upload shows the standard `wp_die` screen with a back link, matching how WooCommerce reports rating errors.
- Third-party review plugins: when detected, Core's reviews rendering, form fields and admin column are skipped; the schema filter and shortcode stay registered but render nothing unless "Stand down" is turned off.
- Full-page caches: wishlist and compare buttons read state from the localized data and re-sync on load, so cached pages show the right state after JS runs; the header count badge is a WooCommerce fragment and refreshes with the cart fragments.
- Wishlist page share links carry ids in the URL (max 50, URL safe base64); very long lists produce long URLs but stay under common limits.

### G. Not tested

- Nothing in this phase ran inside WordPress or a browser: review form submission with uploads, moderation flow and private attachment visibility, EXIF stripping, helpful votes, sort and filter queries against real comment meta (the harness only checks argument building), load more, lightbox, admin column, third-party stand down notice, schema output, wishlist and compare toggles, cookie signing in a real browser, merge on login, header counts and fragments, page setup notice and page creation, share links, compare table rendering with variable products, floating bar, theme styles at desktop, tablet and mobile widths.
- Acceptance scenarios from the spec (guest review with photo held for moderation and image not public until approved; Storefront keeps reviews visible; wishlist survives guest to login) not run.
- PHPCS with WordPress and WooCommerce rulesets, Lighthouse, accessibility audits.

### H. Known limitations

- "View All Reviews" expands the list inline (loads further pages) instead of opening a separate modal; the spec allows either.
- The Elementor widgets for reviews, wishlist and compare are registered as definitions only; the Integrations module in Phase 5 turns them into Elementor widgets and Gutenberg blocks.
- WooCommerce's own review counts include reviews without a rating; the summary uses WooCommerce's review count for the total and its rating counts for the bars, so the bars can add up to less than the total on stores where ratings are optional.
- The rating sort uses only reviews with a rating meta (reviews without a rating are excluded from that sort).
- Compare "differences" compares text values; price cells are compared on their HTML stripped to text.

### I. Ready for next phase

Phase 5: homepage sections (spec 4.3), Slider CPT and renderer, Instagram feed, testimonials CPT, Elementor widgets and Gutenberg blocks (Integrations module consuming `webgram_core/elementor/widgets`), Layout widgets for single product, demo content.
