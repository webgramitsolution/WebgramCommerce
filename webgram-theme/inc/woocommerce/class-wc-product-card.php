<?php
/**
 * Product card renderer. One renderer used by the shop loop, sliders, Elementor widgets, AJAX responses and
 * (through Core) the AI assistant. Core modules add badges and actions through the hooks fired in the template.
 *
 * Data prepared here: images for hover slideshow, price parts, savings, rating, variation chips with a trimmed
 * variations JSON (id, price html, image, attributes, stock) consumed by assets/src/js/modules/product-card.js.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Product_Card {

	/**
	 * @param WC_Product|int $product
	 * @param array{variant?: string, image_size?: string, show_buy_now?: bool, show_rating?: bool, lazy?: bool, show_chips?: bool, show_cart?: bool, show_actions?: bool} $args
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
				'show_buy_now' => (bool) webgram_option( 'card_show_buy_now' ),
				'show_cart'    => (bool) webgram_option( 'card_show_cart' ),
				'show_rating'  => 'hidden' !== webgram_option( 'card_rating_position' ),
				'show_chips'   => (bool) webgram_option( 'card_show_chips' ),
				'show_actions' => 'hidden' !== webgram_option( 'card_actions_position' ),
				'lazy'         => true,
				'hover'        => (string) webgram_option( 'card_hover_effect' ),
			]
		);
		$args = (array) apply_filters( 'webgram/product_card/args', $args, $product );

		$data = [
			'product'   => $product,
			'args'      => $args,
			'permalink' => $product->get_permalink(),
			'title'     => $product->get_name(),
			'image'     => self::image( $product, $args ),
			'gallery'   => 'none' === $args['hover'] ? [] : self::gallery( $product, $args ),
			'price'     => self::price_parts( $product ),
			'save'      => self::savings( $product ),
			'rating'    => $args['show_rating'] && wc_review_ratings_enabled() ? (float) $product->get_average_rating() : 0.0,
			'reviews'   => (int) $product->get_review_count(),
			'chips'     => $args['show_chips'] ? self::chips( $product ) : null,
			'classes'   => self::classes( $product, $args ),
		];
		$data = (array) apply_filters( 'webgram/product_card/data', $data, $product );

		ob_start();
		do_action( 'webgram/product_card/before', $product, $args );
		get_template_part( 'template-parts/cards/product-card', 'standard' === $args['variant'] ? null : $args['variant'], $data );
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

	/** Up to 4 extra image URLs for hover swap or slideshow (loaded by JS on first hover, never on page load). */
	public static function gallery( WC_Product $product, array $args ): array {
		$ids  = array_slice( array_map( 'intval', (array) $product->get_gallery_image_ids() ), 0, 'swap' === $args['hover'] ? 1 : 4 );
		$urls = [];
		foreach ( $ids as $id ) {
			$url = wp_get_attachment_image_url( $id, $args['image_size'] );
			if ( $url ) {
				$urls[] = $url;
			}
		}
		return $urls;
	}

	/**
	 * Price pieces so the template can lay out "sale, regular strike, percent" on one line.
	 *
	 * @return array{html: string, sale: string, regular: string, is_range: bool}
	 */
	public static function price_parts( WC_Product $product ): array {
		$parts = [ 'html' => $product->get_price_html(), 'sale' => '', 'regular' => '', 'is_range' => false ];
		if ( $product->is_type( 'variable' ) ) {
			$min = (float) $product->get_variation_price( 'min' );
			$max = (float) $product->get_variation_price( 'max' );
			$parts['is_range'] = $min !== $max;
			$parts['sale']     = $parts['is_range'] ? '' : wc_price( wc_get_price_to_display( $product, [ 'price' => $min ] ) );
			$reg               = (float) $product->get_variation_regular_price( 'max' );
			if ( ! $parts['is_range'] && $product->is_on_sale() && $reg > $min ) {
				$parts['regular'] = wc_price( wc_get_price_to_display( $product, [ 'price' => $reg ] ) );
			}
		} elseif ( $product->is_on_sale() && '' !== $product->get_sale_price() ) {
			$parts['sale']    = wc_price( wc_get_price_to_display( $product, [ 'price' => (float) $product->get_sale_price() ] ) );
			$parts['regular'] = wc_price( wc_get_price_to_display( $product, [ 'price' => (float) $product->get_regular_price() ] ) );
		} elseif ( '' !== $product->get_price() ) {
			$parts['sale'] = wc_price( wc_get_price_to_display( $product ) );
		}
		return $parts;
	}

	/**
	 * Savings label for simple/variable products, e.g. "Save ₹400" and "33% off" (variable: max saving).
	 *
	 * @return array{amount: string, amount_raw: float, percent: int}|null
	 */
	public static function savings( WC_Product $product ): ?array {
		if ( ! $product->is_on_sale() ) {
			return null;
		}
		if ( $product->is_type( 'variable' ) ) {
			$best = 0.0;
			$pct  = 0;
			foreach ( $product->get_children() as $child_id ) {
				$v = wc_get_product( $child_id );
				if ( ! $v ) {
					continue;
				}
				$r = (float) $v->get_regular_price();
				$s = (float) $v->get_sale_price();
				if ( $r > 0 && $s > 0 && $s < $r && ( $r - $s ) > $best ) {
					$best = $r - $s;
					$pct  = (int) round( ( $r - $s ) / $r * 100 );
				}
			}
			if ( $best <= 0 ) {
				return null;
			}
			return [ 'amount' => wc_price( $best ), 'amount_raw' => $best, 'percent' => $pct ];
		}
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return null;
		}
		return [ 'amount' => wc_price( $regular - $sale ), 'amount_raw' => $regular - $sale, 'percent' => self::percent( $regular, $sale ) ];
	}

	/** Pure percent helper (harness tested). */
	public static function percent( float $regular, float $sale ): int {
		if ( $regular <= 0 || $sale >= $regular ) {
			return 0;
		}
		return (int) round( ( $regular - $sale ) / $regular * 100 );
	}

	/**
	 * Variation chips for one attribute plus a trimmed variations JSON.
	 *
	 * @return array{attribute: string, label: string, style: string, chips: array, more: int, variations: array}|null
	 */
	public static function chips( WC_Product $product ): ?array {
		if ( ! $product->is_type( 'variable' ) ) {
			return null;
		}
		$attributes = $product->get_variation_attributes();
		if ( ! $attributes ) {
			return null;
		}
		$chosen = (string) get_post_meta( $product->get_id(), '_wg_chip_attribute', true ) ?: (string) webgram_option( 'card_chip_attribute' );
		if ( 'first' === $chosen || ! isset( $attributes[ $chosen ] ) ) {
			$chosen = (string) array_key_first( $attributes );
		}
		$values = array_values( array_filter( (array) $attributes[ $chosen ] ) );
		if ( count( $values ) < 1 ) {
			return null;
		}
		$max   = max( 2, (int) webgram_option( 'card_chips_max' ) );
		$style = (string) webgram_option( 'card_chip_style' );
		$key   = 'attribute_' . sanitize_title( $chosen );

		$variations = [];
		foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
			$attrs = $variation->get_variation_attributes();
			$img   = $variation->get_image_id() ? wp_get_attachment_image_url( (int) $variation->get_image_id(), 'webgram-card' ) : '';
			$variations[] = [
				'id'    => $variation->get_id(),
				'price' => $variation->get_price_html(),
				'image' => $img ?: '',
				'attrs' => $attrs,
				'stock' => $variation->is_in_stock(),
				'save'  => self::percent( (float) $variation->get_regular_price(), (float) $variation->get_sale_price() ),
			];
		}

		$chips = [];
		foreach ( array_slice( $values, 0, $max ) as $i => $value ) {
			$label = $value;
			if ( taxonomy_exists( $chosen ) ) {
				$term  = get_term_by( 'slug', $value, $chosen );
				$label = $term ? $term->name : $value;
			}
			$chips[] = [
				'value' => $value,
				'label' => $label,
				'color' => 'colors' === $style ? self::term_color( $chosen, $value ) : '',
				'image' => 'images' === $style ? self::first_variation_image( $variations, $key, $value ) : '',
			];
		}
		return [
			'attribute'  => $chosen,
			'key'        => $key,
			'label'      => wc_attribute_label( $chosen, $product ),
			'style'      => $style,
			'chips'      => $chips,
			'more'       => max( 0, count( $values ) - $max ),
			'variations' => (array) apply_filters( 'webgram/product_card/variations', $variations, $product ),
		];
	}

	/** Color for a color-style chip: term meta "color" (set by common swatch plugins) or the value when it is a hex. */
	private static function term_color( string $taxonomy, string $value ): string {
		if ( taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( $term ) {
				foreach ( [ 'color', '_wg_color', 'product_attribute_color' ] as $meta ) {
					$c = (string) get_term_meta( $term->term_id, $meta, true );
					if ( $c && Webgram_Settings_Sanitizer::color( $c ) ) {
						return Webgram_Settings_Sanitizer::color( $c );
					}
				}
			}
		}
		return Webgram_Settings_Sanitizer::color( $value ) ?: '';
	}

	private static function first_variation_image( array $variations, string $key, string $value ): string {
		foreach ( $variations as $v ) {
			if ( ( $v['attrs'][ $key ] ?? null ) === $value && $v['image'] ) {
				return $v['image'];
			}
		}
		return '';
	}

	private static function classes( WC_Product $product, array $args ): string {
		$classes = [ 'wg-card', 'wg-card--' . $args['variant'], 'wg-card--' . $product->get_type(), 'wg-card--ratio-' . (string) webgram_option( 'card_image_ratio' ), 'wg-card--hover-' . $args['hover'], 'wg-card--actions-' . (string) webgram_option( 'card_actions_position' ), 'wg-card--rating-' . (string) webgram_option( 'card_rating_position' ) ];
		if ( $product->is_on_sale() ) {
			$classes[] = 'is-on-sale';
		}
		if ( ! $product->is_in_stock() ) {
			$classes[] = 'is-out-of-stock';
		}
		return implode( ' ', array_map( 'sanitize_html_class', (array) apply_filters( 'webgram/product_card/classes', $classes, $product ) ) );
	}

	/**
	 * Loop add-to-cart button classes: WooCommerce's defaults (product type, add_to_cart_button, ajax_add_to_cart)
	 * plus theme classes. Passing "class" to woocommerce_template_loop_add_to_cart replaces the defaults, so they
	 * are rebuilt here to keep AJAX add-to-cart and third-party hooks working.
	 */
	public static function cart_button_class( WC_Product $product, string $extra = '' ): string {
		$classes = [ 'button', 'wg-btn', 'wg-btn--icon', 'wg-card__cart', 'product_type_' . $product->get_type() ];
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			$classes[] = 'add_to_cart_button';
		}
		if ( $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			$classes[] = 'ajax_add_to_cart';
		}
		if ( function_exists( 'wp_theme_get_element_class_name' ) ) {
			$classes[] = (string) wp_theme_get_element_class_name( 'button' );
		}
		if ( '' !== $extra ) {
			$classes[] = $extra;
		}
		return implode( ' ', array_filter( $classes ) );
	}

	/** Rating pill markup ("★ 4.5 (12)"), reused by the PDP price line. */
	public static function rating_pill( float $rating, int $count, string $href = '', bool $show_max = false ): string {
		if ( $rating <= 0 ) {
			return '';
		}
		$label = sprintf( /* translators: 1: rating, 2: review count */ __( 'Rated %1$s out of 5 from %2$d reviews', 'webgram' ), number_format_i18n( $rating, 1 ), $count );
		$inner = webgram_icon( 'star', 'wg-rating-pill__star', false ) . '<span class="wg-rating-pill__value">' . esc_html( number_format_i18n( $rating, $show_max ? 2 : 1 ) ) . ( $show_max ? '<span class="wg-rating-pill__max">/5</span>' : '' ) . '</span>';
		$inner .= $show_max ? '<span class="wg-rating-pill__sep"></span>' : '';
		$inner .= '<span class="wg-rating-pill__count">' . ( $show_max ? esc_html( sprintf( /* translators: %d: review count */ _n( '%d review', '%d reviews', $count, 'webgram' ), $count ) ) : '(' . esc_html( (string) $count ) . ')' ) . '</span>';
		$tag   = $href ? 'a' : 'span';
		return '<' . $tag . ' class="wg-rating-pill' . ( $show_max ? ' wg-rating-pill--lg' : '' ) . '"' . ( $href ? ' href="' . esc_url( $href ) . '"' : '' ) . ' aria-label="' . esc_attr( $label ) . '">' . $inner . '</' . $tag . '>';
	}
}
