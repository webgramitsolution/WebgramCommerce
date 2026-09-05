<?php
/**
 * "Webgram options" box on pages, posts and products: layout, sidebar, page title band and title image overrides.
 * Writes _webgram_layout, _webgram_sidebar, _webgram_page_title, _webgram_page_title_image, _webgram_flush_top.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Page_Metabox {

	public const NONCE = 'webgram_page_options';

	public static function init(): void {
		add_action( 'add_meta_boxes', [ self::class, 'add' ] );
		add_action( 'save_post', [ self::class, 'save' ], 10, 2 );
	}

	/** @return string[] */
	public static function post_types(): array {
		$types = [ 'page', 'post' ];
		if ( class_exists( 'WooCommerce' ) ) {
			$types[] = 'product';
		}
		return (array) apply_filters( 'webgram/page_options/post_types', $types );
	}

	public static function add(): void {
		foreach ( self::post_types() as $type ) {
			add_meta_box( 'webgram-page-options', __( 'Webgram options', 'webgram' ), [ self::class, 'render' ], $type, 'side', 'default' );
		}
	}

	/** Layout choices shared with the Layout tab. */
	public static function layouts(): array {
		return [
			'default'       => __( 'Theme default', 'webgram' ),
			'container'     => __( 'No sidebar', 'webgram' ),
			'full-width'    => __( 'Full width', 'webgram' ),
			'sidebar-left'  => __( 'Sidebar left', 'webgram' ),
			'sidebar-right' => __( 'Sidebar right', 'webgram' ),
		];
	}

	public static function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE, self::NONCE );
		$layout  = (string) get_post_meta( $post->ID, '_webgram_layout', true );
		$sidebar = (string) get_post_meta( $post->ID, '_webgram_sidebar', true );
		$title   = (string) get_post_meta( $post->ID, '_webgram_page_title', true );
		if ( '' === $title && get_post_meta( $post->ID, '_webgram_hide_title', true ) ) {
			$title = 'hide';
		}
		$image    = (int) get_post_meta( $post->ID, '_webgram_page_title_image', true );
		$flush    = (bool) get_post_meta( $post->ID, '_webgram_flush_top', true );
		$sidebars = class_exists( 'Webgram_Sidebars' ) ? Webgram_Sidebars::choices() : [];
		?>
		<p>
			<label for="webgram_layout"><strong><?php esc_html_e( 'Layout', 'webgram' ); ?></strong></label>
			<select id="webgram_layout" name="webgram_layout" class="widefat">
				<?php foreach ( self::layouts() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $layout, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php if ( $sidebars ) : ?>
			<p>
				<label for="webgram_sidebar"><strong><?php esc_html_e( 'Sidebar', 'webgram' ); ?></strong></label>
				<select id="webgram_sidebar" name="webgram_sidebar" class="widefat">
					<option value=""><?php esc_html_e( 'Theme default', 'webgram' ); ?></option>
					<?php foreach ( $sidebars as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sidebar, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>
		<p>
			<label for="webgram_page_title"><strong><?php esc_html_e( 'Page title band', 'webgram' ); ?></strong></label>
			<select id="webgram_page_title" name="webgram_page_title" class="widefat">
				<option value="" <?php selected( $title, '' ); ?>><?php esc_html_e( 'Theme default', 'webgram' ); ?></option>
				<option value="show" <?php selected( $title, 'show' ); ?>><?php esc_html_e( 'Show', 'webgram' ); ?></option>
				<option value="hide" <?php selected( $title, 'hide' ); ?>><?php esc_html_e( 'Hide', 'webgram' ); ?></option>
			</select>
		</p>
		<p>
			<strong><?php esc_html_e( 'Title background image', 'webgram' ); ?></strong>
			<span class="wg-meta-image" data-wg-meta-image>
				<input type="hidden" name="webgram_page_title_image" value="<?php echo (int) $image; ?>">
				<?php if ( $image ) : ?>
					<?php echo wp_get_attachment_image( $image, 'medium', false, [ 'style' => 'max-width:100%;height:auto;display:block;margin:6px 0' ] ); ?>
				<?php endif; ?>
				<button type="button" class="button" data-wg-meta-image-select><?php esc_html_e( 'Choose image', 'webgram' ); ?></button>
				<button type="button" class="button-link" data-wg-meta-image-remove <?php echo $image ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'webgram' ); ?></button>
			</span>
		</p>
		<p>
			<label for="webgram_flush_top">
				<input type="checkbox" id="webgram_flush_top" name="webgram_flush_top" value="1" <?php checked( $flush ); ?>>
				<strong><?php esc_html_e( 'Flush content to header', 'webgram' ); ?></strong>
			</label>
			<span class="description"><?php esc_html_e( 'Removes the space above the content so a full width hero touches the header.', 'webgram' ); ?></span>
		</p>
		<script>
		(function () {
			var box = document.querySelector('[data-wg-meta-image]');
			if (!box || !window.wp || !wp.media) { return; }
			var input = box.querySelector('input'), remove = box.querySelector('[data-wg-meta-image-remove]');
			box.querySelector('[data-wg-meta-image-select]').addEventListener('click', function () {
				var frame = wp.media({ multiple: false, library: { type: 'image' } });
				frame.on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					input.value = a.id;
					var img = box.querySelector('img') || box.insertBefore(document.createElement('img'), box.firstChild.nextSibling);
					img.src = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url); img.style.cssText = 'max-width:100%;height:auto;display:block;margin:6px 0';
					remove.hidden = false;
				});
				frame.open();
			});
			remove.addEventListener('click', function () { input.value = '0'; var img = box.querySelector('img'); if (img) { img.remove(); } remove.hidden = true; });
		})();
		</script>
		<?php
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}
		$layout = isset( $_POST['webgram_layout'] ) ? sanitize_key( wp_unslash( $_POST['webgram_layout'] ) ) : 'default';
		self::store( $post_id, '_webgram_layout', isset( self::layouts()[ $layout ] ) && 'default' !== $layout ? $layout : '' );

		$sidebar = isset( $_POST['webgram_sidebar'] ) ? sanitize_key( wp_unslash( $_POST['webgram_sidebar'] ) ) : '';
		self::store( $post_id, '_webgram_sidebar', $sidebar );

		$title = isset( $_POST['webgram_page_title'] ) ? sanitize_key( wp_unslash( $_POST['webgram_page_title'] ) ) : '';
		self::store( $post_id, '_webgram_page_title', in_array( $title, [ 'show', 'hide' ], true ) ? $title : '' );
		delete_post_meta( $post_id, '_webgram_hide_title' );

		self::store( $post_id, '_webgram_page_title_image', isset( $_POST['webgram_page_title_image'] ) ? (string) absint( $_POST['webgram_page_title_image'] ) : '' );

		self::store( $post_id, '_webgram_flush_top', isset( $_POST['webgram_flush_top'] ) ? '1' : '' );
	}

	private static function store( int $post_id, string $key, string $value ): void {
		if ( '' === $value || '0' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

Webgram_Page_Metabox::init();
