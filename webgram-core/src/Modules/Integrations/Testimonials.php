<?php
namespace Webgram\Core\Modules\Integrations;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** wg_testimonial post type: name (title), text (content), photo (thumbnail), label, rating, product link. */
final class Testimonials {

	public const TYPE = 'wg_testimonial';

	public function register(): void {
		add_action( 'init', [ $this, 'register_type' ] );
		add_action( 'add_meta_boxes_' . self::TYPE, [ $this, 'metabox' ] );
		add_action( 'save_post_' . self::TYPE, [ $this, 'save' ] );
	}

	public function register_type(): void {
		register_post_type(
			self::TYPE,
			[
				'labels'       => [ 'name' => __( 'Testimonials', 'webgram-core' ), 'singular_name' => __( 'Testimonial', 'webgram-core' ), 'add_new_item' => __( 'Add testimonial', 'webgram-core' ), 'menu_name' => __( 'Testimonials', 'webgram-core' ) ],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => ModulesPage::parent_slug(),
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
				'capability_type' => 'page',
				'map_meta_cap' => true,
			]
		);
	}

	public function metabox(): void {
		add_meta_box( 'wgc-testimonial', __( 'Testimonial details', 'webgram-core' ), [ $this, 'render' ], self::TYPE, 'side' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'wgc_testimonial', 'wgc_testimonial_nonce' );
		$label   = (string) get_post_meta( $post->ID, '_wg_label', true );
		$rating  = (int) get_post_meta( $post->ID, '_wg_rating', true ) ?: 5;
		$product = (int) get_post_meta( $post->ID, '_wg_product_id', true );
		printf( '<p><label>%s<br><input type="text" name="wg_label" value="%s" class="widefat" placeholder="%s"></label></p>', esc_html__( 'Label (age, city, role)', 'webgram-core' ), esc_attr( $label ), esc_attr__( '34, Mumbai', 'webgram-core' ) );
		echo '<p><label>' . esc_html__( 'Rating', 'webgram-core' ) . '<br><select name="wg_rating">';
		for ( $i = 5; $i >= 1; $i-- ) {
			printf( '<option value="%d" %s>%d</option>', $i, selected( $rating, $i, false ), $i );
		}
		echo '</select></label></p>';
		printf( '<p><label>%s<br><input type="number" name="wg_product_id" value="%d" class="small-text" min="0"></label> <span class="description">%s</span></p>', esc_html__( 'Product ID (optional)', 'webgram-core' ), $product, esc_html__( 'Shows the product name as a link.', 'webgram-core' ) );
		echo '<p class="description">' . esc_html__( 'Use the title for the reviewer name, the editor for the quote and the featured image for the photo.', 'webgram-core' ) . '</p>';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['wgc_testimonial_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wgc_testimonial_nonce'] ) ), 'wgc_testimonial' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_wg_label', sanitize_text_field( wp_unslash( $_POST['wg_label'] ?? '' ) ) );
		update_post_meta( $post_id, '_wg_rating', max( 1, min( 5, (int) ( $_POST['wg_rating'] ?? 5 ) ) ) );
		update_post_meta( $post_id, '_wg_product_id', absint( $_POST['wg_product_id'] ?? 0 ) );
	}

	/** @return array<int, array{name: string, label: string, text: string, rating: int, photo: string, product_name: string, product_url: string}> */
	public static function items( int $limit, array $ids = [] ): array {
		$posts = get_posts( [ 'post_type' => self::TYPE, 'numberposts' => $limit, 'post_status' => 'publish', 'post__in' => $ids ?: null, 'orderby' => $ids ? 'post__in' : 'date' ] );
		$out   = [];
		foreach ( $posts as $post ) {
			$product = (int) get_post_meta( $post->ID, '_wg_product_id', true );
			$p       = $product && function_exists( 'wc_get_product' ) ? wc_get_product( $product ) : null;
			$out[]   = [
				'name'         => (string) $post->post_title,
				'label'        => (string) get_post_meta( $post->ID, '_wg_label', true ),
				'text'         => (string) apply_filters( 'the_content', $post->post_content ),
				'rating'       => max( 1, min( 5, (int) get_post_meta( $post->ID, '_wg_rating', true ) ?: 5 ) ),
				'photo'        => get_the_post_thumbnail( $post, 'medium', [ 'loading' => 'lazy' ] ),
				'product_name' => $p ? $p->get_name() : '',
				'product_url'  => $p ? $p->get_permalink() : '',
			];
		}
		return $out;
	}
}
