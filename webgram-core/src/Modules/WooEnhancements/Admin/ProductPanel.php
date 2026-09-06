<?php
namespace Webgram\Core\Modules\WooEnhancements\Admin;

use Webgram\Core\Modules\WooEnhancements\ContactSeller;
use Webgram\Core\Modules\WooEnhancements\Module;
use Webgram\Core\Modules\WooEnhancements\Specifications;

defined( 'ABSPATH' ) || exit;

/**
 * "Webgram" tab in the product data box: card chip attribute, product video URL, specifications repeater,
 * contact seller overrides. Other Core modules append fields through webgram_core/product_panel/fields and save
 * through webgram_core/product_panel/save.
 */
final class ProductPanel {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'panel' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function tab( array $tabs ): array {
		$tabs['webgram'] = [ 'label' => __( 'Webgram', 'webgram-core' ), 'target' => 'webgram_product_data', 'class' => [], 'priority' => 75 ];
		return $tabs;
	}

	public function assets( string $hook ): void {
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && 'product' === get_post_type() ) {
			wp_enqueue_style( 'webgram-core-admin' );
			wp_enqueue_script( 'webgram-core-admin' );
		}
	}

	public function panel(): void {
		global $post;
		$id      = (int) $post->ID;
		$specs   = array_filter( (array) get_post_meta( $id, Specifications::META, true ), 'is_array' );
		$contact = (array) get_post_meta( $id, ContactSeller::META, true );
		$attrs   = [ 'first' => __( 'Theme default', 'webgram-core' ) ];
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $tax ) {
				$attrs[ 'pa_' . $tax->attribute_name ] = $tax->attribute_label;
			}
		}
		wp_nonce_field( 'webgram_product_panel', 'webgram_product_panel_nonce' );
		?>
		<div id="webgram_product_data" class="panel woocommerce_options_panel wgc-product-panel">
			<div class="options_group">
				<?php
				woocommerce_wp_select( [ 'id' => '_wg_chip_attribute', 'label' => __( 'Card chips attribute', 'webgram-core' ), 'options' => $attrs, 'value' => (string) get_post_meta( $id, '_wg_chip_attribute', true ) ?: 'first', 'desc_tip' => true, 'description' => __( 'Attribute shown as chips under the price on product cards.', 'webgram-core' ) ] );
				woocommerce_wp_text_input( [ 'id' => '_webgram_video_url', 'label' => __( 'Product video URL', 'webgram-core' ), 'type' => 'url', 'placeholder' => 'https://', 'desc_tip' => true, 'description' => __( 'MP4, YouTube or Vimeo. Shown as a gallery slide.', 'webgram-core' ) ] );
				?>
			</div>
			<div class="options_group">
				<p class="form-field"><label><?php esc_html_e( 'Specifications', 'webgram-core' ); ?></label><span class="description"><?php esc_html_e( 'Extra rows merged with WooCommerce attributes.', 'webgram-core' ); ?></span></p>
				<table class="wgc-specs" data-wgc-repeater>
					<tbody>
					<?php foreach ( array_merge( array_values( $specs ), [ [ 'label' => '', 'value' => '' ] ] ) as $i => $row ) : ?>
						<tr>
							<td><input type="text" name="_wg_specs[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( (string) ( $row['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Label', 'webgram-core' ); ?>"></td>
							<td><input type="text" name="_wg_specs[<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( (string) ( $row['value'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Value', 'webgram-core' ); ?>"></td>
							<td><button type="button" class="button-link wgc-row-remove" aria-label="<?php esc_attr_e( 'Remove', 'webgram-core' ); ?>">&times;</button></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button wgc-row-add"><?php esc_html_e( 'Add row', 'webgram-core' ); ?></button></p>
			</div>
			<div class="options_group">
				<?php
				woocommerce_wp_text_input( [ 'id' => '_wg_contact_phone', 'name' => '_wg_contact[phone]', 'label' => __( 'Contact phone (override)', 'webgram-core' ), 'value' => (string) ( $contact['phone'] ?? '' ) ] );
				woocommerce_wp_text_input( [ 'id' => '_wg_contact_whatsapp', 'name' => '_wg_contact[whatsapp]', 'label' => __( 'WhatsApp number (override)', 'webgram-core' ), 'value' => (string) ( $contact['whatsapp'] ?? '' ) ] );
				woocommerce_wp_text_input( [ 'id' => '_wg_contact_chat', 'name' => '_wg_contact[chat_url]', 'label' => __( 'Chat URL (override)', 'webgram-core' ), 'type' => 'url', 'value' => (string) ( $contact['chat_url'] ?? '' ) ] );
				?>
			</div>
			<?php do_action( 'webgram_core/product_panel/fields', $id ); ?>
		</div>
		<?php
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['webgram_product_panel_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['webgram_product_panel_nonce'] ) ), 'webgram_product_panel' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		$chip = isset( $_POST['_wg_chip_attribute'] ) ? sanitize_key( wp_unslash( $_POST['_wg_chip_attribute'] ) ) : 'first';
		update_post_meta( $post_id, '_wg_chip_attribute', 'first' === $chip ? '' : $chip );
		update_post_meta( $post_id, '_webgram_video_url', isset( $_POST['_webgram_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['_webgram_video_url'] ) ) : '' );
		$specs = isset( $_POST['_wg_specs'] ) && is_array( $_POST['_wg_specs'] ) ? Specifications::sanitize_rows( wp_unslash( $_POST['_wg_specs'] ) ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta( $post_id, Specifications::META, $specs );
		$contact = isset( $_POST['_wg_contact'] ) && is_array( $_POST['_wg_contact'] ) ? wp_unslash( $_POST['_wg_contact'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta(
			$post_id,
			ContactSeller::META,
			[
				'phone'    => sanitize_text_field( (string) ( $contact['phone'] ?? '' ) ),
				'whatsapp' => sanitize_text_field( (string) ( $contact['whatsapp'] ?? '' ) ),
				'chat_url' => esc_url_raw( (string) ( $contact['chat_url'] ?? '' ) ),
			]
		);
		do_action( 'webgram_core/product_panel/save', $post_id );
	}
}
