<?php
/**
 * WooCommerce theme support flags and wrappers. Hook repositioning for shop/product/cart pages is added in Phase 2
 * in dedicated class files; this file must stay small.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Setup {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'after_setup_theme', [ self::class, 'supports' ] );
		add_filter( 'loop_shop_columns', [ self::class, 'columns' ] );
		add_filter( 'woocommerce_output_related_products_args', [ self::class, 'related_args' ] );

		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		add_action( 'woocommerce_before_main_content', [ self::class, 'wrapper_start' ], 10 );
		add_action( 'woocommerce_after_main_content', [ self::class, 'wrapper_end' ], 10 );

		// Sidebar placement is handled by the theme layout, not WooCommerce's default hook.
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	}

	public static function supports(): void {
		add_theme_support(
			'woocommerce',
			[
				'thumbnail_image_width' => 600,
				'single_image_width'    => 900,
				'product_grid'          => [
					'default_columns' => 5,
					'min_columns'     => 2,
					'max_columns'     => 6,
				],
			]
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}

	public static function columns(): int {
		return max( 1, min( 6, (int) webgram_option_device( 'shop_columns', 'desktop' ) ) );
	}

	public static function related_args( array $args ): array {
		$n                      = (int) webgram_option( 'product_related_count' );
		$args['posts_per_page'] = $n;
		$args['columns']        = min( 5, max( 1, $n ) );
		return $args;
	}

	public static function wrapper_start(): void {
		echo '<div class="wg-container"><div class="' . esc_attr( webgram_content_classes() ) . '"><main id="primary" class="wg-main">';
	}

	public static function wrapper_end(): void {
		echo '</main>';
		if ( class_exists( 'Webgram_WC_Shop' ) && Webgram_WC_Shop::is_archive() ) {
			Webgram_WC_Shop::filters();
		} elseif ( in_array( webgram_layout(), [ 'sidebar-left', 'sidebar-right' ], true ) && is_active_sidebar( 'sidebar-shop' ) ) {
			echo '<aside class="wg-sidebar wg-sidebar--shop">';
			dynamic_sidebar( 'sidebar-shop' );
			echo '</aside>';
		}
		echo '</div></div>';
	}
}

Webgram_WC_Setup::init();
