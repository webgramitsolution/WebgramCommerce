<?php
namespace Webgram\Core\Modules\Notifications;

defined( 'ABSPATH' ) || exit;

/** Variables for messages, WhatsApp template mapping per event, synced template list from Meta. */
final class Templates {

	public const OPTION_SYNCED = 'webgram_core_whatsapp_templates';

	public const VARIABLES = [ 'customer_name', 'order_number', 'order_total', 'currency', 'payment_method', 'order_status', 'billing_address', 'shipping_address', 'tracking_number', 'tracking_url', 'carrier', 'store_name', 'invoice_number', 'invoice_url', 'order_url', 'items_summary', 'eta' ];

	public function __construct( private Module $module ) {}

	/** Pure: "{customer_name}, {order_number}" style lists to ordered variable keys (unknown keys dropped). */
	public static function parse_params( string $list ): array {
		$out = [];
		foreach ( preg_split( '/[,\s]+/', trim( $list ) ) ?: [] as $key ) {
			$key = trim( $key, '{} ' );
			if ( in_array( $key, self::VARIABLES, true ) ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/** Pure: ordered parameter values for a template. */
	public static function params( array $keys, array $variables ): array {
		return array_map( static fn( string $k ) => (string) ( $variables[ $k ] ?? '' ), $keys );
	}

	/** Pure: replace {variables} in plain text. */
	public static function fill( string $text, array $variables ): string {
		$map = [];
		foreach ( $variables as $k => $v ) {
			$map[ '{' . $k . '}' ] = (string) $v;
		}
		return strtr( $text, $map );
	}

	/** All variables for an order and event. */
	public function variables( \WC_Order $order, string $event ): array {
		$tracking = (array) apply_filters( 'webgram_core/track_order/data', [], $order );
		$items    = [];
		foreach ( $order->get_items() as $item ) {
			$items[] = $item->get_quantity() . ' x ' . $item->get_name();
		}
		$invoice     = \webgram_core()->modules()->get( 'invoice' );
		$invoice_on  = $invoice && \webgram_core()->modules()->is_active( 'invoice' ) && method_exists( $invoice, 'number_for' );
		$variables   = [
			'customer_name'    => trim( $order->get_billing_first_name() ) ?: $order->get_formatted_billing_full_name(),
			'order_number'     => $order->get_order_number(),
			'order_total'      => wp_strip_all_tags( wc_price( (float) $order->get_total(), [ 'currency' => $order->get_currency() ] ) ),
			'currency'         => $order->get_currency(),
			'payment_method'   => $order->get_payment_method_title(),
			'order_status'     => wc_get_order_status_name( $order->get_status() ),
			'billing_address'  => wp_strip_all_tags( str_replace( '<br/>', ', ', $order->get_formatted_billing_address() ) ),
			'shipping_address' => wp_strip_all_tags( str_replace( '<br/>', ', ', $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address() ) ),
			'tracking_number'  => (string) ( $tracking['tracking_number'] ?? $order->get_meta( '_wg_tracking_number', true ) ),
			'tracking_url'     => (string) ( $tracking['tracking_url'] ?? $order->get_meta( '_wg_tracking_url', true ) ),
			'carrier'          => (string) ( $tracking['carrier'] ?? $order->get_meta( '_wg_carrier', true ) ),
			'store_name'       => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'invoice_number'   => $invoice_on ? (string) $invoice->number_for( $order ) : '',
			'invoice_url'      => $invoice_on && '' !== (string) $invoice->number_for( $order ) ? (string) $invoice->download_url( $order, false, 7 * DAY_IN_SECONDS ) : '',
			'order_url'        => $order->get_view_order_url(),
			'items_summary'    => mb_substr( implode( ', ', $items ), 0, 200 ),
			'eta'              => (string) ( $tracking['eta'] ?? $order->get_meta( '_wg_eta', true ) ),
		];
		return (array) apply_filters( 'webgram_core/notifications/variables', $variables, $order, $event );
	}

	/** Mapping row for an event from settings: name, language, params keys. */
	public function mapping( string $event ): array {
		$s = $this->module->settings();
		return [
			'name'     => sanitize_key( (string) $s->get( 'wa_tpl_' . $event, '' ) ),
			'language' => (string) ( $s->get( 'wa_lang_' . $event, '' ) ?: $s->get( 'wa_language', 'en' ) ),
			'params'   => self::parse_params( (string) $s->get( 'wa_params_' . $event, '{customer_name}, {order_number}' ) ),
			'document' => \Webgram\Core\Support\Helpers::bool( $s->get( 'wa_doc_' . $event, false ) ),
		];
	}

	/** @return array<int, array{name: string, language: string, status: string, category: string}> */
	public static function synced(): array {
		$list = get_option( self::OPTION_SYNCED, [] );
		return is_array( $list ) ? $list : [];
	}

	/** Choices for the settings selects: name => "name (language, STATUS)". */
	public static function choices(): array {
		$out = [ '' => __( 'Not mapped (no WhatsApp message)', 'webgram-core' ) ];
		foreach ( self::synced() as $t ) {
			$out[ $t['name'] ] = sprintf( '%s (%s, %s)', $t['name'], $t['language'], $t['status'] );
		}
		return $out;
	}

	/** Email subject and body for events WooCommerce has no email for. */
	public function email_content( string $event, array $variables ): array {
		$s       = $this->module->settings();
		$subject = (string) $s->get( 'email_subject_' . $event, '' );
		$body    = (string) $s->get( 'email_body_' . $event, '' );
		if ( '' === $subject ) {
			$subject = 'shipped' === $event ? __( 'Your order {order_number} has shipped', 'webgram-core' ) : __( 'Your order {order_number} is out for delivery', 'webgram-core' );
		}
		if ( '' === $body ) {
			$body = 'shipped' === $event
				? __( "Hi {customer_name},\n\nGood news: your order {order_number} from {store_name} is on its way.\nCarrier: {carrier}\nTracking: {tracking_number} {tracking_url}\n\nThank you for shopping with us.", 'webgram-core' )
				: __( "Hi {customer_name},\n\nYour order {order_number} is out for delivery today. Please keep your phone reachable.\n\nThank you for shopping with {store_name}.", 'webgram-core' );
		}
		return [ 'subject' => self::fill( $subject, $variables ), 'body' => self::fill( $body, $variables ) ];
	}
}
