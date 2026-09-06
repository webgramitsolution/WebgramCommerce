<?php
namespace Webgram\Core\Modules\Reels;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** wg_reel post type, wg_reel_category taxonomy, metabox and admin columns. */
final class PostType {

	public const TYPE = 'wg_reel';
	public const TAX  = 'wg_reel_category';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_types' ] );
		add_action( 'add_meta_boxes_' . self::TYPE, [ $this, 'metabox' ] );
		add_action( 'save_post_' . self::TYPE, [ $this, 'save' ], 10, 2 );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_filter( 'upload_size_limit', [ $this, 'upload_limit' ] );
	}

	public function register_types(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'       => [ 'name' => __( 'Reels', 'webgram-core' ), 'singular_name' => __( 'Reel', 'webgram-core' ), 'add_new_item' => __( 'Add reel', 'webgram-core' ), 'menu_name' => __( 'Reels', 'webgram-core' ) ],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => ModulesPage::parent_slug(),
				'supports'     => [ 'title', 'excerpt' ],
				'taxonomies'   => [ self::TAX ],
				'capability_type' => 'page',
				'map_meta_cap' => true,
			]
		);
		register_taxonomy( self::TAX, self::TYPE, [ 'labels' => [ 'name' => __( 'Reel categories', 'webgram-core' ), 'singular_name' => __( 'Reel category', 'webgram-core' ) ], 'hierarchical' => true, 'public' => false, 'show_ui' => true, 'show_admin_column' => true, 'show_in_rest' => false ] );
	}

	/** Limit reel uploads to the module setting when uploading from the reel screen. */
	public function upload_limit( $bytes ) {
		$mb = (int) $this->module->settings()->get( 'upload_limit_mb', 0 );
		if ( $mb > 0 && isset( $_SERVER['HTTP_REFERER'] ) && str_contains( (string) wp_unslash( $_SERVER['HTTP_REFERER'] ), 'post_type=' . self::TYPE ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return min( (int) $bytes, $mb * MB_IN_BYTES );
		}
		return $bytes;
	}

	public function assets( string $hook ): void {
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && self::TYPE === get_post_type() ) {
			wp_enqueue_media();
			wp_enqueue_style( 'webgram-core-admin' );
			wp_enqueue_script( 'webgram-core-admin' );
		}
	}

	public function metabox(): void {
		add_meta_box( 'wgc-reel', __( 'Reel', 'webgram-core' ), [ $this, 'render' ], self::TYPE, 'normal', 'high' );
	}

	public static function data( int $post_id ): array {
		return [
			'source'   => (string) get_post_meta( $post_id, '_wg_video_source', true ) ?: 'upload',
			'video_id' => (int) get_post_meta( $post_id, '_wg_video_id', true ),
			'url'      => (string) get_post_meta( $post_id, '_wg_video_url', true ),
			'poster'   => (int) get_post_meta( $post_id, '_wg_poster_id', true ),
			'products' => array_values( array_filter( array_map( 'intval', (array) get_post_meta( $post_id, '_wg_products', true ) ) ) ),
			'cta'      => (array) get_post_meta( $post_id, '_wg_cta', true ) + [ 'text' => '', 'url' => '' ],
		];
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'wgc_reel', 'wgc_reel_nonce' );
		$d        = self::data( (int) $post->ID );
		$products = [];
		if ( function_exists( 'wc_get_products' ) ) {
			foreach ( wc_get_products( [ 'limit' => 300, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ] ) as $p ) {
				$products[ $p->get_id() ] = $p->get_name();
			}
		}
		\webgram_core()->view( 'reels/admin', [ 'd' => $d, 'products' => $products, 'sources' => Sources::all() ] );
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['wgc_reel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wgc_reel_nonce'] ) ), 'wgc_reel' ) || ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$source = isset( $_POST['wg_video_source'] ) && 'external' === $_POST['wg_video_source'] ? 'external' : 'upload';
		update_post_meta( $post_id, '_wg_video_source', $source );
		update_post_meta( $post_id, '_wg_video_id', absint( $_POST['wg_video_id'] ?? 0 ) );
		update_post_meta( $post_id, '_wg_video_url', esc_url_raw( wp_unslash( $_POST['wg_video_url'] ?? '' ) ) );
		update_post_meta( $post_id, '_wg_poster_id', absint( $_POST['wg_poster_id'] ?? 0 ) );
		$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', (array) ( $_POST['wg_products'] ?? [] ) ) ) ) ), 0, 5 );
		update_post_meta( $post_id, '_wg_products', $ids );
		update_post_meta( $post_id, '_wg_cta', [ 'text' => sanitize_text_field( wp_unslash( $_POST['wg_cta_text'] ?? '' ) ), 'url' => esc_url_raw( wp_unslash( $_POST['wg_cta_url'] ?? '' ) ) ] );
	}

	public function columns( array $columns ): array {
		$out = [];
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['wg_poster'] = __( 'Poster', 'webgram-core' );
			}
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['wg_products'] = __( 'Products', 'webgram-core' );
				$out['wg_source']   = __( 'Source', 'webgram-core' );
			}
		}
		return $out;
	}

	public function column( string $column, int $post_id ): void {
		$d = self::data( $post_id );
		if ( 'wg_poster' === $column && $d['poster'] ) {
			echo wp_get_attachment_image( $d['poster'], [ 45, 80 ], false, [ 'style' => 'border-radius:4px' ] );
		} elseif ( 'wg_products' === $column ) {
			$names = [];
			foreach ( $d['products'] as $id ) {
				$p = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
				if ( $p ) {
					$names[] = esc_html( $p->get_name() );
				}
			}
			echo $names ? implode( ', ', $names ) : '<span class="description">' . esc_html__( 'None', 'webgram-core' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		} elseif ( 'wg_source' === $column ) {
			echo esc_html( 'upload' === $d['source'] ? __( 'Upload', 'webgram-core' ) : ( Sources::all()[ Sources::detect( $d['url'] ) ]['label'] ?? __( 'External', 'webgram-core' ) ) );
		}
	}
}
