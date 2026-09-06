<?php
namespace Webgram\Core\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Server-rendered Gutenberg blocks (webgram/{id}) built from the same definitions. A single editor script reads
 * the definitions and builds the inspector controls; rendering always happens in PHP through the Registry.
 */
final class Blocks {

	public function __construct( private Registry $registry ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_blocks' ], 25 );
		add_filter( 'block_categories_all', [ $this, 'category' ] );
	}

	public function category( array $categories ): array {
		array_unshift( $categories, [ 'slug' => 'webgram', 'title' => __( 'Webgram', 'webgram-core' ), 'icon' => null ] );
		return $categories;
	}

	/** Pure: block attribute schema from controls. */
	public static function attributes( array $controls ): array {
		$out = [];
		foreach ( $controls as $cid => $c ) {
			$out[ $cid ] = match ( $c['type'] ) {
				'number', 'image', 'product', 'post' => [ 'type' => 'number', 'default' => (int) $c['default'] ],
				'switch'   => [ 'type' => 'boolean', 'default' => (bool) $c['default'] ],
				'category', 'tag', 'repeater' => [ 'type' => 'array', 'default' => (array) $c['default'] ],
				default    => [ 'type' => 'string', 'default' => (string) $c['default'] ],
			};
		}
		$out['align']     = [ 'type' => 'string', 'default' => '' ];
		$out['className'] = [ 'type' => 'string', 'default' => '' ];
		return $out;
	}

	public function register_blocks(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$definitions = [];
		wp_register_script( 'webgram-core-blocks', WEBGRAM_CORE_URL . 'assets/blocks/editor.js', [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ], WEBGRAM_CORE_VERSION, true );
		foreach ( $this->registry->all() as $id => $def ) {
			$controls = [];
			foreach ( $def['controls'] as $cid => $c ) {
				$c['options'] = in_array( $c['type'], [ 'select' ], true ) ? Registry::options( $c ) : null;
				$c['fields']  = 'repeater' === $c['type'] ? array_map( static fn( array $f ) => $f + [ 'options' => Registry::options( $f ) ], $c['fields'] ) : null;
				unset( $c['render'] );
				$controls[ $cid ] = array_filter( $c, static fn( $v ) => ! is_callable( $v ) || is_string( $v ) );
			}
			$definitions[ $id ] = [ 'title' => $def['title'], 'description' => (string) ( $def['description'] ?? '' ), 'icon' => self::dashicon( $def['icon'] ), 'category' => 'webgram', 'controls' => $controls ];
			register_block_type(
				'webgram/' . str_replace( '_', '-', $id ),
				[
					'api_version'     => 3,
					'title'           => $def['title'],
					'category'        => 'webgram',
					'description'     => (string) ( $def['description'] ?? '' ),
					'attributes'      => self::attributes( $def['controls'] ),
					'editor_script'   => 'webgram-core-blocks',
					'supports'        => [ 'align' => [ 'wide', 'full' ], 'html' => false ],
					'render_callback' => fn( array $attrs ) => $this->render_block( $id, $attrs ),
				]
			);
		}
		wp_localize_script( 'webgram-core-blocks', 'webgramCoreBlocks', [ 'definitions' => $definitions ] );
	}

	private function render_block( string $id, array $attrs ): string {
		$html = $this->registry->render( $id, $attrs );
		if ( '' === $html ) {
			return '';
		}
		$classes = trim( 'wp-block-webgram-' . str_replace( '_', '-', $id ) . ' ' . ( ! empty( $attrs['align'] ) ? 'align' . sanitize_html_class( (string) $attrs['align'] ) : '' ) . ' ' . sanitize_text_field( (string) ( $attrs['className'] ?? '' ) ) );
		return '<div class="' . esc_attr( $classes ) . '">' . $html . '</div>';
	}

	/** Pure: Elementor icon name to a dashicon slug for the block inserter. */
	public static function dashicon( string $eicon ): string {
		$map = [
			'eicon-slider-push' => 'slides', 'eicon-instagram-gallery' => 'instagram', 'eicon-review' => 'star-filled', 'eicon-heart' => 'heart', 'eicon-exchange' => 'randomize',
			'eicon-products' => 'products', 'eicon-product-images' => 'grid-view', 'eicon-posts-grid' => 'grid-view', 'eicon-testimonial' => 'format-quote', 'eicon-coupon' => 'tickets-alt',
			'eicon-shield' => 'shield', 'eicon-banner' => 'cover-image', 'eicon-heading' => 'heading', 'eicon-info-box' => 'info', 'eicon-post-list' => 'admin-post', 'eicon-product-categories' => 'category',
			'eicon-product-title' => 'editor-textcolor', 'eicon-product-price' => 'tag', 'eicon-product-rating' => 'star-half', 'eicon-product-description' => 'text', 'eicon-product-add-to-cart' => 'cart',
			'eicon-product-meta' => 'info-outline', 'eicon-product-tabs' => 'index-card', 'eicon-product-related' => 'grid-view', 'eicon-product-breadcrumbs' => 'arrow-right-alt', 'eicon-product-stock' => 'archive', 'eicon-text' => 'text', 'eicon-marquee' => 'controls-forward',
		];
		return $map[ $eicon ] ?? 'layout';
	}
}
