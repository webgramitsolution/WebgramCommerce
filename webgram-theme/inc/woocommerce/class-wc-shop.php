<?php
/**
 * Shop archive (spec 4.5): category banner, subcategory chips, toolbar (count, sort, grid/list, columns), filters
 * sidebar or off-canvas drawer, per-device columns, pagination / load more / infinite, AJAX. Hooks only, no
 * archive-product.php override.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Shop {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'wp', [ self::class, 'hooks' ] );
		add_filter( 'loop_shop_per_page', [ self::class, 'per_page' ], 20 );
		add_filter( 'loop_shop_columns', [ self::class, 'columns' ], 20 );
		add_filter( 'webgram/product_card/args', [ self::class, 'card_args' ], 5 );
		add_filter( 'body_class', [ self::class, 'body_class' ] );
		add_filter( 'woocommerce_pagination_args', [ self::class, 'pagination_args' ] );
	}

	public static function is_archive(): bool {
		return is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );
	}

	public static function hooks(): void {
		if ( ! self::is_archive() ) {
			return;
		}
		add_filter( 'woocommerce_show_page_title', '__return_false' );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

		add_action( 'woocommerce_before_main_content', [ self::class, 'banner' ], 5 );
		add_action( 'woocommerce_before_shop_loop', [ self::class, 'subcategories' ], 12 );
		add_action( 'woocommerce_before_shop_loop', [ self::class, 'toolbar' ], 20 );
		add_action( 'woocommerce_after_shop_loop', [ self::class, 'after_loop_description' ], 20 );

		if ( 'numbers' !== webgram_option( 'shop_pagination' ) ) {
			remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
			add_action( 'woocommerce_after_shop_loop', [ self::class, 'load_more' ], 10 );
		}
		add_filter( 'woocommerce_product_loop_start', [ self::class, 'loop_start' ] );
		add_action( 'woocommerce_before_main_content', [ self::class, 'wrapper_open' ], 12 );
		add_action( 'woocommerce_after_main_content', [ self::class, 'wrapper_close' ], 8 );
	}

	public static function per_page( int $n ): int {
		return max( 1, (int) webgram_option( 'shop_per_page' ) ) ?: $n;
	}

	public static function columns(): int {
		return max( 1, min( 6, (int) webgram_option_device( 'shop_columns', 'desktop' ) ) );
	}

	/** Grid / list view is remembered in a cookie so the loop renders the right card variant. */
	public static function view(): string {
		if ( ! webgram_option( 'shop_grid_list_toggle' ) ) {
			return 'grid';
		}
		$cookie = isset( $_COOKIE['wg_shop_view'] ) ? sanitize_key( wp_unslash( $_COOKIE['wg_shop_view'] ) ) : '';
		return 'list' === $cookie ? 'list' : 'grid';
	}

	public static function card_args( array $args ): array {
		if ( self::is_archive() && in_the_loop() && 'list' === self::view() ) {
			$args['variant'] = 'list';
		}
		return $args;
	}

	public static function body_class( array $classes ): array {
		if ( class_exists( 'WooCommerce' ) && self::is_archive() ) {
			$classes[] = 'wg-shop-view-' . self::view();
			$classes[] = 'wg-shop-filters-' . sanitize_html_class( (string) webgram_option( 'shop_filters' ) );
		}
		return $classes;
	}

	/** Category banner as the page title band with the term image as background. */
	public static function banner(): void {
		if ( ! webgram_option( 'archive_banner' ) ) {
			return;
		}
		$title = woocommerce_page_title( false );
		$image = 0;
		$desc  = '';
		if ( is_product_taxonomy() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$image = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				$desc  = 'top' === webgram_option( 'archive_description' ) ? term_description() : '';
			}
		} elseif ( is_shop() && 'top' === webgram_option( 'archive_description' ) ) {
			$shop = wc_get_page_id( 'shop' );
			$desc = $shop > 0 ? apply_filters( 'the_content', get_post_field( 'post_content', $shop ) ) : '';
		}
		add_filter( 'webgram/setting/page_title_size', [ self::class, 'banner_size' ] );
		webgram_part( 'misc/page-title', [ 'title' => $title, 'description' => $desc, 'image' => $image ] );
		remove_filter( 'webgram/setting/page_title_size', [ self::class, 'banner_size' ] );
	}

	public static function banner_size(): string {
		return (string) webgram_option( 'archive_banner_height' );
	}

	public static function after_loop_description(): void {
		if ( 'bottom' !== webgram_option( 'archive_description' ) ) {
			return;
		}
		echo '<div class="wg-shop__description wg-prose">';
		if ( is_product_taxonomy() ) {
			echo wp_kses_post( term_description() );
		} elseif ( is_shop() ) {
			$shop = wc_get_page_id( 'shop' );
			echo $shop > 0 ? wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $shop ) ) ) : '';
		}
		echo '</div>';
	}

	public static function subcategories(): void {
		if ( ! webgram_option( 'subcategory_chips' ) || is_search() || is_paged() ) {
			return;
		}
		$parent = 0;
		if ( is_product_category() ) {
			$term = get_queried_object();
			$parent = $term instanceof WP_Term ? (int) $term->term_id : 0;
		} elseif ( ! is_shop() ) {
			return;
		}
		$terms = get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $parent, 'hide_empty' => true, 'number' => 24 ] );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}
		webgram_part( 'shop/subcategories', [ 'terms' => $terms, 'shape' => (string) webgram_option( 'category_card_shape' ) ] );
	}

	public static function toolbar(): void {
		if ( ! webgram_option( 'shop_toolbar' ) ) {
			return;
		}
		webgram_part( 'shop/toolbar', [ 'view' => self::view(), 'columns' => self::columns() ] );
	}

	public static function loop_start( string $html ): string {
		$style = sprintf( '--wg-cols-desktop:%d;--wg-cols-tablet:%d;--wg-cols-mobile:%d', self::columns(), max( 1, (int) webgram_option_device( 'shop_columns', 'tablet' ) ), max( 1, (int) webgram_option_device( 'shop_columns', 'mobile' ) ) );
		return str_replace( '<ul class="products', '<ul style="' . esc_attr( $style ) . '" data-wg-products class="products wg-products wg-products--' . esc_attr( self::view() ), $html );
	}

	/** Extra wrapper around the loop area so AJAX can swap it and the filters drawer can sit beside it. */
	public static function wrapper_open(): void {
		echo '<div class="wg-shop" data-wg-component="shop" data-view="' . esc_attr( self::view() ) . '"><div class="wg-shop__main" data-wg-shop-main>';
	}

	public static function wrapper_close(): void {
		echo '</div></div>';
	}

	public static function load_more(): void {
		global $wp_query;
		if ( (int) $wp_query->max_num_pages <= (int) max( 1, $wp_query->get( 'paged' ) ) ) {
			return;
		}
		$next = get_next_posts_page_link( (int) $wp_query->max_num_pages );
		if ( ! $next ) {
			return;
		}
		printf(
			'<div class="wg-shop__more" data-wg-load-more data-mode="%s"><a class="wg-btn wg-btn--outline" href="%s" rel="next">%s</a><span class="wg-shop__more-sentinel" aria-hidden="true"></span></div>',
			esc_attr( (string) webgram_option( 'shop_pagination' ) ),
			esc_url( $next ),
			esc_html__( 'Load more', 'webgram' )
		);
	}

	public static function pagination_args( array $args ): array {
		$args['prev_text'] = webgram_icon( 'chevron-left', '', false ) . '<span class="wg-sr-only">' . esc_html__( 'Previous', 'webgram' ) . '</span>';
		$args['next_text'] = webgram_icon( 'chevron-right', '', false ) . '<span class="wg-sr-only">' . esc_html__( 'Next', 'webgram' ) . '</span>';
		return $args;
	}

	/** Filters sidebar markup (sidebar or off-canvas). Called by Webgram_WC_Setup::wrapper_end. */
	public static function filters(): void {
		if ( ! self::is_archive() || ! is_active_sidebar( 'sidebar-shop' ) && ! has_action( 'webgram/shop/filters' ) ) {
			return;
		}
		$offcanvas = 'offcanvas' === webgram_option( 'shop_filters' ) || 'full-width' === webgram_layout();
		if ( $offcanvas ) {
			echo '<div id="wg-filters" class="wg-drawer wg-drawer--left wg-drawer--filters" data-wg-component="drawer" data-wg-drawer="filters" hidden><div class="wg-drawer__head"><span class="wg-drawer__title">' . esc_html__( 'Filters', 'webgram' ) . '</span><button class="wg-icon-btn wg-icon-btn--no-label" type="button" data-wg-close="drawer">' . webgram_icon( 'close', '', false ) . '<span class="wg-sr-only">' . esc_html__( 'Close', 'webgram' ) . '</span></button></div><div class="wg-drawer__body">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<aside class="wg-sidebar wg-sidebar--shop wg-filters" data-wg-component="filters"' . ( webgram_option( 'sidebar_sticky' ) ? ' data-sticky="1"' : '' ) . '>';
		}
		do_action( 'webgram/shop/filters_before' );
		dynamic_sidebar( 'sidebar-shop' );
		do_action( 'webgram/shop/filters' );
		echo $offcanvas ? '</div></div>' : '</aside>';
	}
}

Webgram_WC_Shop::init();
