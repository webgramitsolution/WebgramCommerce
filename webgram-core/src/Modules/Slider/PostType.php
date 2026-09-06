<?php
namespace Webgram\Core\Modules\Slider;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** wg_slider post type with the slides repeater and slider settings metaboxes. */
final class PostType {

	public const TYPE          = 'wg_slider';
	public const META_SLIDES   = '_wg_slides';
	public const META_SETTINGS = '_wg_slider_settings';

	public function register(): void {
		add_action( 'init', [ $this, 'register_type' ] );
		add_action( 'add_meta_boxes_' . self::TYPE, [ $this, 'metaboxes' ] );
		add_action( 'save_post_' . self::TYPE, [ $this, 'save' ], 10, 2 );
		add_filter( 'manage_' . self::TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function register_type(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'       => [
					'name'          => __( 'Sliders', 'webgram-core' ),
					'singular_name' => __( 'Slider', 'webgram-core' ),
					'add_new_item'  => __( 'Add slider', 'webgram-core' ),
					'edit_item'     => __( 'Edit slider', 'webgram-core' ),
					'menu_name'     => __( 'Sliders', 'webgram-core' ),
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => ModulesPage::parent_slug(),
				'supports'     => [ 'title' ],
				'capability_type' => 'page',
				'map_meta_cap' => true,
				'show_in_rest' => false,
			]
		);
	}

	public function assets( string $hook ): void {
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && self::TYPE === get_post_type() ) {
			wp_enqueue_media();
			wp_enqueue_style( 'webgram-core-admin' );
			wp_enqueue_script( 'webgram-core-admin' );
		}
	}

	public function metaboxes(): void {
		add_meta_box( 'wgc-slides', __( 'Slides', 'webgram-core' ), [ $this, 'render_slides' ], self::TYPE, 'normal', 'high' );
		add_meta_box( 'wgc-slider-settings', __( 'Slider settings', 'webgram-core' ), [ $this, 'render_settings' ], self::TYPE, 'side' );
		add_meta_box( 'wgc-slider-usage', __( 'Use this slider', 'webgram-core' ), [ $this, 'render_usage' ], self::TYPE, 'side', 'low' );
	}

	public static function slides( int $post_id ): array {
		$slides = get_post_meta( $post_id, self::META_SLIDES, true );
		return is_array( $slides ) ? Slides::sanitize( $slides ) : [];
	}

	public static function settings( int $post_id ): array {
		$stored = get_post_meta( $post_id, self::META_SETTINGS, true );
		return Slides::sanitize_settings( array_merge( Slides::defaults(), is_array( $stored ) ? $stored : [] ) );
	}

	public function render_slides( \WP_Post $post ): void {
		wp_nonce_field( 'wgc_slider_save', 'wgc_slider_nonce' );
		$slides = self::slides( (int) $post->ID );
		\webgram_core()->view( 'slider/admin-slides', [ 'slides' => $slides ] );
	}

	public function render_settings( \WP_Post $post ): void {
		\webgram_core()->view( 'slider/admin-settings', [ 'settings' => self::settings( (int) $post->ID ) ] );
	}

	public function render_usage( \WP_Post $post ): void {
		printf( '<p>%s</p><code>[webgram_slider id="%d"]</code><p class="description">%s</p>', esc_html__( 'Shortcode:', 'webgram-core' ), (int) $post->ID, esc_html__( 'Also available as the "Webgram Slider" Elementor widget and Gutenberg block.', 'webgram-core' ) );
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['wgc_slider_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wgc_slider_nonce'] ) ), 'wgc_slider_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		$slides   = isset( $_POST['wg_slides'] ) && is_array( $_POST['wg_slides'] ) ? wp_unslash( $_POST['wg_slides'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		$settings = isset( $_POST['wg_slider'] ) && is_array( $_POST['wg_slider'] ) ? wp_unslash( $_POST['wg_slider'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta( $post_id, self::META_SLIDES, Slides::sanitize( $slides ) );
		update_post_meta( $post_id, self::META_SETTINGS, Slides::sanitize_settings( $settings ) );
	}

	public function columns( array $columns ): array {
		$out = [];
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['wg_slides']    = __( 'Slides', 'webgram-core' );
				$out['wg_shortcode'] = __( 'Shortcode', 'webgram-core' );
			}
		}
		return $out;
	}

	public function column( string $column, int $post_id ): void {
		if ( 'wg_slides' === $column ) {
			$slides = self::slides( $post_id );
			foreach ( array_slice( $slides, 0, 4 ) as $slide ) {
				if ( $slide['image'] ) {
					echo wp_get_attachment_image( $slide['image'], [ 60, 30 ], false, [ 'style' => 'margin-right:4px;border-radius:3px' ] );
				}
			}
			echo ' <span class="description">' . (int) count( $slides ) . '</span>';
		} elseif ( 'wg_shortcode' === $column ) {
			echo '<code>[webgram_slider id="' . (int) $post_id . '"]</code>';
		}
	}
}
