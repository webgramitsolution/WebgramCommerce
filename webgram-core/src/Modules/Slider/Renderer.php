<?php
namespace Webgram\Core\Modules\Slider;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** Front-end slider markup: Swiper container, per-device pictures, overlays, CTAs and benefit rows. */
final class Renderer {

	public function __construct( private Module $module ) {}

	public function render( int $id, array $overrides = [] ): string {
		$post = get_post( $id );
		if ( ! $post || PostType::TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}
		$slides = PostType::slides( $id );
		if ( ! $slides ) {
			return '';
		}
		$settings = array_merge( PostType::settings( $id ), array_intersect_key( $overrides, Slides::defaults() ) );
		$assets   = \webgram_core()->assets();
		$assets->enqueue_base();
		if ( ! $assets->theme_provides_styles() ) {
			wp_enqueue_style( 'webgram-core-swiper' );
		} else {
			wp_enqueue_style( 'webgram-core-swiper' ); // Swiper's own layout CSS is required in every theme; the theme only skins it.
		}
		wp_enqueue_script( 'webgram-core-swiper' );
		wp_enqueue_script( 'webgram-core-slider' );
		if ( ! $assets->theme_provides_styles() ) {
			wp_enqueue_style( 'webgram-core-slider' );
		}
		$config = [
			'effect'     => $settings['effect'],
			'loop'       => $settings['loop'] && count( $slides ) > 1,
			'autoplay'   => $settings['autoplay'] && count( $slides ) > 1 ? [ 'delay' => $settings['delay'], 'pauseOnMouseEnter' => $settings['pause_hover'], 'disableOnInteraction' => false ] : false,
			'speed'      => 700,
			'navigation' => $settings['navigation'],
			'pagination' => $settings['pagination'],
		];
		return \webgram_core()->view(
			'slider/slider',
			[
				'id'       => $id,
				'slides'   => $slides,
				'settings' => $settings,
				'config'   => (array) apply_filters( 'webgram_core/slider/config', $config, $id, $settings ),
				'style'    => self::inline_style( $settings ),
				'classes'  => Helpers::css_class( 'slider', 'swiper wgc-slider--' . $settings['height_mode'] . ( $settings['full_width'] ? ' wgc-slider--full' : '' ) ),
			],
			false
		);
	}

	/** Pure: CSS custom properties that fix the slide box before images load (no CLS). */
	public static function inline_style( array $settings ): string {
		$vars = [
			'--wgc-slider-ratio'        => Slides::ratio_css( $settings['ratio'] ),
			'--wgc-slider-ratio-mobile' => Slides::ratio_css( $settings['ratio_mobile'] ),
			'--wgc-slider-height'       => (int) $settings['height'] . 'px',
		];
		$out = '';
		foreach ( $vars as $k => $v ) {
			$out .= $k . ':' . $v . ';';
		}
		return $out;
	}

	/**
	 * Pure: which attachment serves each device, falling back to the desktop image.
	 *
	 * @return array{desktop: int, tablet: int, mobile: int}
	 */
	public static function sources( array $slide ): array {
		$desktop = (int) ( $slide['image'] ?? 0 );
		return [
			'desktop' => $desktop,
			'tablet'  => (int) ( $slide['image_tablet'] ?? 0 ) ?: $desktop,
			'mobile'  => (int) ( $slide['image_mobile'] ?? 0 ) ?: ( (int) ( $slide['image_tablet'] ?? 0 ) ?: $desktop ),
		];
	}
}
