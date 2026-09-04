<?php
/**
 * Product card renderer. One renderer used by the shop loop, sliders, Elementor widgets, AJAX responses and
 * (through Core) the AI assistant. Core modules add badges and actions through the two filters/actions below.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Product_Card {

	/**
	 * @param WC_Product|int $product
	 * @param array{variant?: string, image_size?: string, show_buy_now?: bool, show_rating?: bool, lazy?: bool} $args
	 */
	public static function render( $product, array $args = [], bool $echo = true ): string {
		$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
		if ( ! $product || ! $product->is_visible() ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			[
				'variant'      => (string) webgram_option( 'product_card_style' ),
				'image_size'   => 'webgram-card',
				'show_buy_now' => true,
				'show_rating'  => true,
				'lazy'         => true,
			]
		);
		$args = (array) apply_filters( 'webgram/product_card/args', $args, $product );

		$data = [
			'product'   => $product,
			'args'      => $args,
			'permalink' => $product->get_permalink(),
			'title'     => $product->get_name(),
			'image'     => self::image( $product, $args ),
			'price'     => $product->get_price_html(),
			'save'      => self::savings( $product ),
			'rating'    => $args['show_rating'] && wc_review_ratings_enabled() ? (float) $product->get_average_rating() : 0.0,
			'reviews'   => (int) $product->get_review_count(),
			'classes'   => self::classes( $product, $args ),
		];

		ob_start();
		do_action( 'webgram/product_card/before', $product, $args );
		get_template_part( 'template-parts/cards/product-card', $args['variant'], $data );
		do_action( 'webgram/product_card/after', $product, $args );
		$html = (string) ob_get_clean();

		if ( $echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes its output.
		}
		return $html;
	}

	private static function image( WC_Product $product, array $args ): string {
		$attr = [ 'class' => 'wg-card__img' ];
		if ( $args['lazy'] ) {
			$attr['loading'] = 'lazy';
		}
		$id = $product->get_image_id();
		if ( $id ) {
			return (string) wp_get_attachment_image( (int) $id, $args['image_size'], false, $attr );
		}
		return (string) wc_placeholder_img( $args['image_size'], $attr );
	}

	/**
	 * Savings label for simple/variable products, e.g. "Save ₹400" and "33% off".
	 *
	 * @return array{amount: string, percent: int}|null
	 */
	public static function savings( WC_Product $product ): ?array {
		if ( ! $product->is_on_sale() ) {
			return null;
		}
		$regular = (float) ( $product->is_type( 'variable' ) ? $product->get_variation_regular_price( 'max' ) : $product->get_regular_price() );
		$sale    = (float) ( $product->is_type( 'variable' ) ? $product->get_variation_sale_price( 'min' ) : $product->get_sale_price() );
		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return null;
		}
		return [
			'amount'  => wc_price( $regular - $sale ),
			'percent' => (int) round( ( ( $regular - $sale ) / $regular ) * 100 ),
		];
	}

	private static function classes( WC_Product $product, array $args ): string {
		$classes = [ 'wg-card', 'wg-card--' . $args['variant'], 'wg-card--' . $product->get_type() ];
		if ( $product->is_on_sale() ) {
			$classes[] = 'is-on-sale';
		}
		if ( ! $product->is_in_stock() ) {
			$classes[] = 'is-out-of-stock';
		}
		return implode( ' ', array_map( 'sanitize_html_class', (array) apply_filters( 'webgram/product_card/classes', $classes, $product ) ) );
	}
}
