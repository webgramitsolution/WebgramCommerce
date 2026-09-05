# Webgram demo content

Files in this folder reproduce the reference homepage (BUILD-SPEC section 4.3) with Webgram sections only.

- `homepage-gutenberg.html`: block markup for the homepage. Create a page, switch the editor to the code view, paste the content, publish, and set it as the front page under Settings > Reading. Requires Webgram Core (blocks in the "Webgram" category) for the product, category, slider, testimonials, Instagram and coupon sections. The presentational sections (heading, strip, benefits, about, blog grid, banner) work without Core.
- `homepage-elementor.json`: an Elementor page template with the same sections built from the "Webgram" widget category. Import it under Templates > Saved Templates > Import Templates, then insert it into a page. Requires Elementor and Webgram Core.
- Slider ids, product tags and testimonial ids in these files refer to demo data: create a slider under Webgram > Sliders, tag your saver packs `mega-saver`, and add testimonials under Webgram > Testimonials, then adjust the ids in the block or widget options.

Phase 8 adds the one-click importer (WXR, Theme Settings JSON, header and footer layouts, Elementor kit) with original placeholder images.
