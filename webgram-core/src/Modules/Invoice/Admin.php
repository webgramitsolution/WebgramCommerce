<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** Order row action, order metabox with generate and regenerate, bulk "Download invoices" zip. */
final class Admin {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'woocommerce_admin_order_actions', [ $this, 'row_action' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
		add_action( 'admin_post_webgram_core_invoice_generate', [ $this, 'generate' ] );
		add_action( 'admin_post_webgram_core_invoice_zip', [ $this, 'serve_zip' ] );
		foreach ( [ 'edit-shop_order', 'woocommerce_page_wc-orders' ] as $screen ) {
			add_filter( 'bulk_actions-' . $screen, [ $this, 'bulk_action' ] );
			add_filter( 'handle_bulk_actions-' . $screen, [ $this, 'handle_bulk' ], 10, 3 );
		}
		add_action( 'admin_notices', [ $this, 'notices' ] );
	}

	public function row_action( array $actions, \WC_Order $order ): array {
		if ( '' !== $this->module->number_for( $order ) ) {
			$actions['wg_invoice'] = [ 'url' => $this->module->download_url( $order ), 'name' => __( 'Invoice', 'webgram-core' ), 'action' => 'wg-invoice view' ];
		}
		return $actions;
	}

	public function metabox(): void {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'wgc-invoice', __( 'Invoice', 'webgram-core' ), [ $this, 'render_metabox' ], $screen, 'side', 'high' );
	}

	public function render_metabox( $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$number = $this->module->number_for( $order );
		$row    = $this->module->sequence()->find_by_order( $order->get_id() );
		$action = static fn( string $op ) => wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_invoice_generate', 'order_id' => $order->get_id(), 'op' => $op ], admin_url( 'admin-post.php' ) ), 'webgram_core_invoice_' . $order->get_id() );
		if ( '' !== $number ) {
			printf( '<p><strong>%s</strong><br><span class="description">%s</span></p>', esc_html( $number ), esc_html( $row ? wp_date( get_option( 'date_format' ), strtotime( $row['created_at'] ) ?: null ) : '' ) );
			printf( '<p><a class="button button-primary" href="%s" target="_blank">%s</a> <a class="button" href="%s">%s</a></p>', esc_url( $this->module->download_url( $order ) ), esc_html__( 'Download', 'webgram-core' ), esc_url( $action( 'regenerate' ) ), esc_html__( 'Regenerate PDF', 'webgram-core' ) );
		} else {
			printf( '<p class="description">%s</p><p><a class="button button-primary" href="%s">%s</a></p>', esc_html__( 'No invoice number yet.', 'webgram-core' ), esc_url( $action( 'generate' ) ), esc_html__( 'Generate invoice', 'webgram-core' ) );
		}
		if ( ! ( new DompdfGenerator() )->available() ) {
			echo '<p class="description">' . esc_html__( 'dompdf is not installed (run composer install in the plugin folder); invoices open as printable HTML until then.', 'webgram-core' ) . '</p>';
		}
	}

	public function generate(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		$order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'webgram_core_invoice_' . $order_id );
		$order = wc_get_order( $order_id );
		$ok    = false;
		if ( $order instanceof \WC_Order ) {
			$op = isset( $_GET['op'] ) && 'regenerate' === $_GET['op'] ? 'regenerate' : 'generate';
			$ok = null !== $this->module->file_for( $order, 'regenerate' === $op, true );
		}
		set_transient( 'webgram_core_invoice_notice_' . get_current_user_id(), $ok ? 'ok' : 'fail', 60 );
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	public function notices(): void {
		$state = get_transient( 'webgram_core_invoice_notice_' . get_current_user_id() );
		if ( ! $state ) {
			return;
		}
		delete_transient( 'webgram_core_invoice_notice_' . get_current_user_id() );
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'ok' === $state ? 'success' : 'error', 'ok' === $state ? esc_html__( 'Invoice generated.', 'webgram-core' ) : esc_html__( 'The invoice could not be generated. Check the WooCommerce logs (source webgram-core).', 'webgram-core' ) );
	}

	public function bulk_action( array $actions ): array {
		$actions['wg_invoice_zip'] = __( 'Download invoices (zip)', 'webgram-core' );
		return $actions;
	}

	public function handle_bulk( string $redirect, string $action, array $ids ): string {
		if ( 'wg_invoice_zip' !== $action || ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'ZipArchive' ) ) {
			return $redirect;
		}
		$tmp = wp_tempnam( 'webgram-invoices.zip' );
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $tmp, \ZipArchive::OVERWRITE ) ) {
			return $redirect;
		}
		$added = 0;
		foreach ( array_slice( array_map( 'intval', $ids ), 0, 200 ) as $id ) {
			$order = wc_get_order( $id );
			$file  = $order instanceof \WC_Order ? $this->module->file_for( $order, false, true ) : null;
			if ( $file ) {
				$zip->addFile( $file['path'], basename( $file['path'] ) );
				++$added;
			}
		}
		$zip->close();
		if ( ! $added ) {
			wp_delete_file( $tmp );
			return $redirect;
		}
		$token = wp_generate_password( 20, false );
		set_transient( 'webgram_core_zip_' . $token, $tmp, 10 * MINUTE_IN_SECONDS );
		return add_query_arg( [ 'action' => 'webgram_core_invoice_zip', 'token' => $token, '_wpnonce' => wp_create_nonce( 'webgram_core_invoice_zip' ) ], admin_url( 'admin-post.php' ) );
	}

	public function serve_zip(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_core_invoice_zip' );
		$token = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : '';
		$path  = (string) get_transient( 'webgram_core_zip_' . $token );
		if ( '' === $path || ! is_file( $path ) ) {
			wp_die( esc_html__( 'The download has expired. Run the bulk action again.', 'webgram-core' ) );
		}
		delete_transient( 'webgram_core_zip_' . $token );
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="invoices-' . gmdate( 'Ymd-His' ) . '.zip"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		wp_delete_file( $path );
		exit;
	}
}
