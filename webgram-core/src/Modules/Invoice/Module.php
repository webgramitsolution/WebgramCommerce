<?php
namespace Webgram\Core\Modules\Invoice;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Invoices: gap-free numbering in wg_invoice_sequence, normalized data from WC_Order, A4 template rendered by
 * dompdf (HTML fallback), protected storage, REST download with ownership check, admin and My Account actions,
 * email attachment provider. Numbers are never regenerated once assigned.
 */
final class Module extends BaseModule {

	private ?SequenceRepository $sequence = null;
	private ?Storage $storage             = null;

	public function id(): string {
		return 'invoice';
	}

	public function name(): string {
		return __( 'Invoices', 'webgram-core' );
	}

	public function description(): string {
		return __( 'GST ready PDF invoices with configurable numbering, download from admin, My Account and emails.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 7;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function activate(): void {
		$this->sequence()->install();
	}

	public function sequence(): SequenceRepository {
		return $this->sequence ??= new SequenceRepository();
	}

	public function storage(): Storage {
		return $this->storage ??= new Storage();
	}

	public function boot(): void {
		$trigger = (string) $this->settings()->get( 'trigger', 'payment_complete' );
		if ( 'payment_complete' === $trigger ) {
			add_action( 'woocommerce_payment_complete', [ $this, 'auto_assign' ] );
		} elseif ( 'processing' === $trigger ) {
			add_action( 'woocommerce_order_status_processing', [ $this, 'auto_assign' ] );
			add_action( 'woocommerce_order_status_completed', [ $this, 'auto_assign' ] );
		}
		add_filter( 'webgram_core/rest_controllers', fn( array $c ) => array_merge( $c, [ new Rest\InvoiceController( $this ) ] ) );
		add_filter( 'woocommerce_my_account_my_orders_actions', [ $this, 'account_action' ], 10, 2 );
		add_action( 'webgram/thankyou/after_details', [ $this, 'thankyou_button' ] );
		add_action( 'woocommerce_thankyou', [ $this, 'thankyou_fallback' ], 5 );
		add_action( 'webgram_core/product_panel/fields', [ $this, 'hsn_field' ] );
		add_action( 'webgram_core/product_panel/save', [ $this, 'hsn_save' ] );
		if ( is_admin() ) {
			( new Admin( $this ) )->register();
		}
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'h_store', 'label' => __( 'Store details', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'logo', 'label' => __( 'Logo', 'webgram-core' ), 'type' => 'image', 'default' => 0 ],
			[ 'id' => 'store_name', 'label' => __( 'Legal name', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'tagline', 'label' => __( 'Tagline', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'address', 'label' => __( 'Address', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'phone', 'label' => __( 'Phone', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'email', 'label' => __( 'Email', 'webgram-core' ), 'type' => 'email', 'default' => '' ],
			[ 'id' => 'website', 'label' => __( 'Website', 'webgram-core' ), 'type' => 'url', 'default' => '' ],
			[ 'id' => 'gstin', 'label' => __( 'GSTIN', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'pan', 'label' => __( 'PAN (optional)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'cin', 'label' => __( 'CIN (optional)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'support_line', 'label' => __( 'Support line', 'webgram-core' ), 'type' => 'text', 'default' => __( 'If you have any questions about this invoice, contact us at the email or phone above.', 'webgram-core' ) ],
			[ 'id' => 'footer_note', 'label' => __( 'Footer note', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'social', 'label' => __( 'Social handles (one per line)', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'h_number', 'label' => __( 'Numbering', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'format', 'label' => __( 'Format', 'webgram-core' ), 'type' => 'text', 'default' => Numbering::DEFAULT_FORMAT, 'description' => __( 'Placeholders: {prefix} {number} {suffix} {yyyy} {yy} {mm} {fy}. Example: WG-{fy}-{number} gives WG-2026-27-000123.', 'webgram-core' ) ],
			[ 'id' => 'prefix', 'label' => __( 'Prefix', 'webgram-core' ), 'type' => 'text', 'default' => 'WG-' ],
			[ 'id' => 'suffix', 'label' => __( 'Suffix', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'padding', 'label' => __( 'Number padding (digits)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 10, 'default' => 6 ],
			[ 'id' => 'start', 'label' => __( 'Starting number', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 99999999, 'default' => 1 ],
			[ 'id' => 'reset_yearly', 'label' => __( 'Restart numbering every year', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'fy_start', 'label' => __( 'Year type', 'webgram-core' ), 'type' => 'select', 'options' => [ '4' => __( 'Financial year (April to March)', 'webgram-core' ), '1' => __( 'Calendar year', 'webgram-core' ) ], 'default' => '4' ],
			[ 'id' => 'trigger', 'label' => __( 'Assign number when', 'webgram-core' ), 'type' => 'select', 'options' => [ 'payment_complete' => __( 'Payment completes', 'webgram-core' ), 'processing' => __( 'Order reaches Processing or Completed', 'webgram-core' ), 'manual' => __( 'Manually only', 'webgram-core' ) ], 'default' => 'payment_complete' ],
			[ 'id' => 'h_layout', 'label' => __( 'Layout', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'show_images', 'label' => __( 'Product thumbnails', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_sku', 'label' => __( 'SKU column', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_hsn', 'label' => __( 'HSN column (adds an HSN field to products)', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'show_shipping', 'label' => __( 'Shipping address block', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'zebra', 'label' => __( 'Zebra rows', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'color_accent', 'label' => __( 'Accent color', 'webgram-core' ), 'type' => 'color', 'default' => '#1f2937' ],
			[ 'id' => 'color_text', 'label' => __( 'Text color', 'webgram-core' ), 'type' => 'color', 'default' => '#111827' ],
			[ 'id' => 'disclaimer', 'label' => __( 'Disclaimer', 'webgram-core' ), 'type' => 'text', 'default' => __( 'This is a computer generated invoice and does not require a signature.', 'webgram-core' ) ],
		];
	}

	/** Assign the next number (idempotent). Returns the sequence row or null. */
	public function assign( \WC_Order $order ): ?array {
		$existing = $this->sequence()->find_by_order( $order->get_id() );
		if ( $existing && '' !== $existing['invoice_no'] && ! str_starts_with( $existing['invoice_no'], 'pending-' ) ) {
			return $existing;
		}
		$s   = $this->settings();
		$row = $this->sequence()->assign(
			$order->get_id(),
			function ( int $id, string $created_at, int $attempt ) use ( $s ): string {
				$date     = new \DateTimeImmutable( $created_at ?: 'now', new \DateTimeZone( 'UTC' ) );
				$fy_start = (int) $s->get( 'fy_start', 4 ) === 1 ? 1 : 4;
				$start    = max( 1, (int) $s->get( 'start', 1 ) );
				if ( Helpers::bool( $s->get( 'reset_yearly', false ) ) ) {
					$number = $start + $this->sequence()->count_before_in_period( $id, Numbering::period_start( $date, 4 === $fy_start, $fy_start ) ) + ( $attempt - 1 );
				} else {
					$number = $start + $id - 1;
				}
				return Numbering::format( (string) $s->get( 'format', Numbering::DEFAULT_FORMAT ), $number, (int) $s->get( 'padding', 6 ), $date, (string) $s->get( 'prefix', '' ), (string) $s->get( 'suffix', '' ), $fy_start );
			}
		);
		if ( $row ) {
			$order->update_meta_data( '_wg_invoice_number', $row['invoice_no'] );
			$order->update_meta_data( '_wg_invoice_date', $row['created_at'] );
			$order->save_meta_data();
			do_action( 'webgram_core/invoice/assigned', $order, $row );
		}
		return $row;
	}

	public function auto_assign( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->assign( $order );
		}
	}

	public function number_for( \WC_Order $order ): string {
		$row = $this->sequence()->find_by_order( $order->get_id() );
		return $row && ! str_starts_with( $row['invoice_no'], 'pending-' ) ? $row['invoice_no'] : '';
	}

	public function generator(): PdfGeneratorInterface {
		$generator = apply_filters( 'webgram_core/invoice/generator', new DompdfGenerator() );
		if ( ! $generator instanceof PdfGeneratorInterface || ! $generator->available() ) {
			$generator = new HtmlGenerator();
		}
		return $generator;
	}

	public function html( \WC_Order $order ): string {
		$row  = $this->sequence()->find_by_order( $order->get_id() );
		$date = $row ? wp_date( get_option( 'date_format' ), strtotime( $row['created_at'] ) ?: null ) : '';
		$data = InvoiceData::from_order( $order, $this->settings()->all(), $this->number_for( $order ), (string) $date );
		return $this->view( 'invoice', [ 'd' => $data ], false );
	}

	/**
	 * Path and MIME of the invoice file, generating it when missing. Returns null when no number is assigned and
	 * $create is false or the order is not eligible.
	 *
	 * @return array{path: string, mime: string}|null
	 */
	public function file_for( \WC_Order $order, bool $regenerate = false, bool $create = true ): ?array {
		$row = $this->sequence()->find_by_order( $order->get_id() );
		if ( ! $row || str_starts_with( $row['invoice_no'], 'pending-' ) ) {
			if ( ! $create || ! ( $order->is_paid() || current_user_can( 'manage_woocommerce' ) ) ) {
				return null;
			}
			$row = $this->assign( $order );
			if ( ! $row ) {
				return null;
			}
		}
		$generator = $this->generator();
		$path      = $this->storage()->path( $row['invoice_no'], $row['created_at'], $generator->extension() );
		if ( $regenerate || ! is_file( $path ) ) {
			$bytes = $generator->render( $this->html( $order ) );
			if ( '' === $bytes || ! $this->storage()->write( $path, $bytes ) ) {
				return null;
			}
			do_action( 'webgram_core/invoice/generated', $order, $path );
		}
		return [ 'path' => $path, 'mime' => $generator->mime() ];
	}

	/** For the Emails module: a PDF path or empty (HTML fallbacks are never attached). */
	public function attachment_for( \WC_Order $order ): string {
		$file = $this->file_for( $order, false, true );
		return $file && 'application/pdf' === $file['mime'] ? $file['path'] : '';
	}

	/** Pure: signed temporary token for guest downloads (WhatsApp document headers). */
	public function temp_token( int $order_id, int $expires_at ): string {
		return $expires_at . '.' . substr( \webgram_core()->crypto()->sign( 'invoice|' . $order_id . '|' . $expires_at ), 0, 32 );
	}

	public function token_valid( int $order_id, string $token, int $now ): bool {
		if ( ! preg_match( '/^(\d+)\.([a-f0-9]{32})$/', $token, $m ) || (int) $m[1] < $now ) {
			return false;
		}
		return hash_equals( substr( \webgram_core()->crypto()->sign( 'invoice|' . $order_id . '|' . (int) $m[1] ), 0, 32 ), $m[2] );
	}

	public function download_url( \WC_Order $order, bool $with_key = false, int $temp_seconds = 0 ): string {
		$url = rest_url( 'webgram/v1/invoice/' . $order->get_id() );
		if ( $temp_seconds > 0 ) {
			return add_query_arg( 'token', $this->temp_token( $order->get_id(), time() + $temp_seconds ), $url );
		}
		if ( $with_key ) {
			return add_query_arg( 'key', $order->get_order_key(), $url );
		}
		return add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), $url );
	}

	public function account_action( array $actions, \WC_Order $order ): array {
		if ( '' !== $this->number_for( $order ) || $order->is_paid() ) {
			$actions['wg_invoice'] = [ 'url' => $this->download_url( $order ), 'name' => __( 'Invoice', 'webgram-core' ) ];
		}
		return $actions;
	}

	public function thankyou_button( $order ): void {
		if ( $order instanceof \WC_Order && ( '' !== $this->number_for( $order ) || $order->is_paid() ) ) {
			printf( '<p class="wgc-invoice-link"><a class="wg-btn wg-btn--outline wg-btn--sm" href="%s">%s</a></p>', esc_url( $this->download_url( $order, true ) ), esc_html__( 'Download invoice', 'webgram-core' ) );
		}
	}

	public function thankyou_fallback( $order_id ): void {
		if ( did_action( 'webgram/thankyou/after_details' ) ) {
			return;
		}
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->thankyou_button( $order );
		}
	}

	public function hsn_field(): void {
		if ( Helpers::bool( $this->settings()->get( 'show_hsn', false ) ) && function_exists( 'woocommerce_wp_text_input' ) ) {
			woocommerce_wp_text_input( [ 'id' => '_wg_hsn', 'label' => __( 'HSN / SAC code', 'webgram-core' ), 'desc_tip' => true, 'description' => __( 'Printed on invoices when the HSN column is on.', 'webgram-core' ) ] );
		}
	}

	public function hsn_save( int $post_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- the product panel verifies its nonce before firing this action.
		if ( isset( $_POST['_wg_hsn'] ) ) {
			update_post_meta( $post_id, '_wg_hsn', sanitize_text_field( wp_unslash( $_POST['_wg_hsn'] ) ) );
		}
		// phpcs:enable
	}
}
