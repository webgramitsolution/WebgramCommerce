<?php
namespace Webgram\Core\Modules\Invoice\Rest;

use Webgram\Core\Abstracts\RestController;
use Webgram\Core\Modules\Invoice\Module;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/** GET /invoice/{order_id}: streams the PDF to the order owner (or a valid order key) or to shop managers. */
final class InvoiceController extends RestController {

	public function __construct( private Module $module ) {}

	public function register_routes(): void {
		$this->route(
			'/invoice/(?P<order_id>\d+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'download' ],
					'permission_callback' => [ $this, 'can_access' ],
					'args'                => [ 'order_id' => [ 'type' => 'integer', 'required' => true ], 'key' => [ 'type' => 'string' ], 'token' => [ 'type' => 'string' ], 'regenerate' => [ 'type' => 'boolean' ] ],
				],
			]
		);
	}

	public function can_access( WP_REST_Request $request ): bool {
		$order = wc_get_order( (int) $request['order_id'] );
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}
		$key = (string) $request->get_param( 'key' );
		if ( '' !== $key && hash_equals( (string) $order->get_order_key(), $key ) ) {
			return true;
		}
		$token = (string) $request->get_param( 'token' );
		if ( '' !== $token && $this->module->token_valid( $order->get_id(), $token, time() ) ) {
			return true;
		}
		return is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id() && $order->get_customer_id() > 0;
	}

	public function download( WP_REST_Request $request ) {
		$order = wc_get_order( (int) $request['order_id'] );
		if ( ! $order instanceof \WC_Order ) {
			return $this->fail( 'not_found', __( 'Order not found.', 'webgram-core' ), 404 );
		}
		$regenerate = current_user_can( 'manage_woocommerce' ) && filter_var( $request->get_param( 'regenerate' ), FILTER_VALIDATE_BOOLEAN );
		$file       = $this->module->file_for( $order, $regenerate, true );
		if ( ! $file ) {
			return $this->fail( 'unavailable', __( 'The invoice is not available for this order yet.', 'webgram-core' ), 409 );
		}
		nocache_headers();
		header( 'Content-Type: ' . $file['mime'] );
		header( 'Content-Disposition: ' . ( 'text/html' === $file['mime'] ? 'inline' : 'attachment' ) . '; filename="' . basename( $file['path'] ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $file['path'] ) );
		readfile( $file['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
