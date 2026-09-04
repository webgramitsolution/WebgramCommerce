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
			return (string) $code;
		}
		return '';
	}

	public static function bool( mixed $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
