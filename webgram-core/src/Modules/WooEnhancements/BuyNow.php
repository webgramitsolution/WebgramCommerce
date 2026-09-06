<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Buy Now: a second submit on the product form and a card button that add the product (optionally after emptying
 * the cart) and redirect to checkout. Standard WooCommerce add-to-cart validation still runs.
 */
final class BuyNow {

	public const FLAG = 'webgram_buy_now';

	public function __construct( private Module $module ) {}

	public function register(): void {
		if ( ! Helpers::bool( $this->module->settings()->get( 'buy_now_enabled', true ) ) ) {
			return;
		}
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'button' ] );
		add_action( 'webgram/product_card/buy_now', [ $this, 'card_button' ] );
		add_action( 'wp_loaded', [ $this, 'maybe_empty_cart' ], 19 );
		add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'redirect' ], 20, 2 );
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'fragments_flag' ] );
	}

	public function is_buy_now_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		return ! empty( $_REQUEST[ self::FLAG ] );
	}

	public function label(): string {
		return (string) $this->module->settings()->get( 'buy_now_label', __( 'Buy now', 'webgram-core' ) );
	}

	public function button(): void {
		global $product;
		if ( ! $product instanceof \WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() || $product->is_type( 'external' ) || $product->is_type( 'grouped' ) ) {
			return;
		}
		printf(
			'<button type="submit" name="%s" value="1" class="%s">%s<span>%s</span></button>',
			esc_attr( self::FLAG ),
			esc_attr( Helpers::css_class( 'buy-now', 'button wgc-btn wg-btn wg-btn--secondary' ) ),
			'<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>',
			esc_html( $this->label() )
		);
	}

	/** Card button: URL that adds the product and lands on checkout. Variation ids are appended by the theme JS. */
	public function card_button( \WC_Product $product ): void {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() || $product->is_type( 'external' ) || $product->is_type( 'grouped' ) ) {
			return;
		}
		$url = add_query_arg( [ 'add-to-cart' => $product->get_id(), self::FLAG => 1 ], wc_get_checkout_url() );
		printf(
			'<a class="%s" href="%s" data-buy-now-url="%s" rel="nofollow">%s</a>',
			esc_attr( Helpers::css_class( 'buy-now', 'wg-btn wg-btn--primary wg-card__buy' ) ),
			esc_url( $url ),
			esc_url( $url ),
			esc_html( $this->label() )
		);
	}

	public function maybe_empty_cart(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $this->is_buy_now_request() || empty( $_REQUEST['add-to-cart'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		if ( Helpers::bool( $this->module->settings()->get( 'buy_now_empty_cart', false ) ) ) {
			WC()->cart->empty_cart();
		}
	}

	public function redirect( string $url, $product = null ): string {
		if ( $this->is_buy_now_request() && function_exists( 'wc_get_checkout_url' ) && ! wc_notice_count( 'error' ) ) {
			return wc_get_checkout_url();
		}
		return $url;
	}

	public function fragments_flag( array $fragments ): array {
		return $fragments;
	}
}
