<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Declarative input sanitization. Handlers declare field => type; unknown fields are dropped.
 */
final class Sanitizer {

	/**
	 * @param array<string, mixed>  $input raw input
	 * @param array<string, string> $map   field => type
	 * @return array<string, mixed>
	 */
	public static function apply( array $input, array $map ): array {
		$out = [];
		foreach ( $map as $field => $type ) {
			$out[ $field ] = self::value( $input[ $field ] ?? null, $type );
		}
		return $out;
	}

	public static function value( mixed $value, string $type ): mixed {
		return match ( $type ) {
			'int'       => (int) $value,
			'int_list'  => array_values( array_filter( array_map( 'absint', (array) $value ) ) ),
			'float'     => (float) $value,
			'bool'      => filter_var( $value, FILTER_VALIDATE_BOOLEAN ),
			'email'     => sanitize_email( (string) $value ),
			'url'       => esc_url_raw( (string) $value ),
			'key'       => sanitize_key( (string) $value ),
			'slug'      => sanitize_title( (string) $value ),
			'textarea'  => sanitize_textarea_field( (string) $value ),
			'html'      => wp_kses_post( (string) $value ),
			'hex_color' => (string) sanitize_hex_color( (string) $value ),
			'phone'     => preg_replace( '/[^0-9+\s\-()]/', '', (string) $value ),
			'json'      => self::json( $value ),
			'raw'       => $value,
			default     => sanitize_text_field( (string) $value ),
		};
	}

	private static function json( mixed $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : [];
	}
}
