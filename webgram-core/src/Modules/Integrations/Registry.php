<?php
namespace Webgram\Core\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Section definitions shared by Elementor widgets, Gutenberg blocks and shortcodes. Modules (and the theme) add
 * definitions through webgram_core/elementor/widgets:
 *   id => [ title, icon, category, description, controls => [ id => [ label, type, default, options, min, max, fields ] ],
 *           render => callable( array $args ): string  |  shortcode => 'tag' ]
 * Control types: text, textarea, number, switch, select, url, color, image, product, category, tag, post, repeater.
 */
final class Registry {

	public const CONTROL_TYPES = [ 'text', 'textarea', 'number', 'switch', 'select', 'url', 'color', 'image', 'product', 'category', 'tag', 'post', 'repeater', 'html' ];

	private ?array $all = null;

	/** @return array<string, array> */
	public function all(): array {
		if ( null === $this->all ) {
			$this->all = [];
			foreach ( (array) apply_filters( 'webgram_core/elementor/widgets', [] ) as $id => $def ) {
				$id = sanitize_key( (string) $id );
				if ( '' === $id || ! is_array( $def ) || ( empty( $def['render'] ) && empty( $def['shortcode'] ) ) ) {
					continue;
				}
				$def['id']       = $id;
				$def['title']    = (string) ( $def['title'] ?? ucwords( str_replace( '_', ' ', $id ) ) );
				$def['icon']     = (string) ( $def['icon'] ?? 'eicon-code' );
				$def['category'] = (string) ( $def['category'] ?? 'webgram' );
				$def['controls'] = self::normalize_controls( (array) ( $def['controls'] ?? [] ) );
				$this->all[ $id ] = $def;
			}
		}
		return $this->all;
	}

	public function get( string $id ): ?array {
		return $this->all()[ sanitize_key( $id ) ] ?? null;
	}

	/** Pure. */
	public static function normalize_controls( array $controls ): array {
		$out = [];
		foreach ( $controls as $cid => $c ) {
			$cid = sanitize_key( (string) $cid );
			if ( '' === $cid || ! is_array( $c ) ) {
				continue;
			}
			$type = in_array( $c['type'] ?? '', self::CONTROL_TYPES, true ) ? (string) $c['type'] : 'text';
			$out[ $cid ] = $c + [ 'label' => ucfirst( str_replace( '_', ' ', $cid ) ), 'type' => $type, 'default' => self::type_default( $type ) ];
			$out[ $cid ]['type'] = $type;
			if ( 'repeater' === $type ) {
				$out[ $cid ]['fields'] = self::normalize_controls( (array) ( $c['fields'] ?? [] ) );
			}
		}
		return $out;
	}

	private static function type_default( string $type ): mixed {
		return match ( $type ) {
			'number', 'image', 'product', 'post' => 0,
			'switch'   => false,
			'repeater', 'category', 'tag' => [],
			default    => '',
		};
	}

	/** Options for select-like controls; callables are resolved lazily so the widget list stays cheap. */
	public static function options( array $control ): array {
		$options = $control['options'] ?? [];
		if ( is_callable( $options ) ) {
			$options = $options();
		}
		return is_array( $options ) ? $options : [];
	}

	/**
	 * Pure: sanitize raw arguments (from Elementor settings, block attributes or shortcode atts) against controls.
	 * Unknown keys are dropped; missing keys receive defaults.
	 */
	public static function sanitize_args( array $controls, array $raw ): array {
		$out = [];
		foreach ( $controls as $cid => $c ) {
			$value = array_key_exists( $cid, $raw ) ? $raw[ $cid ] : $c['default'];
			$out[ $cid ] = self::sanitize_value( $c, $value );
		}
		return $out;
	}

	public static function sanitize_value( array $c, mixed $value ): mixed {
		switch ( $c['type'] ) {
			case 'number':
				$n = is_numeric( $value ) ? $value + 0 : (int) $c['default'];
				if ( isset( $c['min'] ) ) {
					$n = max( $c['min'], $n );
				}
				if ( isset( $c['max'] ) ) {
					$n = min( $c['max'], $n );
				}
				return $n;
			case 'switch':
				return in_array( $value, [ true, 1, '1', 'yes', 'true', 'on' ], true );
			case 'select':
				$options = self::options( $c );
				$value   = is_array( $value ) ? (string) reset( $value ) : (string) $value;
				return $options && ! array_key_exists( $value, $options ) ? (string) $c['default'] : $value;
			case 'url':
				return esc_url_raw( (string) ( is_array( $value ) ? ( $value['url'] ?? '' ) : $value ) );
			case 'color':
				return (string) sanitize_hex_color( (string) $value ) ?: '';
			case 'image':
				return absint( is_array( $value ) ? ( $value['id'] ?? 0 ) : $value );
			case 'product':
			case 'post':
				return absint( is_array( $value ) ? reset( $value ) : $value );
			case 'category':
			case 'tag':
				$list = is_array( $value ) ? $value : explode( ',', (string) $value );
				return array_values( array_filter( array_map( static fn( $v ) => sanitize_title( trim( (string) $v ) ), $list ), 'strlen' ) );
			case 'repeater':
				$rows = [];
				foreach ( (array) $value as $row ) {
					if ( is_array( $row ) ) {
						$rows[] = self::sanitize_args( (array) ( $c['fields'] ?? [] ), $row );
					}
				}
				return isset( $c['max'] ) ? array_slice( $rows, 0, (int) $c['max'] ) : $rows;
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'html':
				return wp_kses_post( (string) $value );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	public function render( string $id, array $raw ): string {
		$def = $this->get( $id );
		if ( ! $def ) {
			return '';
		}
		$args = self::sanitize_args( $def['controls'], $raw );
		$args = (array) apply_filters( 'webgram_core/section/args', $args, $id, $raw );
		if ( ! empty( $def['render'] ) && is_callable( $def['render'] ) ) {
			$html = (string) call_user_func( $def['render'], $args );
		} else {
			$atts = '';
			foreach ( $args as $k => $v ) {
				if ( is_scalar( $v ) ) {
					$atts .= ' ' . $k . '="' . esc_attr( is_bool( $v ) ? ( $v ? '1' : '0' ) : (string) $v ) . '"';
				} elseif ( is_array( $v ) && $v && ! is_array( reset( $v ) ) ) {
					$atts .= ' ' . $k . '="' . esc_attr( implode( ',', array_map( 'strval', $v ) ) ) . '"';
				}
			}
			$html = do_shortcode( '[' . (string) $def['shortcode'] . $atts . ']' );
		}
		return (string) apply_filters( 'webgram_core/section/html', $html, $id, $args );
	}
}
