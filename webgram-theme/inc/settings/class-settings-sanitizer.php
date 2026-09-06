<?php
/**
 * Sanitizes Theme Settings values by field type. Pure functions so the harness can exercise them without WordPress
 * beyond the standard sanitize_* helpers.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Settings_Sanitizer {

	public const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * @param array<string, array> $fields id => field definition
	 * @param array<string, mixed> $input  raw input (already unslashed)
	 * @return array<string, mixed> sanitized values for known fields only
	 */
	public static function sanitize_all( array $fields, array $input ): array {
		$out = [];
		foreach ( $fields as $id => $field ) {
			$type = $field['type'] ?? 'text';
			if ( in_array( $type, [ 'heading', 'link', 'info' ], true ) ) {
				continue;
			}
			$present = array_key_exists( $id, $input );
			if ( ! $present && 'switch' !== $type && 'multicheck' !== $type && 'sortable' !== $type ) {
				continue; // Untouched field (e.g. hidden by dependency): keep stored value.
			}
			$out[ $id ] = self::sanitize( $field, $input[ $id ] ?? null );
		}
		return $out;
	}

	public static function sanitize( array $field, mixed $value ): mixed {
		$type = $field['type'] ?? 'text';

		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
			return call_user_func( $field['sanitize'], $value, $field );
		}

		switch ( $type ) {
			case 'switch':
				return in_array( $value, [ 1, '1', true, 'true', 'on', 'yes' ], true );

			case 'number':
			case 'range':
				return self::number( $value, $field );

			case 'select':
			case 'radio':
			case 'radio_image':
			case 'icon':
				$choices = (array) ( $field['choices'] ?? [] );
				$value   = is_scalar( $value ) ? (string) $value : '';
				if ( $choices && ! array_key_exists( $value, $choices ) ) {
					return (string) ( $field['default'] ?? array_key_first( $choices ) );
				}
				return sanitize_text_field( $value );

			case 'multicheck':
				$choices = (array) ( $field['choices'] ?? [] );
				$value   = array_map( 'strval', (array) $value );
				return array_values( array_filter( $value, static fn( $v ) => array_key_exists( $v, $choices ) ) );

			case 'sortable':
				$items = (array) ( $field['items'] ?? $field['choices'] ?? [] );
				$value = array_map( 'strval', (array) $value );
				return array_values( array_unique( array_filter( $value, static fn( $v ) => array_key_exists( $v, $items ) ) ) );

			case 'color':
				return self::color( (string) $value );

			case 'image':
			case 'page':
			case 'html_block':
			case 'menu':
				return absint( $value );

			case 'url':
				return esc_url_raw( (string) $value );

			case 'email':
				return sanitize_email( (string) $value );

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			case 'html':
				return wp_kses_post( (string) $value );

			case 'code':
				return self::code( (string) $value, (string) ( $field['language'] ?? 'css' ) );

			case 'dimensions':
				return self::dimensions( $value, $field );

			case 'typography':
				return self::typography( $value, $field );

			case 'repeater':
				return self::repeater( $value, $field );

			case 'secret':
				return sanitize_text_field( (string) $value );

			case 'text':
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	public static function number( mixed $value, array $field ): int|float {
		$value = is_numeric( $value ) ? $value + 0 : ( $field['default'] ?? 0 );
		if ( isset( $field['min'] ) && $value < $field['min'] ) {
			$value = $field['min'];
		}
		if ( isset( $field['max'] ) && $value > $field['max'] ) {
			$value = $field['max'];
		}
		$step = $field['step'] ?? 1;
		return ( is_float( $step ) || ( is_string( $step ) && str_contains( $step, '.' ) ) ) ? (float) $value : (int) $value;
	}

	/** Hex or rgba() colors only. Anything else becomes an empty string. */
	public static function color( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+)\s*)?\)$/', $value ) ) {
			return preg_replace( '/\s+/', '', $value );
		}
		return '';
	}

	/** Custom CSS/JS: strip closing tags that could break out of the print context. JS storage is guarded by capability elsewhere. */
	public static function code( string $value, string $language ): string {
		$value = str_replace( "\0", '', $value );
		if ( 'css' === $language ) {
			$value = wp_strip_all_tags( $value );
			$value = preg_replace( '/<\/?\s*style[^>]*>/i', '', $value ) ?? '';
		} else {
			$value = preg_replace( '/<\/\s*script\s*>/i', '', $value ) ?? '';
		}
		return $value;
	}

	/** @return array{desktop: int|float, tablet: int|float, mobile: int|float} */
	public static function dimensions( mixed $value, array $field ): array {
		$default = (array) ( $field['default'] ?? [] );
		$value   = is_array( $value ) ? $value : [];
		$out     = [];
		$last    = null;
		foreach ( self::DEVICES as $device ) {
			$raw = $value[ $device ] ?? null;
			if ( null === $raw || '' === $raw ) {
				$raw = $default[ $device ] ?? $last ?? 0;
			}
			$out[ $device ] = self::number( $raw, $field );
			$last           = $out[ $device ];
		}
		return $out;
	}

	public static function typography( mixed $value, array $field ): array {
		$value = is_array( $value ) ? $value : [];
		return [
			'family'         => sanitize_text_field( (string) ( $value['family'] ?? 'inherit' ) ),
			'weight'         => in_array( (string) ( $value['weight'] ?? '' ), [ 'inherit', '300', '400', '500', '600', '700', '800' ], true ) ? (string) $value['weight'] : 'inherit',
			'size'           => self::dimensions( $value['size'] ?? [], [ 'min' => 8, 'max' => 120, 'default' => $field['default']['size'] ?? [] ] ),
			'line_height'    => is_numeric( $value['line_height'] ?? null ) ? (float) $value['line_height'] : 0,
			'letter_spacing' => is_numeric( $value['letter_spacing'] ?? null ) ? (float) $value['letter_spacing'] : 0,
			'transform'      => in_array( (string) ( $value['transform'] ?? '' ), [ 'none', 'uppercase', 'capitalize' ], true ) ? (string) $value['transform'] : 'none',
		];
	}

	public static function repeater( mixed $value, array $field ): array {
		$subfields = (array) ( $field['fields'] ?? [] );
		$max       = (int) ( $field['max'] ?? 50 );
		$rows      = [];
		if ( ! is_array( $value ) ) {
			return [];
		}
		foreach ( array_values( $value ) as $row ) {
			if ( ! is_array( $row ) || count( $rows ) >= $max ) {
				continue;
			}
			$clean = [];
			foreach ( $subfields as $sid => $sub ) {
				$sub['id']     = $sid;
				$clean[ $sid ] = self::sanitize( $sub, $row[ $sid ] ?? ( $sub['default'] ?? null ) );
			}
			$rows[] = $clean;
		}
		return $rows;
	}
}
