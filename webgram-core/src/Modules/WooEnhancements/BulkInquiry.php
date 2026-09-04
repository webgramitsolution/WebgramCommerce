<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Bulk inquiry: modal on the product page and a full form (shortcode [webgram_bulk_inquiry]) for the Bulk Order
 * page. Stores private wg_inquiry posts, emails the store admin through wp_mail. Honeypot, nonce, rate limit.
 */
final class BulkInquiry {

	public const POST_TYPE = 'wg_inquiry';

	private bool $modal_needed = false;

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'post_type' ] );
		add_shortcode( 'webgram_bulk_inquiry', [ $this, 'shortcode' ] );
		add_action( 'webgram/product/bulk_inquiry_modal', [ $this, 'need_modal' ] );
		add_action( 'wp_footer', [ $this, 'modal' ], 31 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_status' ] );

		( new class( $this ) extends AjaxHandler {
			public function __construct( private BulkInquiry $inquiry ) {}
			protected function action(): string {
				return 'bulk_inquiry';
			}
			protected function fields(): array {
				return [ 'name' => 'text', 'company' => 'text', 'phone' => 'phone', 'email' => 'email', 'product_id' => 'int', 'product_name' => 'text', 'quantity' => 'int', 'message' => 'textarea', 'website' => 'text' ];
			}
			protected function handle( array $input ): void {
				$result = $this->inquiry->submit( $input );
				if ( ! $result['ok'] ) {
					$this->error( $result['message'], $result['status'] ?? 400 );
				}
				$this->success( [ 'id' => $result['id'] ], $result['message'] );
			}
		} )->register();
	}

	public function post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'          => [ 'name' => __( 'Bulk inquiries', 'webgram-core' ), 'singular_name' => __( 'Inquiry', 'webgram-core' ), 'menu_name' => __( 'Inquiries', 'webgram-core' ) ],
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => ModulesPage::parent_slug(),
				'supports'        => [ 'title' ],
				'capability_type' => 'post',
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'    => true,
			]
		);
	}

	/**
	 * Validate and store. Pure enough to harness test the validation branch.
	 *
	 * @return array{ok: bool, message: string, id?: int, status?: int}
	 */
	public function validate( array $input ): array {
		if ( '' !== trim( (string) ( $input['website'] ?? '' ) ) ) {
			return [ 'ok' => false, 'message' => __( 'Submission rejected.', 'webgram-core' ), 'status' => 400 ]; // Honeypot.
		}
		if ( '' === trim( (string) $input['name'] ) ) {
			return [ 'ok' => false, 'message' => __( 'Please enter your name.', 'webgram-core' ) ];
		}
		$phone = Helpers::to_e164( (string) $input['phone'], Helpers::calling_code( (string) \webgram_core()->settings()->get( 'default_country', 'IN' ) ) ?: '91' );
		if ( '' === $phone ) {
			return [ 'ok' => false, 'message' => __( 'Please enter a valid phone number with country code.', 'webgram-core' ) ];
		}
		if ( '' === (string) $input['email'] ) {
			return [ 'ok' => false, 'message' => __( 'Please enter a valid email address.', 'webgram-core' ) ];
		}
		if ( (int) $input['quantity'] < 1 ) {
			return [ 'ok' => false, 'message' => __( 'Please enter the quantity you need.', 'webgram-core' ) ];
		}
		return [ 'ok' => true, 'message' => '', 'phone' => $phone ];
	}

	public function submit( array $input ): array {
		$check = $this->validate( $input );
		if ( ! $check['ok'] ) {
			return $check;
		}
		if ( ! Helpers::rate_limit( 'bulk_inquiry', 5, HOUR_IN_SECONDS ) ) {
			return [ 'ok' => false, 'message' => __( 'Too many requests. Please try again later.', 'webgram-core' ), 'status' => 429 ];
		}
		$product = (int) $input['product_id'] > 0 ? wc_get_product( (int) $input['product_id'] ) : null;
		$name    = $product ? $product->get_name() : (string) $input['product_name'];
		$id      = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s: %s x %d', (string) $input['name'], $name ?: __( 'General', 'webgram-core' ), (int) $input['quantity'] ),
				'meta_input'  => [
					'_wg_name'       => (string) $input['name'],
					'_wg_company'    => (string) $input['company'],
					'_wg_phone'      => $check['phone'],
					'_wg_email'      => (string) $input['email'],
					'_wg_product_id' => $product ? $product->get_id() : 0,
					'_wg_product'    => $name,
					'_wg_quantity'   => (int) $input['quantity'],
					'_wg_message'    => (string) $input['message'],
					'_wg_status'     => 'new',
					'_wg_ip_hash'    => Helpers::ip_hash(),
				],
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			return [ 'ok' => false, 'message' => __( 'Could not save your request. Please try again.', 'webgram-core' ), 'status' => 500 ];
		}
		$this->notify( (int) $id, $input, $check['phone'], $name );
		do_action( 'webgram_core/bulk_inquiry/created', (int) $id );
		return [ 'ok' => true, 'id' => (int) $id, 'message' => (string) $this->module->settings()->get( 'bulk_success', __( 'Thank you. Our team will contact you shortly with a quote.', 'webgram-core' ) ) ];
	}

	private function notify( int $id, array $input, string $phone, string $product ): void {
		$to = (string) $this->module->settings()->get( 'bulk_notify_email', '' ) ?: (string) get_option( 'admin_email' );
		$lines = [
			sprintf( '%s: %s', __( 'Name', 'webgram-core' ), $input['name'] ),
			sprintf( '%s: %s', __( 'Company', 'webgram-core' ), $input['company'] ),
			sprintf( '%s: %s', __( 'Phone', 'webgram-core' ), $phone ),
			sprintf( '%s: %s', __( 'Email', 'webgram-core' ), $input['email'] ),
			sprintf( '%s: %s', __( 'Product', 'webgram-core' ), $product ),
			sprintf( '%s: %d', __( 'Quantity', 'webgram-core' ), (int) $input['quantity'] ),
			'',
			(string) $input['message'],
			'',
			admin_url( 'post.php?post=' . $id . '&action=edit' ),
		];
		wp_mail( $to, sprintf( /* translators: %s: site name */ __( '[%s] New bulk inquiry', 'webgram-core' ), wp_specialchars_decode( get_bloginfo( 'name' ) ) ), implode( "\n", $lines ), [ 'Reply-To: ' . sanitize_email( (string) $input['email'] ) ] );
	}

	public function need_modal(): void {
		$this->modal_needed = true;
	}

	public function modal(): void {
		if ( ! $this->modal_needed || is_admin() ) {
			return;
		}
		global $product;
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		\webgram_core()->view( 'woo-enhancements/bulk-inquiry', [ 'modal' => true, 'product' => $product instanceof \WC_Product ? $product : null, 'products' => [] ] );
	}

	public function shortcode( array|string $atts ): string {
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		$products = wc_get_products( [ 'limit' => 200, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC', 'return' => 'ids' ] );
		$list     = [];
		foreach ( $products as $pid ) {
			$list[ (int) $pid ] = get_the_title( $pid );
		}
		return \webgram_core()->view( 'woo-enhancements/bulk-inquiry', [ 'modal' => false, 'product' => null, 'products' => $list ], false );
	}

	public function columns( array $columns ): array {
		return [ 'cb' => $columns['cb'] ?? '', 'title' => __( 'Inquiry', 'webgram-core' ), 'wg_contact' => __( 'Contact', 'webgram-core' ), 'wg_qty' => __( 'Quantity', 'webgram-core' ), 'wg_status' => __( 'Status', 'webgram-core' ), 'date' => __( 'Date', 'webgram-core' ) ];
	}

	public function column( string $column, int $post_id ): void {
		if ( 'wg_contact' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, '_wg_phone', true ) ) . '<br>' . esc_html( (string) get_post_meta( $post_id, '_wg_email', true ) );
		} elseif ( 'wg_qty' === $column ) {
			echo (int) get_post_meta( $post_id, '_wg_quantity', true );
		} elseif ( 'wg_status' === $column ) {
			echo esc_html( self::statuses()[ (string) get_post_meta( $post_id, '_wg_status', true ) ] ?? __( 'New', 'webgram-core' ) );
		}
	}

	public static function statuses(): array {
		return [ 'new' => __( 'New', 'webgram-core' ), 'contacted' => __( 'Contacted', 'webgram-core' ), 'quoted' => __( 'Quoted', 'webgram-core' ), 'won' => __( 'Won', 'webgram-core' ), 'lost' => __( 'Lost', 'webgram-core' ) ];
	}

	public function metabox(): void {
		add_meta_box( 'wg_inquiry_details', __( 'Inquiry details', 'webgram-core' ), [ $this, 'render_metabox' ], self::POST_TYPE, 'normal', 'high' );
	}

	public function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'wg_inquiry_status', 'wg_inquiry_nonce' );
		$fields = [ 'name' => __( 'Name', 'webgram-core' ), 'company' => __( 'Company', 'webgram-core' ), 'phone' => __( 'Phone', 'webgram-core' ), 'email' => __( 'Email', 'webgram-core' ), 'product' => __( 'Product', 'webgram-core' ), 'quantity' => __( 'Quantity', 'webgram-core' ), 'message' => __( 'Message', 'webgram-core' ) ];
		echo '<table class="widefat striped">';
		foreach ( $fields as $key => $label ) {
			printf( '<tr><th style="width:160px">%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) get_post_meta( $post->ID, '_wg_' . $key, true ) ) ) );
		}
		echo '<tr><th>' . esc_html__( 'Status', 'webgram-core' ) . '</th><td><select name="wg_inquiry_status">';
		foreach ( self::statuses() as $key => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( (string) get_post_meta( $post->ID, '_wg_status', true ), $key, false ), esc_html( $label ) );
		}
		echo '</select></td></tr></table>';
	}

	public function save_status( int $post_id ): void {
		if ( ! isset( $_POST['wg_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wg_inquiry_nonce'] ) ), 'wg_inquiry_status' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = isset( $_POST['wg_inquiry_status'] ) ? sanitize_key( wp_unslash( $_POST['wg_inquiry_status'] ) ) : 'new';
		update_post_meta( $post_id, '_wg_status', array_key_exists( $status, self::statuses() ) ? $status : 'new' );
	}
}
