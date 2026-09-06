<?php
namespace Webgram\Core\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Single product layout widgets for Core Layouts (Elementor or Gutenberg built product pages). Each part calls the
 * WooCommerce template function for that piece, so third-party hooks on those functions keep working.
 */
final class ProductParts {

	public function register(): void {
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'definitions' ] );
	}

	private function part( string $id, string $title, string $icon, callable $render, array $controls = [] ): array {
		return [
			'title'    => $title,
			'icon'     => $icon,
			'category' => 'webgram-product',
			'controls' => $controls,
			'render'   => static function ( array $args ) use ( $render ): string {
				if ( ! function_exists( 'wc_get_product' ) ) {
					return '';
				}
				$product = Preview::product();
				if ( ! $product ) {
					return '';
				}
				ob_start();
				$render( $product, $args );
				return (string) ob_get_clean();
			},
		];
	}

	public function definitions( array $w ): array {
		$w['product_title']       = $this->part( 'product_title', __( 'Product Title', 'webgram-core' ), 'eicon-product-title', static function ( \WC_Product $p, array $a ): void {
			$tag = in_array( $a['tag'], [ 'h1', 'h2', 'h3', 'div' ], true ) ? $a['tag'] : 'h1';
			printf( '<%1$s class="product_title entry-title wg-product__title">%2$s</%1$s>', esc_attr( $tag ), esc_html( $p->get_name() ) );
		}, [ 'tag' => [ 'label' => __( 'HTML tag', 'webgram-core' ), 'type' => 'select', 'options' => [ 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div' ], 'default' => 'h1' ] ] );
		$w['product_price']       = $this->part( 'product_price', __( 'Product Price', 'webgram-core' ), 'eicon-product-price', static fn( \WC_Product $p ) => woocommerce_template_single_price() );
		$w['product_rating']      = $this->part( 'product_rating', __( 'Product Rating', 'webgram-core' ), 'eicon-product-rating', static fn( \WC_Product $p ) => woocommerce_template_single_rating() );
		$w['product_excerpt']     = $this->part( 'product_excerpt', __( 'Product Short Description', 'webgram-core' ), 'eicon-product-description', static fn( \WC_Product $p ) => woocommerce_template_single_excerpt() );
		$w['product_add_to_cart'] = $this->part( 'product_add_to_cart', __( 'Product Add To Cart', 'webgram-core' ), 'eicon-product-add-to-cart', static fn( \WC_Product $p ) => woocommerce_template_single_add_to_cart() );
		$w['product_gallery']     = $this->part( 'product_gallery', __( 'Product Gallery', 'webgram-core' ), 'eicon-product-images', static function ( \WC_Product $p ): void {
			if ( has_action( 'webgram/product/gallery' ) ) {
				do_action( 'webgram/product/gallery', $p );
			} else {
				woocommerce_show_product_images();
			}
		} );
		$w['product_meta']        = $this->part( 'product_meta', __( 'Product Meta', 'webgram-core' ), 'eicon-product-meta', static fn( \WC_Product $p ) => woocommerce_template_single_meta() );
		$w['product_stock']       = $this->part( 'product_stock', __( 'Product Stock', 'webgram-core' ), 'eicon-product-stock', static fn( \WC_Product $p ) => print( wp_kses_post( wc_get_stock_html( $p ) ) ) );
		$w['product_tabs']        = $this->part( 'product_tabs', __( 'Product Data Tabs', 'webgram-core' ), 'eicon-product-tabs', static fn( \WC_Product $p ) => woocommerce_output_product_data_tabs() );
		$w['product_description'] = $this->part( 'product_description', __( 'Product Description', 'webgram-core' ), 'eicon-text', static function ( \WC_Product $p ): void {
			echo '<div class="woocommerce-product-details__description wg-product__overview">' . wp_kses_post( apply_filters( 'the_content', $p->get_description() ) ) . '</div>';
		} );
		$w['product_related']     = $this->part( 'product_related', __( 'Related Products', 'webgram-core' ), 'eicon-product-related', static function ( \WC_Product $p, array $a ): void {
			woocommerce_related_products( [ 'posts_per_page' => (int) $a['limit'], 'columns' => (int) $a['columns'], 'orderby' => 'rand' ] );
		}, [ 'limit' => [ 'label' => __( 'Products', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 4 ], 'columns' => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 4 ] ] );
		$w['product_upsells']     = $this->part( 'product_upsells', __( 'Upsell Products', 'webgram-core' ), 'eicon-product-related', static function ( \WC_Product $p, array $a ): void {
			woocommerce_upsell_display( (int) $a['limit'], (int) $a['columns'] );
		}, [ 'limit' => [ 'label' => __( 'Products', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 4 ], 'columns' => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 4 ] ] );
		$w['product_breadcrumb']  = $this->part( 'product_breadcrumb', __( 'Breadcrumb', 'webgram-core' ), 'eicon-product-breadcrumbs', static fn( \WC_Product $p ) => woocommerce_breadcrumb() );
		$w['product_section']     = $this->part( 'product_section', __( 'Webgram Product Section', 'webgram-core' ), 'eicon-info-box', static function ( \WC_Product $p, array $a ): void {
			do_action( 'webgram/product/summary/' . sanitize_key( (string) $a['section'] ), $p ); // Core summary providers (coupon, contact seller, pincode, specifications, buy now).
		}, [ 'section' => [ 'label' => __( 'Section', 'webgram-core' ), 'type' => 'select', 'options' => [ 'coupon' => __( 'Coupon box', 'webgram-core' ), 'contact_seller' => __( 'Contact seller', 'webgram-core' ), 'pincode' => __( 'Delivery check', 'webgram-core' ), 'specifications' => __( 'Specifications', 'webgram-core' ), 'payment_strip' => __( 'Payment strip', 'webgram-core' ), 'trust_seals' => __( 'Trust seals', 'webgram-core' ), 'share' => __( 'Share', 'webgram-core' ) ], 'default' => 'specifications' ] ] );
		return $w;
	}
}
