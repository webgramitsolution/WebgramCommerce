<?php
/**
 * Checkout and thank-you page hooks: coupon placement, steps header, trust text, Core hook bridges, timeline data.
 * No WooCommerce field is removed; gateways and field editors keep working.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Checkout {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'wp', [ self::class, 'hooks' ] );
		add_action( 'woocommerce_review_order_before_payment', static fn() => do_action( 'webgram/checkout/before_payment' ) );
		add_action( 'woocommerce_checkout_after_order_review', static fn() => do_action( 'webgram/checkout/after_order_review' ), 5 );
		add_action( 'woocommerce_review_order_after_submit', [ self::class, 'trust_text' ] );
		add_filter( 'woocommerce_checkout_fields', [ self::class, 'field_classes' ], 50 );
	}

	public static function hooks(): void {
		if ( ! is_checkout() || is_order_received_page() ) {
			return;
		}
		if ( 'summary' === webgram_option( 'checkout_coupon_place' ) ) {
			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
		}
	}

	/** Only adds classes and placeholders; never removes or reorders fields. */
	public static function field_classes( array $fields ): array {
		foreach ( $fields as $group => &$group_fields ) {
			foreach ( $group_fields as $key => &$field ) {
				$field['class']   = array_unique( array_merge( (array) ( $field['class'] ?? [] ), [ 'wg-checkout-field' ] ) );
				$field['input_class'] = array_unique( array_merge( (array) ( $field['input_class'] ?? [] ), [ 'wg-input' ] ) );
			}
		}
		return $fields;
	}

	public static function trust_text(): void {
		$text = (string) webgram_option( 'checkout_trust_text' );
		if ( '' !== $text ) {
			echo '<p class="wg-checkout__trust">' . webgram_icon( 'lock', '', false ) . '<span>' . esc_html( $text ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/** Steps header (purely visual). $current: cart|details|payment|done */
	public static function steps( string $current ): void {
		if ( ! webgram_option( 'checkout_steps' ) ) {
			return;
		}
		$steps = [ 'cart' => __( 'Cart', 'webgram' ), 'details' => __( 'Details', 'webgram' ), 'payment' => __( 'Payment', 'webgram' ), 'done' => __( 'Done', 'webgram' ) ];
		$keys  = array_keys( $steps );
		$pos   = (int) array_search( $current, $keys, true );
		echo '<ol class="wg-steps" aria-label="' . esc_attr__( 'Checkout progress', 'webgram' ) . '">';
		foreach ( $keys as $i => $key ) {
			$class = $i < $pos ? 'is-done' : ( $i === $pos ? 'is-current' : '' );
			$link  = 'cart' === $key ? wc_get_cart_url() : ( 'details' === $key ? wc_get_checkout_url() : '' );
			printf( '<li class="wg-steps__item %s"><span class="wg-steps__num">%d</span>%s</li>', esc_attr( $class ), (int) $i + 1, $link && $i < $pos ? '<a href="' . esc_url( $link ) . '">' . esc_html( $steps[ $key ] ) . '</a>' : '<span>' . esc_html( $steps[ $key ] ) . '</span>' );
		}
		echo '</ol>';
	}

	/**
	 * Thank-you timeline steps (pure): placed, confirmed (paid), processing, completed.
	 *
	 * @return array<int, array{label: string, done: bool, current: bool}>
	 */
	public static function timeline( string $status, bool $paid ): array {
		$steps   = [ __( 'Order placed', 'webgram' ), __( 'Payment confirmed', 'webgram' ), __( 'Processing', 'webgram' ), __( 'Completed', 'webgram' ) ];
		$reached = match ( $status ) {
			'pending', 'failed', 'cancelled' => 1,
			'on-hold'    => $paid ? 2 : 1,
			'processing' => 3,
			'completed'  => 4,
			default      => $paid ? 2 : 1,
		};
		$out = [];
		foreach ( $steps as $i => $label ) {
			$out[] = [ 'label' => $label, 'done' => $i < $reached, 'current' => $i === $reached - 1 ];
		}
		return $out;
	}
}

Webgram_WC_Checkout::init();
