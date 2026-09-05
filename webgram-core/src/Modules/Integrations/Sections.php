<?php
namespace Webgram\Core\Modules\Integrations;

use Webgram\Core\Support\Helpers;
use Webgram\Core\Support\ProductQuery;

defined( 'ABSPATH' ) || exit;

/** Core section renderers (products, categories, coupons, trust badges, testimonials). Templates in templates/sections. */
final class Sections {

	public function register(): void {
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'definitions' ] );
	}

	private static function heading_controls( string $title ): array {
		return [
			'title'         => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text', 'default' => $title ],
			'subtitle'      => [ 'label' => __( 'Subtitle', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			'heading_align' => [ 'label' => __( 'Heading align', 'webgram-core' ), 'type' => 'select', 'options' => [ 'center' => __( 'Center with ornament', 'webgram-core' ), 'start' => __( 'Left', 'webgram-core' ) ], 'default' => 'center' ],
			'view_all_text' => [ 'label' => __( '"View all" text', 'webgram-core' ), 'type' => 'text', 'default' => __( 'View All', 'webgram-core' ) ],
			'view_all_url'  => [ 'label' => __( '"View all" link', 'webgram-core' ), 'type' => 'url', 'default' => '' ],
		];
	}

	private static function product_controls( string $title, string $source, string $layout ): array {
		return self::heading_controls( $title ) + [
			'source'   => [ 'label' => __( 'Source', 'webgram-core' ), 'type' => 'select', 'options' => [ 'recent' => __( 'Newest', 'webgram-core' ), 'best_selling' => __( 'Best selling', 'webgram-core' ), 'trending' => __( 'Trending', 'webgram-core' ), 'on_sale' => __( 'On sale', 'webgram-core' ), 'featured' => __( 'Featured', 'webgram-core' ), 'top_rated' => __( 'Top rated', 'webgram-core' ), 'category' => __( 'Category', 'webgram-core' ), 'tag' => __( 'Tag', 'webgram-core' ), 'ids' => __( 'Selected products', 'webgram-core' ), 'random' => __( 'Random', 'webgram-core' ) ], 'default' => $source ],
			'category' => [ 'label' => __( 'Categories', 'webgram-core' ), 'type' => 'category', 'default' => [] ],
			'tag'      => [ 'label' => __( 'Tags', 'webgram-core' ), 'type' => 'tag', 'default' => [] ],
			'ids'      => [ 'label' => __( 'Product IDs (comma separated)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			'limit'    => [ 'label' => __( 'Products', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 48, 'default' => 'band' === $layout ? 4 : 5 ],
			'columns'  => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 'band' === $layout ? 4 : 5 ],
			'layout'   => [ 'label' => __( 'Layout', 'webgram-core' ), 'type' => 'select', 'options' => [ 'grid' => __( 'Grid (slider on mobile)', 'webgram-core' ), 'slider' => __( 'Slider', 'webgram-core' ), 'band' => __( 'Dark band with big title', 'webgram-core' ) ], 'default' => $layout ],
			'band_line1' => [ 'label' => __( 'Band title line 1', 'webgram-core' ), 'type' => 'text', 'default' => __( 'BEST', 'webgram-core' ) ],
			'band_line2' => [ 'label' => __( 'Band title line 2 (script)', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Sellers', 'webgram-core' ) ],
			'in_stock' => [ 'label' => __( 'Only in stock', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
		];
	}

	public function definitions( array $w ): array {
		$products = fn( array $a ) => $this->products( $a );
		$w['product_grid']   = [ 'title' => __( 'Webgram Product Grid', 'webgram-core' ), 'icon' => 'eicon-products', 'controls' => self::product_controls( __( 'Our Products', 'webgram-core' ), 'recent', 'grid' ), 'render' => $products ];
		$w['product_slider'] = [ 'title' => __( 'Webgram Product Slider', 'webgram-core' ), 'icon' => 'eicon-slider-push', 'controls' => self::product_controls( __( 'New Arrivals', 'webgram-core' ), 'recent', 'slider' ), 'render' => $products ];
		$w['trending']       = [ 'title' => __( 'Webgram Trending Products', 'webgram-core' ), 'icon' => 'eicon-products', 'controls' => self::product_controls( __( 'Trending Products', 'webgram-core' ), 'trending', 'grid' ), 'render' => $products ];
		$w['best_sellers']   = [ 'title' => __( 'Webgram Best Sellers', 'webgram-core' ), 'icon' => 'eicon-products', 'controls' => self::product_controls( __( 'Best Sellers', 'webgram-core' ), 'best_selling', 'band' ), 'render' => $products ];
		$w['mega_saver']     = [ 'title' => __( 'Webgram Mega Saver Packs', 'webgram-core' ), 'icon' => 'eicon-products', 'controls' => self::product_controls( __( 'Mega Saver Packs', 'webgram-core' ), 'tag', 'grid' ), 'render' => $products ];
		$w['featured']       = [ 'title' => __( 'Webgram Featured Products', 'webgram-core' ), 'icon' => 'eicon-products', 'controls' => self::product_controls( __( 'Featured', 'webgram-core' ), 'featured', 'grid' ), 'render' => $products ];
		$w['categories']     = [
			'title'    => __( 'Webgram Categories', 'webgram-core' ),
			'icon'     => 'eicon-product-categories',
			'controls' => self::heading_controls( __( 'Shop by Category', 'webgram-core' ) ) + [
				'source'         => [ 'label' => __( 'Source', 'webgram-core' ), 'type' => 'select', 'options' => [ 'top' => __( 'Top level categories', 'webgram-core' ), 'selected' => __( 'Selected categories', 'webgram-core' ), 'all' => __( 'All categories', 'webgram-core' ) ], 'default' => 'top' ],
				'category'       => [ 'label' => __( 'Categories', 'webgram-core' ), 'type' => 'category', 'default' => [] ],
				'count'          => [ 'label' => __( 'Number of items', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 24, 'default' => 8 ],
				'columns'        => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 2, 'max' => 10, 'default' => 8 ],
				'shape'          => [ 'label' => __( 'Shape', 'webgram-core' ), 'type' => 'select', 'options' => [ 'circle' => __( 'Circle', 'webgram-core' ), 'square' => __( 'Square', 'webgram-core' ), 'rounded' => __( 'Rounded', 'webgram-core' ) ], 'default' => 'circle' ],
				'label_position' => [ 'label' => __( 'Label', 'webgram-core' ), 'type' => 'select', 'options' => [ 'ribbon' => __( 'Ribbon over the image', 'webgram-core' ), 'below' => __( 'Below the image', 'webgram-core' ) ], 'default' => 'ribbon' ],
				'hide_empty'     => [ 'label' => __( 'Hide empty categories', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
				'show_count'     => [ 'label' => __( 'Show product count', 'webgram-core' ), 'type' => 'switch', 'default' => false ],
			],
			'render'   => fn( array $a ) => $this->categories( $a ),
		];
		$w['coupons_row']    = [
			'title'    => __( 'Webgram Coupons Row', 'webgram-core' ),
			'icon'     => 'eicon-coupon',
			'controls' => self::heading_controls( __( 'Offers for You', 'webgram-core' ) ) + [
				'codes'   => [ 'label' => __( 'Coupon codes (comma separated, empty for all live coupons)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				'limit'   => [ 'label' => __( 'Maximum coupons', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 4 ],
				'columns' => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 4 ],
			],
			'render'   => fn( array $a ) => $this->coupons( $a ),
		];
		$w['trust_badges']   = [
			'title'    => __( 'Webgram Trust Badges', 'webgram-core' ),
			'icon'     => 'eicon-shield',
			'controls' => self::heading_controls( '' ) + [
				'items'   => [ 'label' => __( 'Badges', 'webgram-core' ), 'type' => 'repeater', 'max' => 8, 'default' => [], 'fields' => [ 'title' => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text' ], 'image' => [ 'label' => __( 'Image', 'webgram-core' ), 'type' => 'image' ], 'link' => [ 'label' => __( 'Link', 'webgram-core' ), 'type' => 'url' ] ] ],
				'columns' => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 2, 'max' => 8, 'default' => 5 ],
				'grayscale' => [ 'label' => __( 'Grayscale until hover', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
			],
			'render'   => fn( array $a ) => $this->trust_badges( $a ),
		];
		$w['testimonials']   = [
			'title'    => __( 'Webgram Testimonials', 'webgram-core' ),
			'icon'     => 'eicon-testimonial',
			'controls' => self::heading_controls( __( 'Trusted by Families', 'webgram-core' ) ) + [
				'count'       => [ 'label' => __( 'Testimonials', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 6 ],
				'ids'         => [ 'label' => __( 'Specific testimonials (IDs, comma separated)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				'columns'     => [ 'label' => __( 'Visible cards', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 4, 'default' => 3 ],
				'style'       => [ 'label' => __( 'Style', 'webgram-core' ), 'type' => 'select', 'options' => [ 'dark' => __( 'Dark band', 'webgram-core' ), 'light' => __( 'Light', 'webgram-core' ) ], 'default' => 'dark' ],
				'show_rating' => [ 'label' => __( 'Show stars', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
			],
			'render'   => fn( array $a ) => $this->testimonials( $a ),
		];
		return $w;
	}

	/** Heading args shared by templates. */
	public static function heading( array $a ): array {
		return [ 'title' => (string) ( $a['title'] ?? '' ), 'subtitle' => (string) ( $a['subtitle'] ?? '' ), 'align' => 'start' === ( $a['heading_align'] ?? 'center' ) ? 'start' : 'center', 'link_url' => (string) ( $a['view_all_url'] ?? '' ), 'link_text' => (string) ( $a['view_all_text'] ?? '' ) ];
	}

	private function view( string $name, array $args ): string {
		\webgram_core()->assets()->enqueue_base();
		if ( ! \webgram_core()->assets()->theme_provides_styles() ) {
			wp_enqueue_style( 'webgram-core-sections' );
		}
		return \webgram_core()->view( 'sections/' . $name, $args, false );
	}

	public function products( array $a ): string {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '';
		}
		$products = ProductQuery::products( [ 'source' => $a['source'], 'limit' => $a['limit'], 'category' => $a['category'], 'tag' => $a['tag'], 'ids' => $a['ids'], 'in_stock' => $a['in_stock'] ] );
		if ( ! $products ) {
			return '';
		}
		if ( '' === $a['view_all_url'] && function_exists( 'wc_get_page_permalink' ) ) {
			$a['view_all_url'] = 'category' === $a['source'] && $a['category'] ? (string) get_term_link( (string) $a['category'][0], 'product_cat' ) : wc_get_page_permalink( 'shop' );
			if ( is_wp_error( $a['view_all_url'] ) ) {
				$a['view_all_url'] = wc_get_page_permalink( 'shop' );
			}
		}
		return $this->view( 'products', [ 'products' => $products, 'a' => $a, 'heading' => self::heading( $a ), 'layout' => in_array( $a['layout'], [ 'grid', 'slider', 'band' ], true ) ? $a['layout'] : 'grid' ] );
	}

	public function categories( array $a ): string {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return '';
		}
		$query = [ 'taxonomy' => 'product_cat', 'hide_empty' => (bool) $a['hide_empty'], 'number' => (int) $a['count'], 'orderby' => 'name' ];
		if ( 'selected' === $a['source'] && $a['category'] ) {
			$query['slug']    = $a['category'];
			$query['orderby'] = 'include';
		} elseif ( 'top' === $a['source'] ) {
			$query['parent'] = 0;
		}
		$terms = get_terms( $query );
		$terms = is_array( $terms ) ? array_values( array_filter( $terms, static fn( $t ) => $t instanceof \WP_Term && 'uncategorized' !== $t->slug ) ) : [];
		if ( ! $terms ) {
			return '';
		}
		if ( 'selected' === $a['source'] && $a['category'] ) {
			usort( $terms, static fn( \WP_Term $x, \WP_Term $y ) => array_search( $x->slug, $a['category'], true ) <=> array_search( $y->slug, $a['category'], true ) );
		}
		$items = [];
		foreach ( $terms as $term ) {
			$thumb   = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$items[] = [ 'name' => $term->name, 'url' => (string) get_term_link( $term ), 'image' => $thumb ? (string) wp_get_attachment_image_url( $thumb, 'woocommerce_thumbnail' ) : (string) wc_placeholder_img_src( 'woocommerce_thumbnail' ), 'count' => (int) $term->count ];
		}
		return $this->view( 'categories', [ 'items' => $items, 'a' => $a, 'heading' => self::heading( $a ) ] );
	}

	public function coupons( array $a ): string {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return '';
		}
		$codes = array_values( array_filter( array_map( 'trim', explode( ',', (string) $a['codes'] ) ) ) );
		if ( ! $codes ) {
			foreach ( get_posts( [ 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'numberposts' => (int) $a['limit'] * 3, 'orderby' => 'date', 'order' => 'DESC' ] ) as $post ) {
				$codes[] = (string) $post->post_title;
			}
		}
		$coupons = [];
		$live    = class_exists( '\Webgram\Core\Modules\Coupons\Module' ) ? [ '\Webgram\Core\Modules\Coupons\Module', 'is_live' ] : null;
		foreach ( $codes as $code ) {
			$coupon = new \WC_Coupon( $code );
			if ( ! $coupon->get_id() || ( $live && ! $live( $coupon ) ) ) {
				continue;
			}
			$headline  = class_exists( '\Webgram\Core\Modules\Coupons\Module' ) ? \Webgram\Core\Modules\Coupons\Module::headline( $coupon->get_discount_type(), (float) $coupon->get_amount(), $coupon->get_free_shipping(), $coupon->get_description() ) : strtoupper( $coupon->get_code() );
			$coupons[] = [ 'code' => $coupon->get_code(), 'headline' => $headline, 'description' => $coupon->get_description(), 'expires' => $coupon->get_date_expires() ? $coupon->get_date_expires()->date_i18n( get_option( 'date_format' ) ) : '', 'min' => (float) $coupon->get_minimum_amount() ];
			if ( count( $coupons ) >= (int) $a['limit'] ) {
				break;
			}
		}
		if ( ! $coupons ) {
			return '';
		}
		if ( \webgram_core()->modules()->is_active( 'coupons' ) ) {
			\webgram_core()->assets()->enqueue_module( 'coupons' );
		}
		return $this->view( 'coupons', [ 'coupons' => $coupons, 'a' => $a, 'heading' => self::heading( $a ) ] );
	}

	public function trust_badges( array $a ): string {
		$items = array_values( array_filter( (array) $a['items'], static fn( $i ) => ! empty( $i['image'] ) || ! empty( $i['title'] ) ) );
		if ( ! $items ) {
			return '';
		}
		return $this->view( 'trust-badges', [ 'items' => $items, 'a' => $a, 'heading' => self::heading( $a ) ] );
	}

	public function testimonials( array $a ): string {
		$ids   = array_values( array_filter( array_map( 'intval', explode( ',', (string) $a['ids'] ) ) ) );
		$items = Testimonials::items( (int) $a['count'], $ids );
		if ( ! $items ) {
			return '';
		}
		return $this->view( 'testimonials', [ 'items' => $items, 'a' => $a, 'heading' => self::heading( $a ) ] );
	}
}
