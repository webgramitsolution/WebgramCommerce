<?php
namespace Webgram\Core\Modules\Notifications;

defined( 'ABSPATH' ) || exit;

/** Order events, their WooCommerce hooks and emails, and the custom shipped and out for delivery statuses. */
final class Events {

	public const SHIPPED  = 'shipped';
	public const OUT      = 'out-for-delivery';

	/** @return array<string, array{label: string, wc_emails: string[], default: bool}> */
	public static function all(): array {
		return (array) apply_filters(
			'webgram_core/notifications/events',
			[
				'order_placed'       => [ 'label' => __( 'Order placed', 'webgram-core' ), 'wc_emails' => [ 'customer_processing_order', 'customer_on_hold_order' ], 'default' => true ],
				'payment_successful' => [ 'label' => __( 'Payment successful', 'webgram-core' ), 'wc_emails' => [], 'default' => true ],
				'processing'         => [ 'label' => __( 'Processing', 'webgram-core' ), 'wc_emails' => [], 'default' => false ],
				'shipped'            => [ 'label' => __( 'Shipped', 'webgram-core' ), 'wc_emails' => [], 'default' => true ],
				'out_for_delivery'   => [ 'label' => __( 'Out for delivery', 'webgram-core' ), 'wc_emails' => [], 'default' => true ],
				'completed'          => [ 'label' => __( 'Delivered (completed)', 'webgram-core' ), 'wc_emails' => [ 'customer_completed_order' ], 'default' => true ],
				'cancelled'          => [ 'label' => __( 'Cancelled', 'webgram-core' ), 'wc_emails' => [ 'customer_cancelled_order' ], 'default' => true ],
				'failed'             => [ 'label' => __( 'Payment failed', 'webgram-core' ), 'wc_emails' => [ 'customer_failed_order' ], 'default' => false ],
				'refunded'           => [ 'label' => __( 'Refunded', 'webgram-core' ), 'wc_emails' => [ 'customer_refunded_order' ], 'default' => true ],
			]
		);
	}

	/** Pure: WooCommerce status (without wc- prefix) to event name, '' when none. */
	public static function event_for_status( string $status, array $shipped_statuses = [] ): string {
		$status = str_replace( 'wc-', '', $status );
		if ( in_array( $status, array_merge( [ self::SHIPPED ], $shipped_statuses ), true ) ) {
			return 'shipped';
		}
		return match ( $status ) {
			self::OUT   => 'out_for_delivery',
			'processing' => 'processing',
			'completed' => 'completed',
			'cancelled' => 'cancelled',
			'failed'    => 'failed',
			'refunded'  => 'refunded',
			default     => '',
		};
	}

	public function __construct( private Module $module, private Queue $queue ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_statuses' ] );
		add_filter( 'wc_order_statuses', [ $this, 'order_statuses' ] );
		add_filter( 'bulk_actions-edit-shop_order', [ $this, 'bulk_statuses' ] );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', [ $this, 'bulk_statuses' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'order_placed' ] );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'order_placed_object' ] );
		add_action( 'woocommerce_payment_complete', [ $this, 'payment_successful' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'status_changed' ], 10, 3 );
		add_action( 'woocommerce_order_fully_refunded', [ $this, 'refunded' ] );
	}

	public function register_statuses(): void {
		register_post_status( 'wc-' . self::SHIPPED, [ 'label' => _x( 'Shipped', 'Order status', 'webgram-core' ), 'public' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'webgram-core' ) ] );
		if ( \Webgram\Core\Support\Helpers::bool( $this->module->settings()->get( 'status_out_for_delivery', true ) ) ) {
			register_post_status( 'wc-' . self::OUT, [ 'label' => _x( 'Out for delivery', 'Order status', 'webgram-core' ), 'public' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Out for delivery <span class="count">(%s)</span>', 'Out for delivery <span class="count">(%s)</span>', 'webgram-core' ) ] );
		}
	}

	public function order_statuses( array $statuses ): array {
		$out = [];
		foreach ( $statuses as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$out[ 'wc-' . self::SHIPPED ] = _x( 'Shipped', 'Order status', 'webgram-core' );
				if ( \Webgram\Core\Support\Helpers::bool( $this->module->settings()->get( 'status_out_for_delivery', true ) ) ) {
					$out[ 'wc-' . self::OUT ] = _x( 'Out for delivery', 'Order status', 'webgram-core' );
				}
			}
		}
		return $out;
	}

	public function bulk_statuses( array $actions ): array {
		$actions[ 'mark_' . self::SHIPPED ] = __( 'Change status to shipped', 'webgram-core' );
		if ( \Webgram\Core\Support\Helpers::bool( $this->module->settings()->get( 'status_out_for_delivery', true ) ) ) {
			$actions[ 'mark_' . self::OUT ] = __( 'Change status to out for delivery', 'webgram-core' );
		}
		return $actions;
	}

	public function order_placed( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->queue->enqueue( $order, 'order_placed' );
		}
	}

	public function order_placed_object( $order ): void {
		if ( $order instanceof \WC_Order ) {
			$this->queue->enqueue( $order, 'order_placed' );
		}
	}

	public function payment_successful( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->queue->enqueue( $order, 'payment_successful' );
		}
	}

	public function status_changed( $order_id, string $old, string $new ): void {
		$event = self::event_for_status( $new, array_map( 'strval', (array) apply_filters( 'webgram_core/notifications/shipped_statuses', [] ) ) );
		if ( '' === $event || 'refunded' === $event ) {
			return;
		}
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->queue->enqueue( $order, $event );
		}
	}

	public function refunded( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( $order instanceof \WC_Order ) {
			$this->queue->enqueue( $order, 'refunded' );
		}
	}
}
