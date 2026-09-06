<?php
namespace Webgram\Core\Modules\Slider;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Hero slider: wg_slider post type with per-device images, overlays, CTAs and animations, rendered with Swiper
 * through the shortcode, the Elementor widget and the Gutenberg block (all share Renderer).
 */
final class Module extends BaseModule {

	private ?Renderer $renderer = null;

	public function id(): string {
		return 'slider';
	}

	public function name(): string {
		return __( 'Slider', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Responsive hero slider with per-device images, overlays, CTAs and animations.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function phase(): int {
		return 5;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		( new PostType() )->register();
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_shortcode( 'webgram_slider', [ $this, 'shortcode' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-swiper', 'vendor/swiper/swiper-bundle.min.css' );
		$assets->script( 'webgram-core-swiper', 'vendor/swiper/swiper-bundle.min.js', [], true );
		$assets->style( 'webgram-core-slider', 'css/slider.css', [ 'webgram-core-swiper' ] );
		$assets->script( 'webgram-core-slider', 'js/slider.js', [ 'webgram-core-swiper' ] );
	}

	public function renderer(): Renderer {
		return $this->renderer ??= new Renderer( $this );
	}

	public function render( int $id, array $overrides = [] ): string {
		return $this->renderer()->render( $id, $overrides );
	}

	public function shortcode( array|string $atts ): string {
		$atts = shortcode_atts( [ 'id' => 0 ], (array) $atts, 'webgram_slider' );
		return $this->render( (int) $atts['id'] );
	}

	/** @return array<int, string> id => title */
	public static function choices(): array {
		$out = [];
		foreach ( get_posts( [ 'post_type' => PostType::TYPE, 'numberposts' => 100, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ] ) as $post ) {
			$out[ (int) $post->ID ] = (string) $post->post_title;
		}
		return $out;
	}

	public function widget_definition( array $widgets ): array {
		$widgets['slider'] = [
			'title'    => __( 'Webgram Slider', 'webgram-core' ),
			'icon'     => 'eicon-slider-push',
			'controls' => [
				'slider_id' => [ 'label' => __( 'Slider', 'webgram-core' ), 'type' => 'select', 'options' => static fn() => self::choices(), 'default' => 0 ],
			],
			'render'   => fn( array $args ) => $this->render( (int) ( $args['slider_id'] ?? 0 ) ),
		];
		return $widgets;
	}
}
