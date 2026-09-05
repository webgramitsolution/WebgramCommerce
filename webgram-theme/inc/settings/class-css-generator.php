<?php
/**
 * Turns Theme Settings into CSS custom properties plus responsive overrides and custom CSS. Output is cached in a
 * transient and printed inline after main.css so it overrides the compiled fallback tokens.
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

	/** setting id => CSS variable name for direct 1:1 color mappings. */
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

	/** @return array<string, string> variable name (without leading --) => value, desktop values */
	public function tokens(): array {
		$t = [];

		foreach ( $this->token_map() as $key => $var ) {
			if ( str_starts_with( $key, 'color_' ) ) {
				$value = Webgram_Settings_Sanitizer::color( (string) webgram_option( $key ) );
				if ( '' !== $value ) {
					$t[ ltrim( $var, '-' ) ] = $value;
				}
			}
		}

		$t['wg-container-max'] = (int) webgram_option( 'container_width' ) . 'px';
		$t['wg-btn-radius']    = (int) webgram_option( 'button_radius' ) . 'px';

		$factor = match ( (string) webgram_option( 'spacing_scale' ) ) {
			'compact' => 0.85,
			'relaxed' => 1.2,
			default   => 1.0,
		};
		foreach ( [ 1 => 4, 2 => 8, 3 => 12, 4 => 16, 5 => 20, 6 => 24, 8 => 32, 10 => 40, 12 => 48, 16 => 64 ] as $step => $px ) {
			$t[ 'wg-space-' . $step ] = (string) round( $px * $factor ) . 'px';
		}
		$t['wg-section-gap']  = (int) webgram_option_device( 'section_gap' ) . 'px';
		$t['wg-border-width'] = max( 0, min( 3, (int) webgram_option( 'border_width' ) ) ) . 'px';

		$radius = match ( (string) webgram_option( 'radius_scale' ) ) {
			'sharp'   => [ '0', '0', '0', '0' ],
			'soft'    => [ '2px', '4px', '6px', '8px' ],
			'pill'    => [ '8px', '14px', '20px', '28px' ],
			default   => [ '4px', '8px', '12px', '16px' ],
		};
		[ $t['wg-radius-sm'], $t['wg-radius-md'], $t['wg-radius-lg'], $t['wg-radius-xl'] ] = $radius;

		$shadow = match ( (string) webgram_option( 'shadow_scale' ) ) {
			'none'   => [ 'none', 'none', 'none' ],
			'soft'   => [ '0 1px 2px rgba(17,24,39,0.04)', '0 4px 12px rgba(17,24,39,0.05)', '0 10px 30px rgba(17,24,39,0.08)' ],
			'strong' => [ '0 1px 3px rgba(17,24,39,0.1)', '0 8px 24px rgba(17,24,39,0.14)', '0 20px 50px rgba(17,24,39,0.2)' ],
			default  => [ '0 1px 2px rgba(17,24,39,0.06)', '0 6px 18px rgba(17,24,39,0.08)', '0 16px 40px rgba(17,24,39,0.12)' ],
		};
		[ $t['wg-shadow-sm'], $t['wg-shadow-md'], $t['wg-shadow-lg'] ] = $shadow;

		$t['wg-font-body']    = $this->font_stack( (string) webgram_option( 'font_body' ) );
		$t['wg-font-heading'] = $this->font_stack( (string) webgram_option( 'font_heading' ) );
		$t['wg-font-menu']    = 'inherit' === webgram_option( 'font_menu' ) ? 'var(--wg-font-body)' : $this->font_stack( (string) webgram_option( 'font_menu' ) );
		$t['wg-font-button']  = 'inherit' === webgram_option( 'font_button' ) ? 'var(--wg-font-body)' : $this->font_stack( (string) webgram_option( 'font_button' ) );

		$t['wg-font-size-base'] = webgram_option_device( 'font_size_base' ) . 'px';
		$t['wg-lh-normal']      = (string) (float) webgram_option( 'body_line_height' );
		$t['wg-fw-heading']     = (string) (int) webgram_option( 'heading_weight' );
		$t['wg-ls-heading']     = (float) webgram_option( 'heading_letter_spacing' ) . 'em';
		$t['wg-lh-heading']     = (string) (float) webgram_option( 'heading_line_height' );
		foreach ( [ 1, 2, 3, 4, 5, 6 ] as $level ) {
			$t[ 'wg-fs-h' . $level ] = webgram_option_device( 'font_size_h' . $level ) . 'px';
		}
		$t['wg-menu-font-size']      = (int) webgram_option( 'menu_font_size' ) . 'px';
		$t['wg-menu-font-weight']    = (string) (int) webgram_option( 'menu_font_weight' );
		$t['wg-menu-letter-spacing'] = (float) webgram_option( 'menu_letter_spacing' ) . 'em';
		$t['wg-btn-font-weight']     = (string) (int) webgram_option( 'button_font_weight' );
		$t['wg-btn-letter-spacing']  = (float) webgram_option( 'button_letter_spacing' ) . 'em';
		$t['wg-btn-transform']       = webgram_option( 'button_uppercase' ) ? 'uppercase' : 'none';
		$t['wg-sidebar-width']       = (int) webgram_option( 'sidebar_width' ) . '%';
		$t['wg-boxed-bg']            = Webgram_Settings_Sanitizer::color( (string) webgram_option( 'boxed_bg' ) ) ?: '#f3f4f6';
		$t['wg-preloader-color']     = Webgram_Settings_Sanitizer::color( (string) webgram_option( 'preloader_color' ) ) ?: 'var(--wg-color-primary)';
		$t['wg-color-primary-contrast'] = '#ffffff';

		return (array) apply_filters( 'webgram/tokens', $t );
	}

	/** Device overrides for dimension fields. */
	private function device_tokens( string $device ): array {
		$t                      = [];
		$t['wg-font-size-base'] = webgram_option_device( 'font_size_base', $device ) . 'px';
		foreach ( [ 1, 2, 3, 4, 5, 6 ] as $level ) {
			$t[ 'wg-fs-h' . $level ] = webgram_option_device( 'font_size_h' . $level, $device ) . 'px';
		}
		$t['wg-section-gap'] = (int) webgram_option_device( 'section_gap', $device ) . 'px';
		return (array) apply_filters( 'webgram/tokens_' . $device, $t );
	}

	private function block( array $tokens ): string {
		$lines = [];
		foreach ( $tokens as $name => $value ) {
			$name  = preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $name ) );
			$value = wp_strip_all_tags( (string) $value );
			if ( '' !== $name && '' !== $value && ! str_contains( $value, '</' ) && ! str_contains( $value, ';' ) ) {
				$lines[] = '--' . $name . ':' . $value;
			}
		}
		return ':root{' . implode( ';', $lines ) . '}';
	}

	private function build(): string {
		$css  = $this->block( $this->tokens() );
		$css .= '@media(max-width:991.98px){' . $this->block( $this->device_tokens( 'tablet' ) ) . '}';
		$css .= '@media(max-width:767.98px){' . $this->block( $this->device_tokens( 'mobile' ) ) . '}';

		$custom_url = (string) webgram_option( 'font_custom_url' );
		$custom     = (string) webgram_option( 'font_custom_name' );
		if ( $custom && $custom_url && str_ends_with( strtolower( $custom_url ), '.woff2' ) ) {
			$css .= '@font-face{font-family:"' . $this->font_name( $custom ) . '";src:url(' . esc_url( $custom_url ) . ') format("woff2");font-display:swap}';
		}

		$css .= Webgram_Settings_Sanitizer::code( (string) webgram_option( 'custom_css' ), 'css' );
		$tablet = Webgram_Settings_Sanitizer::code( (string) webgram_option( 'custom_css_tablet' ), 'css' );
		$mobile = Webgram_Settings_Sanitizer::code( (string) webgram_option( 'custom_css_mobile' ), 'css' );
		if ( '' !== trim( $tablet ) ) {
			$css .= '@media(max-width:991.98px){' . $tablet . '}';
		}
		if ( '' !== trim( $mobile ) ) {
			$css .= '@media(max-width:767.98px){' . $mobile . '}';
		}

		return (string) apply_filters( 'webgram/generated_css', $css );
	}

	private function font_name( string $family ): string {
		return preg_replace( '/[^A-Za-z0-9 \-]/', '', $family ) ?? '';
	}

	private function font_stack( string $family ): string {
		if ( 'custom' === $family ) {
			$family = (string) webgram_option( 'font_custom_name' );
		}
		if ( 'system' === $family || '' === $family || 'inherit' === $family ) {
			return 'system-ui,-apple-system,"Segoe UI",Roboto,sans-serif';
		}
		$serif = in_array( $family, [ 'Playfair Display' ], true );
		return '"' . $this->font_name( $family ) . '",' . ( $serif ? 'Georgia,serif' : 'system-ui,sans-serif' );
	}
}
