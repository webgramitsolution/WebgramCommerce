<?php
namespace Webgram\Core\Modules\Slider;

defined( 'ABSPATH' ) || exit;

/** Pure sanitizers and helpers for slider data. */
final class Slides {

	public const ALIGN      = [ 'left', 'center', 'right' ];
	public const VALIGN     = [ 'top', 'middle', 'bottom' ];
	public const ANIMATIONS = [ 'fade', 'slide', 'zoom' ];
	public const MAX_SLIDES = 12;

	public static function defaults(): array {
		return [
			'autoplay'     => true,
			'delay'        => 5000,
			'speed'        => 700,
			'loop'         => true,
			'navigation'   => true,
			'pagination'   => true,
			'height_mode'  => 'ratio',
			'height'       => 520,
			'ratio'        => '16:6',
			'ratio_mobile' => '4:5',
			'lazy'         => true,
			'effect'       => 'fade',
			'full_width'   => false,
			'pause_hover'  => true,
		];
	}

	public static function sanitize_settings( array $raw ): array {
		$d = self::defaults();
		return [
			'autoplay'     => ! empty( $raw['autoplay'] ),
			'delay'        => max( 1000, min( 30000, (int) ( $raw['delay'] ?? $d['delay'] ) ) ),
			'speed'        => max( 100, min( 3000, (int) ( $raw['speed'] ?? $d['speed'] ) ) ),
			'loop'         => ! empty( $raw['loop'] ),
			'navigation'   => ! empty( $raw['navigation'] ),
			'pagination'   => ! empty( $raw['pagination'] ),
			'height_mode'  => in_array( $raw['height_mode'] ?? '', [ 'ratio', 'fixed', 'viewport' ], true ) ? (string) $raw['height_mode'] : 'ratio',
			'height'       => max( 200, min( 1200, (int) ( $raw['height'] ?? $d['height'] ) ) ),
			'ratio'        => self::ratio( (string) ( $raw['ratio'] ?? $d['ratio'] ), $d['ratio'] ),
			'ratio_mobile' => self::ratio( (string) ( $raw['ratio_mobile'] ?? $d['ratio_mobile'] ), $d['ratio_mobile'] ),
			'lazy'         => ! empty( $raw['lazy'] ),
			'effect'       => in_array( $raw['effect'] ?? '', [ 'fade', 'slide' ], true ) ? (string) $raw['effect'] : 'fade',
			'full_width'   => ! empty( $raw['full_width'] ),
			'pause_hover'  => ! empty( $raw['pause_hover'] ),
		];
	}

	/** Accepts "16:6", "16/6" or "16x6"; returns "W:H" or the fallback. */
	public static function ratio( string $value, string $fallback ): string {
		if ( preg_match( '/^\s*(\d{1,3})\s*[:\/x]\s*(\d{1,3})\s*$/i', $value, $m ) && (int) $m[1] > 0 && (int) $m[2] > 0 ) {
			return (int) $m[1] . ':' . (int) $m[2];
		}
		return $fallback;
	}

	/** "16:6" to the CSS aspect-ratio value "16 / 6". */
	public static function ratio_css( string $ratio ): string {
		[ $w, $h ] = array_map( 'intval', explode( ':', $ratio . ':1' ) );
		return max( 1, $w ) . ' / ' . max( 1, $h );
	}

	/** @return array<int, array<string, mixed>> */
	public static function sanitize( array $raw ): array {
		$out = [];
		foreach ( $raw as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}
			$clean = [
				'image'           => absint( $slide['image'] ?? 0 ),
				'image_tablet'    => absint( $slide['image_tablet'] ?? 0 ),
				'image_mobile'    => absint( $slide['image_mobile'] ?? 0 ),
				'heading'         => sanitize_text_field( (string) ( $slide['heading'] ?? '' ) ),
				'subheading'      => sanitize_text_field( (string) ( $slide['subheading'] ?? '' ) ),
				'description'     => wp_kses_post( (string) ( $slide['description'] ?? '' ) ),
				'cta_text'        => sanitize_text_field( (string) ( $slide['cta_text'] ?? '' ) ),
				'cta_url'         => esc_url_raw( (string) ( $slide['cta_url'] ?? '' ) ),
				'cta2_text'       => sanitize_text_field( (string) ( $slide['cta2_text'] ?? '' ) ),
				'cta2_url'        => esc_url_raw( (string) ( $slide['cta2_url'] ?? '' ) ),
				'align'           => in_array( $slide['align'] ?? '', self::ALIGN, true ) ? (string) $slide['align'] : 'left',
				'valign'          => in_array( $slide['valign'] ?? '', self::VALIGN, true ) ? (string) $slide['valign'] : 'middle',
				'overlay_color'   => (string) sanitize_hex_color( (string) ( $slide['overlay_color'] ?? '' ) ),
				'overlay_opacity' => max( 0, min( 100, (int) ( $slide['overlay_opacity'] ?? 0 ) ) ),
				'text_color'      => (string) sanitize_hex_color( (string) ( $slide['text_color'] ?? '' ) ),
				'animation'       => in_array( $slide['animation'] ?? '', self::ANIMATIONS, true ) ? (string) $slide['animation'] : 'fade',
				'benefits'        => self::benefits( (string) ( $slide['benefits'] ?? '' ) ),
			];
			if ( $clean['image'] || $clean['heading'] ) {
				$out[] = $clean;
			}
			if ( count( $out ) >= self::MAX_SLIDES ) {
				break;
			}
		}
		return $out;
	}

	/** Lines "icon|Text" (icon optional) to at most 4 items. */
	public static function benefits( string $text ): array {
		$out = [];
		foreach ( preg_split( '/\r?\n/', trim( $text ) ) ?: [] as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$out[] = count( $parts ) === 2 ? [ 'icon' => sanitize_key( $parts[0] ), 'text' => sanitize_text_field( $parts[1] ) ] : [ 'icon' => 'check', 'text' => sanitize_text_field( $parts[0] ) ];
			if ( count( $out ) >= 4 ) {
				break;
			}
		}
		return $out;
	}
}
