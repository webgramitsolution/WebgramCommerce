<?php
/**
 * Turns Customizer values into CSS custom properties. Output is cached in a transient and printed inline
 * (roughly 2 KB) after main.css so it overrides the compiled fallback tokens.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_CSS_Generator {

	private const TRANSIENT = 'webgram_token_css';
	private static ?Webgram_CSS_Generator $instance = null;

	public static function instance(): Webgram_CSS_Generator {
		return self::$instance ??= new self();
	}

	public function get_css(): string {
		if ( is_customize_preview() ) {
			return $this->build();
		}
		$css = get_transient( self::TRANSIENT );
		if ( ! is_string( $css ) ) {
			$css = $this->build();
			set_transient( self::TRANSIENT, $css, WEEK_IN_SECONDS );
		}
		return $css;
	}

	public function flush(): void {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * setting id => CSS variable name, for postMessage live preview. Only direct 1:1 mappings.
	 */
	public function token_map(): array {
		$map = [];
		foreach ( array_keys( webgram_defaults() ) as $key ) {
			if ( str_starts_with( $key, 'color_' ) ) {
				$map[ $key ] = '--wg-color-' . str_replace( '_', '-', substr( $key, 6 ) );
			}
		}
		$map['container_width'] = '--wg-container-max';
		$map['button_radius']   = '--wg-btn-radius';
		return $map;
	}

	/** @return array<string, string> variable name (without leading --) => value */
	public function tokens(): array {
		$t = [];

		foreach ( $this->token_map() as $key => $var ) {
			$value = webgram_option( $key );
			if ( str_starts_with( $key, 'color_' ) ) {
				$t[ ltrim( $var, '-' ) ] = (string) $value;
			}
		}

		$t['wg-container-max'] = (int) webgram_option( 'container_width' ) . 'px';
		$t['wg-btn-radius']    = (int) webgram_option( 'button_radius' ) . 'px';

		$radius = match ( (string) webgram_option( 'radius_scale' ) ) {
			'sharp'   => [ '0', '0', '0', '0' ],
			'soft'    => [ '2px', '4px', '6px', '8px' ],
			'pill'    => [ '8px', '14px', '20px', '28px' ],
			default   => [ '4px', '8px', '12px', '16px' ],
		};
		[ $t['wg-radius-sm'], $t['wg-radius-md'], $t['wg-radius-lg'], $t['wg-radius-xl'] ] = $radius;

		$t['wg-font-body']    = $this->font_stack( (string) webgram_option( 'font_body' ) );
		$t['wg-font-heading'] = $this->font_stack( (string) webgram_option( 'font_heading' ) );
		$t['wg-font-size-base'] = (int) webgram_option( 'font_size_base' ) . 'px';
		$t['wg-fw-heading']   = (string) (int) webgram_option( 'heading_weight' );
		$t['wg-ls-heading']   = (float) webgram_option( 'heading_letter_spacing' ) . 'em';

		// Derived: header/topbar/footer text colors already tokens; primary contrast is fixed white for v1.
		$t['wg-color-primary-contrast'] = '#ffffff';

		return (array) apply_filters( 'webgram/tokens', $t );
	}

	private function build(): string {
		$lines = [];
		foreach ( $this->tokens() as $name => $value ) {
			$name  = preg_replace( '/[^a-z0-9\-]/', '', strtolower( $name ) );
			$value = wp_strip_all_tags( (string) $value );
			if ( '' !== $name && '' !== $value && ! str_contains( $value, '</' ) ) {
				$lines[] = '--' . $name . ':' . $value;
			}
		}
		return ':root{' . implode( ';', $lines ) . '}';
	}

	private function font_stack( string $family ): string {
		if ( 'system' === $family || '' === $family ) {
			return 'system-ui,-apple-system,"Segoe UI",Roboto,sans-serif';
		}
		$serif = in_array( $family, [ 'Playfair Display' ], true );
		return '"' . $family . '",' . ( $serif ? 'Georgia,serif' : 'system-ui,sans-serif' );
	}
}
