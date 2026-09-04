<?php
/**
 * Single product page (spec 4.4): section ordering from Theme Settings mapped to callbacks on
 * woocommerce_single_product_summary, custom gallery, stacked sections, below-the-columns blocks, mobile sticky bar.
 * Ids without a provider (Core module off) are skipped.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Product {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'wp', [ self::class, 'hooks' ] );
		add_filter( 'woocommerce_product_tabs', [ self::class, 'tabs' ], 98 );
		add_filter( 'body_class', [ self::class, 'body_class' ] );
	}

	public static function body_class( array $classes ): array {
		if ( function_exists( 'is_product' ) && is_product() && webgram_option( 'product_sticky_bar' ) ) {
			$classes[] = 'wg-has-product-bar';
		}
		return $classes;
	}

	/** Summary section id => callable. Core modules add theirs through the filter. */
	public static function providers(): array {
		$providers = [
			'title'             => 'woocommerce_template_single_title',
			'meta'              => [ self::class, 'meta' ],
			'price'             => [ self::class, 'price' ],
			'short_description' => 'woocommerce_template_single_excerpt',
			'variations'        => [ self::class, 'add_to_cart_form' ],
			'quantity_cart'     => [ self::class, 'add_to_cart_form' ],
			'payment_strip'     => [ self::class, 'payment_strip' ],
			'trust_seals'       => [ self::class, 'trust_seals' ],
			'shipping_returns'  => [ self::class, 'info_cards' ],
			'specifications'    => [ self::class, 'specifications' ],
			'overview'          => [ self::class, 'overview' ],
			'share'             => [ self::class, 'share' ],
		];
		return (array) apply_filters( 'webgram/product/summary_providers', $providers );
	}

	public static function hooks(): void {
		if ( ! is_product() ) {
			return;
		}
		// Gallery: theme's own gallery replaces WooCommerce's flexslider markup.
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		add_action( 'woocommerce_before_single_product_summary', [ self::class, 'gallery' ], 20 );

		// Summary: clear defaults, re-add in the configured order.
		foreach ( [ 'woocommerce_template_single_title' => 5, 'woocommerce_template_single_rating' => 10, 'woocommerce_template_single_price' => 10, 'woocommerce_template_single_excerpt' => 20, 'woocommerce_template_single_add_to_cart' => 30, 'woocommerce_template_single_meta' => 40, 'woocommerce_template_single_sharing' => 50 ] as $fn => $prio ) {
			remove_action( 'woocommerce_single_product_summary', $fn, $prio );
		}
		$order     = (array) webgram_option( 'product_summary_order' );
		$providers = self::providers();
		$priority  = 10;
		$done      = [];
		foreach ( $order as $id ) {
			if ( isset( $done[ $id ] ) ) {
				continue;
			}
			$done[ $id ] = true;
			if ( 'variations' === $id && in_array( 'quantity_cart', $order, true ) ) {
				continue; // One add-to-cart form renders swatches, quantity and buttons together.
			}
			if ( isset( $providers[ $id ] ) && is_callable( $providers[ $id ] ) ) {
				add_action( 'woocommerce_single_product_summary', $providers[ $id ], $priority );
			}
			// Core modules render into their slot; nothing prints when the module is off.
			add_action( 'woocommerce_single_product_summary', static fn() => do_action( 'webgram/product/summary/' . $id ), $priority + 1 );
			$priority += 10;
		}

		// Below the columns.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		$below    = (array) webgram_option( 'product_below_order' );
		$priority = 10;
		foreach ( $below as $id ) {
			switch ( $id ) {
				case 'related':
					if ( (int) webgram_option( 'product_related_count' ) > 0 ) {
						add_action( 'woocommerce_after_single_product_summary', [ self::class, 'related' ], $priority );
					}
					break;
				case 'reviews':
					add_action( 'woocommerce_after_single_product_summary', [ self::class, 'reviews' ], $priority );
					break;
				default:
					add_action( 'woocommerce_after_single_product_summary', static fn() => do_action( 'webgram/product/below/' . $id ), $priority );
			}
			$priority += 10;
		}
		if ( 'tabs' === webgram_option( 'product_tabs_style' ) ) {
			add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 5 );
		}
		add_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 95 );
		add_action( 'woocommerce_after_single_product_summary', static fn() => do_action( 'webgram/product/after_related' ), 96 );
		add_action( 'webgram/product/after_columns', static fn() => do_action( 'webgram/product/after_summary' ) );

		if ( webgram_option( 'product_sticky_bar' ) ) {
			add_action( 'webgram/after_page', [ self::class, 'sticky_bar' ], 60 );
		}
	}

	/** Stacked sections replace the tabs: description and attributes render inline, reviews below. */
	public static function tabs( array $tabs ): array {
		if ( is_product() && 'stacked' === webgram_option( 'product_tabs_style' ) ) {
			unset( $tabs['description'], $tabs['additional_information'], $tabs['reviews'] );
		}
		return $tabs;
	}

	public static function gallery(): void {
		global $product;
		webgram_part( 'product/gallery', [ 'product' => $product ] );
	}

	public static function meta(): void {
		if ( webgram_option( 'product_meta_show' ) ) {
			woocommerce_template_single_meta();
		}
	}

	public static function price(): void {
		global $product;
		webgram_part( 'product/price', [ 'product' => $product ] );
	}

	public static function add_to_cart_form(): void {
		global $product;
		if ( $product->is_type( 'variable' ) && in_array( 'variations', (array) webgram_option( 'product_summary_order' ), true ) ) {
			add_action( 'woocommerce_before_variations_form', [ self::class, 'swatches' ] );
		}
		woocommerce_template_single_add_to_cart();
		remove_action( 'woocommerce_before_variations_form', [ self::class, 'swatches' ] );
	}

	public static function swatches(): void {
		global $product;
		if ( ! apply_filters( 'webgram/product/use_swatches', true, $product ) ) {
			return; // A third-party swatch plugin can take over.
		}
		webgram_part( 'product/swatches', [ 'product' => $product ] );
	}

	public static function payment_strip(): void {
		if ( webgram_option( 'product_payment_strip' ) ) {
			webgram_part( 'product/payment-strip' );
		}
	}

	public static function trust_seals(): void {
		webgram_part( 'product/trust-seals', [ 'seals' => (array) webgram_option( 'product_trust_seals' ) ] );
	}

	public static function info_cards(): void {
		global $product;
		$cards = (array) apply_filters( 'webgram/product/info_cards', (array) webgram_option( 'product_info_cards' ), $product );
		webgram_part( 'product/info-cards', [ 'cards' => $cards ] );
	}

	/** Specifications: Core provides the merged table; fallback prints WooCommerce attributes. */
	public static function specifications(): void {
		global $product;
		if ( has_action( 'webgram/product/specifications' ) ) {
			do_action( 'webgram/product/specifications', $product );
			return;
		}
		if ( ! $product->has_attributes() && ! $product->has_dimensions() && ! $product->has_weight() ) {
			return;
		}
		echo '<section class="wg-product__section wg-specs" data-wg-accordion><h2 class="wg-product__section-title">' . esc_html__( 'Specifications', 'webgram' ) . '</h2><div class="wg-product__section-body">';
		wc_display_product_attributes( $product );
		echo '</div></section>';
	}

	public static function overview(): void {
		global $product;
		$content = apply_filters( 'the_content', $product->get_description() );
		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return;
		}
		echo '<section class="wg-product__section wg-overview" data-wg-accordion><h2 class="wg-product__section-title">' . esc_html__( 'Overview', 'webgram' ) . '</h2><div class="wg-product__section-body wg-prose">' . $content . '</div></section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output.
	}

	public static function share(): void {
		if ( webgram_option( 'product_share_show' ) ) {
			webgram_part( 'misc/share', [ 'url' => get_permalink(), 'title' => get_the_title() ] );
		}
	}

	public static function related(): void {
		add_filter( 'woocommerce_product_related_products_heading', static fn() => __( 'Related Products', 'webgram' ) );
		echo '<div class="wg-product__related wg-section-ornament">';
		woocommerce_output_related_products();
		echo '</div>';
	}

	public static function reviews(): void {
		global $product;
		if ( ! comments_open() && ! $product->get_review_count() ) {
			return;
		}
		if ( has_action( 'webgram/product/reviews' ) ) {
			do_action( 'webgram/product/reviews', $product ); // Core Reviews module (Phase 4).
			return;
		}
		echo '<div class="wg-product__reviews" id="reviews-anchor">';
		comments_template();
		echo '</div>';
	}

	public static function sticky_bar(): void {
		global $product;
		if ( $product instanceof WC_Product && $product->is_purchasable() ) {
			webgram_part( 'product/sticky-bar', [ 'product' => $product ] );
		}
	}
}

Webgram_WC_Product::init();
