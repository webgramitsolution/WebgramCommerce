<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\RestController;
use Webgram\Core\Support\Helpers;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Track order: shortcode form plus REST POST webgram/v1/track-order (nonce + rate limit). Looks up by order number
 * and billing email or phone through WC_Order only; returns a limited, non-sensitive payload with a status timeline
 * and shipment data contributed by shipping plugins via webgram_core/track_order/data.
 */
final class TrackOrder extends RestController {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_shortcode( 'webgram_track_order', [ $this, 'shortcode' ] );
		add_filter( 'webgram_core/rest_controllers', fn( array $c ) => [ ...$c, $this ] );
	}

	public function register_routes(): void {
		$this->route(
			'/track-order',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'track' ],
					'permission_callback' => $this->require_nonce( 'wp_rest' ),
					'args'                => [
						'order'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
						'contact' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
					],
				],
			]
		);
	}

	public function shortcode(): string {
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		return \webgram_core()->view( 'woo-enhancements/track-order', [], false );
	}

	/** Pure: does the given contact match the order's billing email or phone? */
	public static function contact_matches( string $contact, string $email, string $phone, string $calling_code = '91' ): bool {
		$contact = trim( $contact );
		if ( '' === $contact ) {
			return false;
		}
		if ( str_contains( $contact, '@' ) ) {
			return strtolower( $contact ) === strtolower( trim( $email ) );
		}
		$a = Helpers::to_e164( $contact, $calling_code );
		$b = Helpers::to_e164( $phone, $calling_code );
		if ( '' !== $a && '' !== $b ) {
			return $a === $b;
		}
		$da = preg_replace( '/\D/', '', $contact );
		$db = preg_replace( '/\D/', '', $phone );
		return '' !== $da && strlen( $da ) >= 8 && ( str_ends_with( $db, $da ) || str_ends_with( $da, $db ) );
	}

	/** Timeline steps with completed flags (pure). */
	public static function timeline( string $status, array $dates = [] ): array {
		$steps = [ 'placed', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered' ];
		$labels = [
			'placed'           => __( 'Order placed', 'webgram-core' ),
			'confirmed'        => __( 'Confirmed', 'webgram-core' ),
			'packed'           => __( 'Packed', 'webgram-core' ),
			'shipped'          => __( 'Shipped', 'webgram-core' ),
			'out_for_delivery' => __( 'Out for delivery', 'webgram-core' ),
			'delivered'        => __( 'Delivered', 'webgram-core' ),
		];
		$reached = match ( $status ) {
			'pending', 'failed' => 0,
			'on-hold'           => 1,
			'processing'        => 2,
			'packed'            => 3,
			'shipped'           => 4,
			'out-for-delivery'  => 5,
			'completed'         => 6,
			default             => 0,
		};
		$reached = (int) apply_filters( 'webgram_core/track_order/reached_step', $reached, $status );
		$out     = [];
		foreach ( $steps as $i => $key ) {
			$out[] = [ 'key' => $key, 'label' => $labels[ $key ], 'done' => $i < $reached, 'current' => $i === $reached - 1, 'date' => $dates[ $key ] ?? '' ];
		}
		return $out;
	}

	public function track( WP_REST_Request $request ) {
		if ( ! Helpers::rate_limit( 'track_order', 10, HOUR_IN_SECONDS ) ) {
			return $this->fail( 'rate_limited', __( 'Too many attempts. Please try again in an hour.', 'webgram-core' ), 429 );
		}
		$order_ref = (string) $request->get_param( 'order' );
		$contact   = (string) $request->get_param( 'contact' );
		$order_id  = (int) preg_replace( '/\D/', '', $order_ref );
		$order     = $order_id > 0 ? wc_get_order( $order_id ) : false;
		if ( ! $order ) {
			$found = wc_get_orders( [ 'limit' => 1, 'return' => 'objects', 'search' => $order_ref ] ) ?: [];
			$order = $found[0] ?? false;
		}
		$order = (int) apply_filters( 'webgram_core/track_order/order_id', $order ? $order->get_id() : 0, $order_ref ) ? $order : false;
		$code  = Helpers::calling_code( (string) \webgram_core()->settings()->get( 'default_country', 'IN' ) ) ?: '91';
		if ( ! $order instanceof \WC_Order || ! self::contact_matches( $contact, $order->get_billing_email(), $order->get_billing_phone(), $code ) ) {
			return $this->fail( 'not_found', __( 'We could not find an order with those details. Check the order number and the email or phone used at checkout.', 'webgram-core' ), 404 );
		}

		$status = $order->get_status();
		$dates  = [ 'placed' => $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '' ];
		if ( $order->get_date_paid() ) {
			$dates['confirmed'] = wc_format_datetime( $order->get_date_paid() );
		}
		if ( $order->get_date_completed() ) {
			$dates['delivered'] = wc_format_datetime( $order->get_date_completed() );
		}
		$items = [];
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = [
				'name'  => $item->get_name(),
				'qty'   => $item->get_quantity(),
				'image' => $product ? (string) wp_get_attachment_image_url( (int) $product->get_image_id(), 'thumbnail' ) : '',
			];
		}
		$data = [
			'order_number' => $order->get_order_number(),
			'status'       => $status,
			'status_label' => wc_get_order_status_name( $status ),
			'timeline'     => self::timeline( $status, $dates ),
			'items'        => $items,
			'shipping'     => $order->get_shipping_method(),
			'carrier'      => '',
			'tracking'     => '',
			'tracking_url' => '',
			'eta'          => '',
		];
		// Common shipment tracking metas (WooCommerce Shipment Tracking, AST, Shiprocket) plus a filter for others.
		$st = $order->get_meta( '_wc_shipment_tracking_items' );
		if ( is_array( $st ) && $st ) {
			$last = end( $st );
			$data['carrier']      = (string) ( $last['tracking_provider'] ?? $last['custom_tracking_provider'] ?? '' );
			$data['tracking']     = (string) ( $last['tracking_number'] ?? '' );
			$data['tracking_url'] = (string) ( $last['custom_tracking_link'] ?? '' );
		} elseif ( $order->get_meta( '_tracking_number' ) ) {
			$data['tracking'] = (string) $order->get_meta( '_tracking_number' );
			$data['carrier']  = (string) $order->get_meta( '_tracking_provider' );
		}
		$data = (array) apply_filters( 'webgram_core/track_order/data', $data, $order );
		return $this->ok( $data );
	}
}
