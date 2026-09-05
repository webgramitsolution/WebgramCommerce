<?php
/**
 * Slide cart drawer and cart page (spec 4.8): fragments, quantity and remove endpoint, open after add to cart,
 * cross-sells count, empty cart suggestions. Hooks for Core: webgram/cart/before_items, after_items, before_totals,
 * after_totals, before_checkout_button.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Cart {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'webgram/after_page', [ self::class, 'drawer' ], 15 );
		add_filter( 'woocommerce_add_to_cart_fragments', [ self::class, 'fragments' ] );
		add_action( 'wc_ajax_webgram_cart_update', [ self::class, 'ajax_update' ] );
		add_filter( 'woocommerce_cross_sells_total', [ self::class, 'cross_sells_total' ] );
		add_filter( 'woocommerce_cross_sells_columns', static fn() => 4 );
		add_action( 'woocommerce_cart_is_empty', [ self::class, 'empty_suggestions' ], 20 );
		add_filter( 'woocommerce_add_to_cart_redirect', [ self::class, 'no_redirect_when_drawer' ], 5 );
	}

	public static function enabled(): bool {
		return (bool) webgram_option( 'cart_drawer' ) && ! is_cart() && ! is_checkout();
	}

	public static function drawer(): void {
		if ( ! self::enabled() ) {
			return;
		}
		webgram_part( 'cart/slide-cart' );
	}

	/** Drawer body and header count are fragments so every add-to-cart refreshes them. */
	public static function fragments( array $fragments ): array {
		if ( ! WC()->cart ) {
			return $fragments;
		}
		$count = WC()->cart->get_cart_contents_count();
		$fragments['.wg-cart-count'] = '<span class="wg-icon-btn__count wg-cart-count" data-count="' . esc_attr( (string) $count ) . '">' . esc_html( (string) $count ) . '</span>';
		if ( webgram_option( 'cart_drawer' ) ) {
			ob_start();
			webgram_part( 'cart/drawer-content' );
			$fragments['[data-wg-drawer-content]'] = (string) ob_get_clean();
		}
		return $fragments;
	}

	/** Quantity change or removal from the drawer; returns fresh fragments. */
	public static function ajax_update(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'webgram_nonce' ) ) {
			wp_send_json( [ 'success' => false, 'message' => __( 'Session expired, please reload the page.', 'webgram' ) ], 403 );
		}
		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$qty = isset( $_POST['qty'] ) ? (float) $_POST['qty'] : -1;
		if ( '' === $key || ! WC()->cart || ! WC()->cart->get_cart_item( $key ) ) {
			wp_send_json( [ 'success' => false, 'message' => __( 'Item not found in cart.', 'webgram' ) ], 404 );
		}
		if ( $qty <= 0 ) {
			WC()->cart->remove_cart_item( $key );
		} else {
			WC()->cart->set_quantity( $key, $qty, true );
		}
		WC()->cart->calculate_totals();
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();
		wp_send_json(
			[
				'success'   => true,
				'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
				'cart_hash' => WC()->cart->get_cart_hash(),
				'count'     => WC()->cart->get_cart_contents_count(),
				'message'   => $notices ? wp_strip_all_tags( (string) ( $notices[0]['notice'] ?? '' ) ) : '',
			]
		);
	}

	/**
	 * Total savings in the cart: regular minus sale price on every line plus applied discounts.
	 */
	public static function savings(): float {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0.0;
		}
		$saved = 0.0;
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
				continue;
			}
			$regular = (float) $product->get_regular_price();
			$price   = (float) $product->get_price();
			if ( $regular > $price ) {
				$saved += ( $regular - $price ) * (int) $item['quantity'];
			}
		}
		$saved += (float) WC()->cart->get_discount_total();
		return round( (float) apply_filters( 'webgram/cart/savings', $saved ), wc_get_price_decimals() );
	}

	public static function cross_sells_total(): int {
		return (int) webgram_option( 'cart_cross_sells' );
	}

	/** Suggested products under the empty cart message. */
	public static function empty_suggestions(): void {
		$n = (int) webgram_option( 'cart_empty_products' );
		if ( $n < 1 ) {
			return;
		}
		$products = (array) apply_filters( 'webgram/cart/empty_products', wc_get_products( [ 'limit' => $n, 'status' => 'publish', 'orderby' => 'popularity', 'visibility' => 'catalog' ] ), $n );
		if ( ! $products ) {
			return;
		}
		echo '<section class="wg-cart-empty__products wg-section-ornament"><h2>' . esc_html__( 'You may like', 'webgram' ) . '</h2>';
		wc_set_loop_prop( 'columns', min( 5, count( $products ) ) );
		woocommerce_product_loop_start();
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product && $product->is_visible() ) {
				$GLOBALS['post'] = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $GLOBALS['post'] );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
		wp_reset_postdata();
		wc_reset_loop();
		echo '</section>';
	}

	/** With the drawer on, WooCommerce's "redirect to cart after add" setting is ignored so the drawer can open. */
	public static function no_redirect_when_drawer( $url ) {
		if ( is_string( $url ) && '' !== $url && webgram_option( 'cart_drawer' ) && 'drawer' === webgram_option( 'cart_after_add' ) && empty( $_REQUEST['webgram_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}
		return $url;
	}

	/** Compact line items used by the drawer template. */
	public static function items(): array {
		$items = [];
		if ( ! WC()->cart ) {
			return $items;
		}
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['data'] ?? null;
			if ( ! $product instanceof WC_Product || ! $product->exists() || $item['quantity'] <= 0 ) {
				continue;
			}
			if ( ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) {
				continue;
			}
			$items[] = [
				'key'       => $key,
				'product'   => $product,
				'name'      => apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $item, $key ),
				'url'       => $product->is_visible() ? $product->get_permalink( $item ) : '',
				'image'     => apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'webgram-thumb', [ 'loading' => 'lazy' ] ), $item, $key ),
				'price'     => apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $item, $key ),
				'subtotal'  => apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $item['quantity'] ), $item, $key ),
				'quantity'  => (float) $item['quantity'],
				'max'       => $product->get_max_purchase_quantity(),
				'sold_individually' => $product->is_sold_individually(),
				'meta'      => wc_get_formatted_cart_item_data( $item ),
			];
		}
		return $items;
	}
}

Webgram_WC_Cart::init();
