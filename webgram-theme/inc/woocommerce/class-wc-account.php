<?php
/**
 * My Account: navigation icons, dashboard cards, orders actions hook, split login/register page with Full Name and
 * Confirm Password fields validated server-side. Nothing bypasses WooCommerce's own handlers.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Account {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'woocommerce_account_dashboard', [ self::class, 'dashboard_cards' ], 5 );
		add_action( 'woocommerce_my_account_my_orders_column_order-actions', [ self::class, 'order_actions' ] );
		add_filter( 'woocommerce_registration_errors', [ self::class, 'validate_registration' ], 10, 3 );
		add_action( 'woocommerce_created_customer', [ self::class, 'save_registration' ] );
	}

	/** Endpoint => icon name; filterable. */
	public static function nav_icons(): array {
		return (array) apply_filters(
			'webgram/account/nav_icons',
			[
				'dashboard'       => 'home',
				'orders'          => 'package',
				'downloads'       => 'download',
				'edit-address'    => 'map-pin',
				'payment-methods' => 'credit-card',
				'edit-account'    => 'user',
				'customer-logout' => 'log-out',
				'wishlist'        => 'heart',
			]
		);
	}

	public static function dashboard_cards(): void {
		if ( ! webgram_option( 'account_dashboard_cards' ) ) {
			return;
		}
		$user  = wp_get_current_user();
		$cards = [
			[ 'orders', __( 'Orders', 'webgram' ), sprintf( /* translators: %d: order count */ _n( '%d order', '%d orders', wc_get_customer_order_count( $user->ID ), 'webgram' ), wc_get_customer_order_count( $user->ID ) ) ],
			[ 'edit-address', __( 'Addresses', 'webgram' ), __( 'Billing and shipping', 'webgram' ) ],
			[ 'edit-account', __( 'Account details', 'webgram' ), __( 'Name, email, password', 'webgram' ) ],
		];
		if ( wc_get_account_menu_items()['downloads'] ?? false ) {
			$cards[] = [ 'downloads', __( 'Downloads', 'webgram' ), __( 'Your digital files', 'webgram' ) ];
		}
		$cards = (array) apply_filters( 'webgram/account/dashboard_cards', $cards );
		$icons = self::nav_icons();
		echo '<div class="wg-account-cards">';
		foreach ( $cards as [ $endpoint, $title, $text ] ) {
			printf(
				'<a class="wg-account-card" href="%s">%s<span class="wg-account-card__title">%s</span><span class="wg-account-card__text">%s</span></a>',
				esc_url( wc_get_account_endpoint_url( $endpoint ) ),
				webgram_icon( (string) ( $icons[ $endpoint ] ?? 'circle' ), '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $title ),
				esc_html( $text )
			);
		}
		echo '</div>';
	}

	/** Orders table actions column: WooCommerce buttons plus the Core hook (invoice download and others). */
	public static function order_actions( WC_Order $order ): void {
		foreach ( wc_get_account_orders_actions( $order ) as $key => $action ) {
			printf( '<a href="%s" class="woocommerce-button button %s wg-btn wg-btn--sm wg-btn--outline">%s</a> ', esc_url( $action['url'] ), sanitize_html_class( $key ), esc_html( $action['name'] ) );
		}
		do_action( 'webgram/account/after_order_actions', $order );
	}

	/** Split the posted full name into first and last (pure). */
	public static function split_name( string $full ): array {
		$full  = trim( preg_replace( '/\s+/', ' ', $full ) ?? '' );
		$parts = '' === $full ? [] : explode( ' ', $full );
		$first = (string) array_shift( $parts );
		return [ $first, implode( ' ', $parts ) ];
	}

	public static function validate_registration( WP_Error $errors, string $username, string $email ): WP_Error {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies its own registration nonce before this filter runs.
		if ( ! isset( $_POST['webgram_register'] ) ) {
			return $errors;
		}
		$name = isset( $_POST['webgram_full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['webgram_full_name'] ) ) : '';
		if ( '' === $name ) {
			$errors->add( 'webgram_full_name', __( 'Please enter your full name.', 'webgram' ) );
		}
		if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$p1 = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$p2 = isset( $_POST['webgram_password2'] ) ? (string) wp_unslash( $_POST['webgram_password2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $p1 !== $p2 ) {
				$errors->add( 'webgram_password2', __( 'Passwords do not match.', 'webgram' ) );
			}
		}
		// phpcs:enable
		return $errors;
	}

	public static function save_registration( int $customer_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- runs inside WooCommerce's verified registration flow.
		if ( ! isset( $_POST['webgram_register'], $_POST['webgram_full_name'] ) ) {
			return;
		}
		[ $first, $last ] = self::split_name( sanitize_text_field( wp_unslash( $_POST['webgram_full_name'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$customer = new WC_Customer( $customer_id );
		$customer->set_first_name( $first );
		$customer->set_last_name( $last );
		$customer->set_billing_first_name( $first );
		$customer->set_billing_last_name( $last );
		$customer->set_display_name( trim( $first . ' ' . $last ) );
		$customer->save();
	}
}

Webgram_WC_Account::init();
