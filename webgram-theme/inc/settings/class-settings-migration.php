<?php
/**
 * One-time migration from Phase 0 Customizer theme_mods to the webgram_theme_settings option.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Settings_Migration {

	public const FLAG = 'webgram_settings_migrated';

	/** Phase 0 keys that map 1:1, plus renamed keys. */
	public static function map(): array {
		$same = [
			'color_primary', 'color_primary_hover', 'color_secondary', 'color_accent', 'color_text', 'color_text_muted', 'color_heading',
			'color_bg', 'color_bg_alt', 'color_surface', 'color_border', 'color_success', 'color_warning', 'color_danger', 'color_price',
			'color_sale', 'color_star', 'color_topbar_bg', 'color_topbar_text', 'color_header_bg', 'color_header_text', 'color_footer_bg',
			'color_footer_text', 'font_source', 'font_body', 'font_heading', 'heading_weight', 'heading_letter_spacing', 'container_width',
			'radius_scale', 'button_radius', 'button_style', 'shop_layout', 'blog_layout', 'footer_columns', 'footer_copyright',
			'product_card_style', 'category_card_shape',
		];
		$map = array_combine( $same, $same );
		$map['header_sticky'] = 'sticky_enabled';
		return $map;
	}

	/**
	 * @param array<string, mixed> $mods theme mods (key => value)
	 * @return array<string, mixed> values for the new option
	 */
	public static function convert( array $mods ): array {
		$out = [];
		foreach ( self::map() as $old => $new ) {
			if ( array_key_exists( $old, $mods ) ) {
				$out[ $new ] = $mods[ $old ];
			}
		}
		if ( isset( $mods['font_size_base'] ) && is_numeric( $mods['font_size_base'] ) ) {
			$size                  = (int) $mods['font_size_base'];
			$out['font_size_base'] = [ 'desktop' => $size, 'tablet' => $size, 'mobile' => max( 14, $size - 1 ) ];
		}
		if ( isset( $mods['shop_columns'] ) && is_numeric( $mods['shop_columns'] ) ) {
			$out['shop_columns'] = [ 'desktop' => (int) $mods['shop_columns'], 'tablet' => 3, 'mobile' => 2 ];
		}
		return $out;
	}

	public static function maybe_run(): void {
		if ( get_option( self::FLAG ) ) {
			return;
		}
		$mods = get_theme_mods();
		if ( is_array( $mods ) && false === get_option( Webgram_Settings::OPTION ) ) {
			$values = self::convert( $mods );
			if ( $values ) {
				$fields = Webgram_Settings::instance()->theme_fields();
				Webgram_Settings::instance()->update( Webgram_Settings_Sanitizer::sanitize_all( $fields, $values ) );
			}
		}
		update_option( self::FLAG, WEBGRAM_VERSION, false );
	}
}

add_action( 'admin_init', [ 'Webgram_Settings_Migration', 'maybe_run' ] );
add_action( 'after_switch_theme', [ 'Webgram_Settings_Migration', 'maybe_run' ] );
