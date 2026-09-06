<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/**
 * HTML Blocks: CPT wg_block edited with Elementor or Gutenberg, rendered anywhere through [webgram_block id=""],
 * the webgram/html_block filter (theme) or Blocks::render(). Also renders wg_layout content (same pipeline).
 */
final class Blocks {

	public const POST_TYPE = 'wg_block';

	private static array $rendering = [];

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_type' ] );
		add_shortcode( 'webgram_block', [ $this, 'shortcode' ] );
		add_filter( 'webgram/html_blocks', [ $this, 'list' ] );
		add_filter( 'webgram/html_block', [ $this, 'filter_render' ], 10, 2 );
		add_filter( 'elementor/cpt_support', [ $this, 'elementor_support' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
	}

	public function register_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'HTML Blocks', 'webgram-core' ),
					'singular_name' => __( 'HTML Block', 'webgram-core' ),
					'add_new_item'  => __( 'Add HTML Block', 'webgram-core' ),
					'edit_item'     => __( 'Edit HTML Block', 'webgram-core' ),
					'menu_name'     => __( 'HTML Blocks', 'webgram-core' ),
				],
				'public'              => false,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => ModulesPage::parent_slug(),
				'show_in_rest'        => true,
				'supports'            => [ 'title', 'editor', 'revisions', 'custom-fields' ],
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'has_archive'         => false,
			]
		);
	}

	/** "Webgram HTML Block" widget, block and [webgram_html_block] shortcode: places any saved block inside a page. */
	public function widget_definition( array $w ): array {
		$w['html_block'] = [
			'title'    => __( 'Webgram HTML Block', 'webgram-core' ),
			'icon'     => 'eicon-code',
			'category' => 'webgram',
			'controls' => [
				'block_id' => [ 'label' => __( 'HTML Block', 'webgram-core' ), 'type' => 'select', 'options' => static fn(): array => [ 0 => __( 'Select a block', 'webgram-core' ) ] + (array) apply_filters( 'webgram/html_blocks', [] ), 'default' => 0 ],
			],
			'render'   => static fn( array $a ): string => (int) ( $a['block_id'] ?? 0 ) > 0 ? '<div class="wgc-block-embed">' . self::render( (int) $a['block_id'] ) . '</div>' : '',
		];
		return $w;
	}

	public function elementor_support( array $types ): array {
		$types[] = self::POST_TYPE;
		$types[] = Layouts\PostType::POST_TYPE;
		return array_unique( $types );
	}

	/** @return array<int, string> id => title */
	public function list( array $blocks ): array {
		$posts = get_posts( [ 'post_type' => self::POST_TYPE, 'post_status' => 'publish', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ] );
		foreach ( $posts as $id ) {
			$blocks[ (int) $id ] = get_the_title( $id );
		}
		return $blocks;
	}

	public function filter_render( string $html, int $id ): string {
		return $html . self::render( $id );
	}

	public function shortcode( array|string $atts ): string {
		$atts = shortcode_atts( [ 'id' => 0 ], (array) $atts, 'webgram_block' );
		return self::render( (int) $atts['id'] );
	}

	/** Render a block or layout post by id. Elementor content when built with Elementor, else the_content pipeline. */
	public static function render( int $id ): string {
		if ( $id <= 0 || isset( self::$rendering[ $id ] ) ) {
			return '';
		}
		$post = get_post( $id );
		if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, [ self::POST_TYPE, Layouts\PostType::POST_TYPE ], true ) ) {
			return '';
		}
		self::$rendering[ $id ] = true;

		$html = '';
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents->get( $id ) && \Elementor\Plugin::$instance->documents->get( $id )->is_built_with_elementor() ) {
			$html = (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id );
		} else {
			$html = (string) apply_filters( 'the_content', $post->post_content );
		}
		unset( self::$rendering[ $id ] );

		$class = \Webgram\Core\Support\Helpers::css_class( 'block', 'wgc-block-' . $id );
		return '<div class="' . esc_attr( $class ) . '">' . $html . '</div>';
	}

	public function columns( array $columns ): array {
		$columns['wg_shortcode'] = __( 'Shortcode', 'webgram-core' );
		return $columns;
	}

	public function column( string $column, int $post_id ): void {
		if ( 'wg_shortcode' === $column ) {
			echo '<code>[webgram_block id="' . (int) $post_id . '"]</code>';
		}
	}
}
