<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

final class Helpers {

	/** Normalizes a phone number to E.164. Returns '' when it cannot be made valid. */
	public static function to_e164( string $phone, string $default_country_code = '' ): string {
		$digits = preg_replace( '/[^0-9+]/', '', $phone ) ?? '';

		if ( '' === $digits ) {
			return '';
		}

		if ( str_starts_with( $digits, '00' ) ) {
			$digits = '+' . substr( $digits, 2 );
		}

		if ( ! str_starts_with( $digits, '+' ) ) {
			$default_country_code = ltrim( $default_country_code, '+' );
			if ( '' === $default_country_code ) {
				return '';
			}
			// Strip a single leading trunk zero (common in India, UK, many others).
			$digits = ltrim( $digits, '0' );
			$digits = '+' . $default_country_code . $digits;
		}

		$plain = substr( $digits, 1 );
		if ( strlen( $plain ) < 8 || strlen( $plain ) > 15 || ! ctype_digit( $plain ) ) {
			return '';
		}

		return $digits;
	}

	/** Country calling code for a WooCommerce country code (ISO 3166-1 alpha-2). */
	public static function calling_code( string $country ): string {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$code = WC()->countries->get_country_calling_code( strtoupper( $country ) );
			if ( is_array( $code ) ) {
				$code = reset( $code );
			}
			if ( '' !== (string) $code ) {
				return (string) $code;
			}
		}
		$fallback = [ 'IN' => '91', 'US' => '1', 'CA' => '1', 'GB' => '44', 'AE' => '971', 'SA' => '966', 'AU' => '61', 'DE' => '49', 'FR' => '33', 'SG' => '65', 'MY' => '60', 'BD' => '880', 'LK' => '94', 'NP' => '977', 'PK' => '92', 'ID' => '62', 'ZA' => '27', 'NG' => '234', 'KE' => '254', 'NZ' => '64', 'IE' => '353', 'NL' => '31', 'IT' => '39', 'ES' => '34', 'BR' => '55', 'MX' => '52', 'JP' => '81', 'CN' => '86', 'QA' => '974', 'KW' => '965', 'OM' => '968', 'BH' => '973' ];
		return $fallback[ strtoupper( $country ) ] ?? '';
	}

	public static function bool( mixed $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * CSS classes for a Core component: "wgc-{name}" always (fallback styles), plus "wg-{name}" when the active
	 * theme declared that it styles Core components, so the theme can target its own prefix.
	 */
	public static function css_class( string $name, string $extra = '' ): string {
		$classes = [ 'wgc-' . $name ];
		$support = get_theme_support( 'webgram-core' );
		if ( is_array( $support ) && ! empty( $support[0]['styles'] ) ) {
			$classes[] = 'wg-' . $name;
		}
		if ( '' !== $extra ) {
			$classes[] = $extra;
		}
		return implode( ' ', $classes );
	}

	/** Client IP hashed for rate limiting; never stored raw. */
	public static function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( hash( 'sha256', 'webgram|' . $ip . '|' . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : '' ) ), 0, 32 );
	}

	/**
	 * Transient based rate limiter. Returns true when the action is allowed.
	 */
	public static function rate_limit( string $bucket, int $max, int $window_seconds ): bool {
		$key   = 'wgc_rl_' . substr( md5( $bucket . '|' . self::ip_hash() ), 0, 24 );
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return false;
		}
		set_transient( $key, $count + 1, $window_seconds );
		return true;
	}

	/** Current device from the User-Agent, coarse: desktop|tablet|mobile. Cached pages should not depend on this. */
	public static function device(): string {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		if ( preg_match( '/ipad|tablet|kindle|playbook|silk/', $ua ) ) {
			return 'tablet';
		}
		if ( preg_match( '/mobi|android|iphone|ipod|blackberry|windows phone/', $ua ) ) {
			return 'mobile';
		}
		return 'desktop';
	}

	/** Whether we are on a WooCommerce-ish page type used by targeting rules. */
	public static function page_type(): string {
		if ( is_front_page() ) {
			return 'home';
		}
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			return 'shop';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return 'checkout';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return 'account';
		}
		if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() ) {
			return 'blog';
		}
		if ( is_page() ) {
			return 'page';
		}
		return 'other';
	}
}
