# Webgram demo content

Files in this folder reproduce the reference homepage (BUILD-SPEC section 4.3) with Webgram sections only.

- `homepage-gutenberg.html`: block markup for the homepage. Create a page, switch the editor to the code view, paste the content, publish, and set it as the front page under Settings > Reading. Requires Webgram Core (blocks in the "Webgram" category) for the product, category, slider, testimonials, Instagram and coupon sections. The presentational sections (heading, strip, benefits, about, blog grid, banner) work without Core.
- `homepage-elementor.json`: an Elementor page template with the same sections built from the "Webgram" widget category. Import it under Templates > Saved Templates > Import Templates, then insert it into a page. Requires Elementor and Webgram Core.
- Slider ids, product tags and testimonial ids in these files refer to demo data: create a slider under Webgram > Sliders, tag your saver packs `mega-saver`, and add testimonials under Webgram > Testimonials, then adjust the ids in the block or widget options.

## One click import

Webgram > Demo import runs the importer in this order, each step idempotent:

- `theme-settings.json`: Theme Settings values plus the header and footer builder presets to apply.
- `images/*.png`: original flat placeholder images (self-made, CC0) sideloaded once into the media library and matched later by the `_webgram_demo` meta.
- `products.csv`: 12 sample products in 4 categories imported through WooCommerce's own CSV importer (column names are WooCommerce field keys; product images are referenced by file name).
- `posts.json`: 4 blog posts with categories and featured images.
- Webgram Core (`webgram/demo/import` action): home slider, 6 testimonials, 2 coupons, wishlist and compare pages. The slider id is written into the homepage block markup.
- Pages: Home (from `homepage-gutenberg.html`), Blog, Help, Track Order, Bulk Order, About, Contact, with Home and Blog assigned under Settings > Reading.
- Menus: Primary (Shop with category children, About, Blog, Help, Contact) assigned to primary, secondary and mobile locations; Footer menu.
- `widgets.json`: footer block widgets for empty footer areas.

Nothing is deleted or overwritten: existing pages, menus, products (by SKU) and widgets are kept.
