<?php
/**
 * Elements: Cart (count badge, drawer trigger) and Account (hover dropdown).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Cart extends Webgram_Element {

	public function id(): string {
		return 'cart';
	}

	public function label(): string {
		return __( 'Cart', 'webgram' );
	}

	public function icon(): string {
		return 'cart';
	}

	public function group(): string {
		return 'actions';
	}

	public function is_available(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function settings_fields(): array {
		return $this->icon_fields( __( 'Cart', 'webgram' ), 'cart' ) + [
			'behavior'   => [ 'label' => __( 'Click opens', 'webgram' ), 'type' => 'radio', 'choices' => [ 'drawer' => __( 'Cart drawer', 'webgram' ), 'page' => __( 'Cart page', 'webgram' ) ], 'default' => 'drawer' ],
			'show_count' => [ 'label' => __( 'Item count badge', 'webgram' ), 'type' => 'switch', 'default' => true ],
			'show_total' => [ 'label' => __( 'Show subtotal next to icon', 'webgram' ), 'type' => 'switch', 'default' => false ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			return;
		}
		$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		$extra = '';
		if ( ! empty( $settings['show_count'] ) ) {
			$extra .= '<span class="wg-icon-btn__count wg-cart-count" data-count="' . esc_attr( (string) $count ) . '">' . esc_html( (string) $count ) . '</span>';
		}
		if ( ! empty( $settings['show_total'] ) && WC()->cart ) {
			$extra .= '<span class="wg-icon-btn__total wg-cart-total">' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</span>';
		}
		$attrs = [ 'class' => 'wg-header__cart' ];
		if ( 'drawer' === $settings['behavior'] ) {
			$attrs['data-wg-toggle']  = 'slide-cart';
			$attrs['aria-controls']   = 'wg-slide-cart';
		}
		$this->icon_link( wc_get_cart_url(), 'cart', (string) $settings['label'], $settings, $attrs, $extra );
	}
}

final class Webgram_Element_Account extends Webgram_Element {

	public function id(): string {
		return 'account';
	}

	public function label(): string {
		return __( 'Account', 'webgram' );
	}

	public function icon(): string {
		return 'user';
	}

	public function group(): string {
		return 'actions';
	}

	public function settings_fields(): array {
		return $this->icon_fields( __( 'Account', 'webgram' ), 'user' ) + [
			'dropdown'        => [ 'label' => __( 'Hover dropdown with account links', 'webgram' ), 'type' => 'switch', 'default' => true ],
			'label_logged_in' => [ 'label' => __( 'Label when logged in', 'webgram' ), 'type' => 'text', 'default' => __( 'Account', 'webgram' ) ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$logged = is_user_logged_in();
		$url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : ( $logged ? admin_url( 'profile.php' ) : wp_login_url() );
		$label  = $logged ? (string) $settings['label_logged_in'] : (string) $settings['label'];
		$links  = [];
		if ( $logged ) {
			if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
				$links[ wc_get_account_endpoint_url( 'dashboard' ) ] = __( 'Dashboard', 'webgram' );
				$links[ wc_get_account_endpoint_url( 'orders' ) ]    = __( 'Orders', 'webgram' );
				$links[ wc_get_account_endpoint_url( 'edit-address' ) ] = __( 'Addresses', 'webgram' );
			}
			$links = (array) apply_filters( 'webgram/header/account_links', $links );
			$links[ wp_logout_url( home_url( '/' ) ) ] = __( 'Logout', 'webgram' );
		} else {
			$links[ $url ] = __( 'Login', 'webgram' );
			if ( get_option( 'users_can_register' ) || ( function_exists( 'wc_registration_enabled' ) && wc_registration_enabled() ) ) {
				$links[ add_query_arg( 'action', 'register', $url ) ] = __( 'Register', 'webgram' );
			}
			$links = (array) apply_filters( 'webgram/header/account_links', $links );
		}

		$dropdown = ! empty( $settings['dropdown'] ) && 'desktop' === $device;
		echo '<div class="wg-account' . ( $dropdown ? ' wg-account--dropdown' : '' ) . '" data-wg-component="account">';
		$this->icon_link( $url, 'user', $label, $settings, $dropdown ? [ 'aria-haspopup' => 'true', 'aria-expanded' => 'false' ] : [], $dropdown ? webgram_icon( 'chevron-down', 'wg-account__chevron', false ) : '' );
		if ( $dropdown ) {
			echo '<div class="wg-account__menu"><ul>';
			foreach ( $links as $href => $text ) {
				printf( '<li><a href="%s">%s</a></li>', esc_url( (string) $href ), esc_html( (string) $text ) );
			}
			echo '</ul></div>';
		}
		echo '</div>';
	}
}
