<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** Normalized invoice array built from a WC_Order plus the module settings. Pure helpers are unit tested. */
final class InvoiceData {

	/**
	 * Pure: classify WooCommerce tax labels into CGST, SGST, IGST or other.
	 *
	 * @param array<int, array{label: string, amount: float, rate?: float}> $tax_lines
	 * @return array<int, array{label: string, kind: string, amount: float, rate: float}>
	 */
	public static function classify_taxes( array $tax_lines ): array {
		$out = [];
		foreach ( $tax_lines as $line ) {
			$label = (string) ( $line['label'] ?? '' );
			$kind  = 'other';
			foreach ( [ 'cgst', 'sgst', 'igst', 'utgst', 'vat', 'gst' ] as $needle ) {
				if ( false !== stripos( $label, $needle ) ) {
					$kind = strtoupper( $needle );
					break;
				}
			}
			$out[] = [ 'label' => $label, 'kind' => $kind, 'amount' => (float) ( $line['amount'] ?? 0 ), 'rate' => (float) ( $line['rate'] ?? 0 ) ];
		}
		return $out;
	}

	/** Pure: "Paid via Razorpay (UPI)" style payment line. */
	public static function payment_line( string $method_title, string $transaction_id, bool $paid ): string {
		if ( ! $paid ) {
			return $method_title ? sprintf( /* translators: %s: payment method */ __( 'Payment pending via %s', 'webgram-core' ), $method_title ) : __( 'Payment pending', 'webgram-core' );
		}
		$line = $method_title ? sprintf( /* translators: %s: payment method */ __( 'Paid via %s', 'webgram-core' ), $method_title ) : __( 'Paid', 'webgram-core' );
		return $transaction_id ? $line . ' (' . sprintf( /* translators: %s: transaction id */ __( 'Transaction ID: %s', 'webgram-core' ), $transaction_id ) . ')' : $line;
	}

	public static function from_order( \WC_Order $order, array $s, string $invoice_no, string $invoice_date ): array {
		$items    = [];
		$show_hsn = ! empty( $s['show_hsn'] );
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product   = $item->get_product();
			$qty       = max( 1, (int) $item->get_quantity() );
			$subtotal  = (float) $item->get_subtotal();
			$total     = (float) $item->get_total();
			$taxes     = [];
			$tax_data  = (array) $item->get_taxes();
			foreach ( (array) ( $tax_data['total'] ?? [] ) as $rate_id => $amount ) {
				$taxes[] = [ 'label' => \WC_Tax::get_rate_label( (int) $rate_id ), 'amount' => (float) $amount, 'rate' => (float) \WC_Tax::get_rate_percent_value( (int) $rate_id ) ];
			}
			$attributes = [];
			foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
				$attributes[] = wp_strip_all_tags( $meta->display_key ) . ': ' . wp_strip_all_tags( $meta->display_value );
			}
			$items[] = [
				'name'       => $item->get_name(),
				'variation'  => implode( ', ', $attributes ),
				'sku'        => $product ? (string) $product->get_sku() : '',
				'hsn'        => $show_hsn && $product ? (string) $product->get_meta( '_wg_hsn', true ) : '',
				'image'      => $product && ! empty( $s['show_images'] ) ? (string) wp_get_attachment_image_url( (int) $product->get_image_id(), 'thumbnail' ) : '',
				'qty'        => $qty,
				'unit_price' => $subtotal / $qty,
				'discount'   => max( 0, $subtotal - $total ),
				'taxes'      => self::classify_taxes( $taxes ),
				'total'      => $total,
			];
		}
		$tax_lines = [];
		foreach ( $order->get_tax_totals() as $code => $tax ) {
			$tax_lines[] = [ 'label' => (string) $tax->label, 'amount' => (float) $tax->amount, 'rate' => (float) ( $tax->rate_id ? \WC_Tax::get_rate_percent_value( (int) $tax->rate_id ) : 0 ) ];
		}
		$refunds = [];
		foreach ( $order->get_refunds() as $refund ) {
			$refunds[] = [ 'date' => $refund->get_date_created() ? $refund->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '', 'amount' => (float) $refund->get_amount(), 'reason' => (string) $refund->get_reason() ];
		}
		$paid   = $order->is_paid();
		$coupon = implode( ', ', array_map( 'strtoupper', $order->get_coupon_codes() ) );
		$data   = [
			'store'    => [
				'logo'     => ! empty( $s['logo'] ) ? (string) wp_get_attachment_image_url( (int) $s['logo'], 'medium' ) : '',
				'name'     => (string) ( $s['store_name'] ?: get_bloginfo( 'name' ) ),
				'tagline'  => (string) ( $s['tagline'] ?? '' ),
				'address'  => (string) ( $s['address'] ?? '' ),
				'phone'    => (string) ( $s['phone'] ?? '' ),
				'email'    => (string) ( $s['email'] ?: get_option( 'admin_email' ) ),
				'website'  => (string) ( $s['website'] ?: home_url( '/' ) ),
				'gstin'    => (string) ( $s['gstin'] ?? '' ),
				'pan'      => (string) ( $s['pan'] ?? '' ),
				'cin'      => (string) ( $s['cin'] ?? '' ),
				'support'  => (string) ( $s['support_line'] ?? '' ),
				'footer'   => (string) ( $s['footer_note'] ?? '' ),
				'social'   => (string) ( $s['social'] ?? '' ),
			],
			'customer' => [
				'name'     => $order->get_formatted_billing_full_name(),
				'billing'  => $order->get_formatted_billing_address(),
				'shipping' => ! empty( $s['show_shipping'] ) ? $order->get_formatted_shipping_address() : '',
				'email'    => $order->get_billing_email(),
				'phone'    => $order->get_billing_phone(),
			],
			'invoice'  => [ 'number' => $invoice_no, 'date' => $invoice_date ],
			'order'    => [
				'number'          => $order->get_order_number(),
				'date'            => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
				'payment_date'    => $order->get_date_paid() ? $order->get_date_paid()->date_i18n( get_option( 'date_format' ) ) : '',
				'status'          => wc_get_order_status_name( $order->get_status() ),
				'payment_method'  => $order->get_payment_method_title(),
				'transaction_id'  => (string) $order->get_transaction_id(),
				'payment_line'    => self::payment_line( $order->get_payment_method_title(), (string) $order->get_transaction_id(), $paid ),
				'shipping_method' => $order->get_shipping_method(),
				'paid'            => $paid,
				'note'            => (string) $order->get_customer_note(),
			],
			'items'    => $items,
			'totals'   => [
				'subtotal' => (float) $order->get_subtotal(),
				'discount' => (float) $order->get_total_discount(),
				'coupon'   => $coupon,
				'shipping' => (float) $order->get_shipping_total(),
				'fees'     => (float) $order->get_total_fees(),
				'taxes'    => self::classify_taxes( $tax_lines ),
				'total'    => (float) $order->get_total(),
				'refunded' => (float) $order->get_total_refunded(),
			],
			'refunds'  => $refunds,
			'currency' => $order->get_currency(),
			'disclaimer' => (string) ( $s['disclaimer'] ?? '' ),
			'generated'  => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'colors'     => [ 'accent' => (string) ( $s['color_accent'] ?: '#1f2937' ), 'text' => (string) ( $s['color_text'] ?: '#111827' ) ],
			'show'       => [ 'sku' => ! empty( $s['show_sku'] ), 'hsn' => $show_hsn, 'images' => ! empty( $s['show_images'] ), 'zebra' => ! empty( $s['zebra'] ) ],
		];
		return (array) apply_filters( 'webgram_core/invoice/data', $data, $order );
	}
}
