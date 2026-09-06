<?php
namespace Webgram\Core\Modules\Analytics;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * First party analytics: wg_events table, batched REST collector, server side events through
 * webgram_core/analytics/event (and the alias webgram_core/event), trending views for ProductQuery, retention,
 * privacy export and erase, admin reports with inline SVG bars.
 */
final class Module extends BaseModule {

	private ?EventRepository $events = null;

	public function id(): string {
		return 'analytics';
	}

	public function name(): string {
		return __( 'Analytics', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Reel plays, assistant conversations, wishlist adds and product views stored on your own site, with a simple dashboard.', 'webgram-core' );
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
		$this->repo()->install();
	}

	public function uninstall(): void {
		$this->repo()->drop();
	}

	public function repo(): EventRepository {
		return $this->events ??= new EventRepository();
	}

	public function boot(): void {
		add_filter( 'webgram_core/rest_controllers', fn( array $c ) => array_merge( $c, [ new Collector( $this ) ] ) );
		add_action( 'webgram_core/analytics/event', [ $this, 'server_event' ], 10, 3 );
		add_action( 'webgram_core/event', [ $this, 'server_event' ], 10, 3 );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
		add_filter( 'webgram_core/trending/views', [ $this, 'trending_views' ] );
		add_action( 'webgram_core_daily_maintenance', [ $this, 'retention' ] );
		add_action( 'init', [ $this, 'schedule' ] );
		add_action( 'wp', [ $this, 'product_view' ] );
		add_action( 'template_redirect', [ $this, 'search_event' ] );
		add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart_event' ], 10, 6 );
		add_action( 'woocommerce_thankyou', [ $this, 'purchase_event' ] );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'checkout_start_event' ] );
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'eraser' ] );
		if ( is_admin() ) {
			( new Reports( $this, $this->repo() ) )->register();
		}
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'enabled', 'label' => __( 'Collect browser events', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Reel plays, chat opens, product views. No IP addresses are stored; sessions are hashed.', 'webgram-core' ) ],
			[ 'id' => 'sample', 'label' => __( 'Sampling (percent of visitors)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 100, 'default' => 100 ],
			[ 'id' => 'track_admins', 'label' => __( 'Also track logged in staff', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'retention_days', 'label' => __( 'Keep events for (days)', 'webgram-core' ), 'type' => 'number', 'min' => 7, 'max' => 730, 'default' => 90 ],
		];
	}

	public function schedule(): void {
		if ( ! wp_next_scheduled( 'webgram_core_daily_maintenance' ) ) {
			wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', 'webgram_core_daily_maintenance' );
		}
	}

	public function enabled(): bool {
		if ( ! Helpers::bool( $this->settings()->get( 'enabled', true ) ) ) {
			return false;
		}
		if ( ! Helpers::bool( $this->settings()->get( 'track_admins', false ) ) && current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}
		return (bool) apply_filters( 'webgram_core/analytics/enabled', true );
	}

	/** @return string[] */
	public function allowed_events(): array {
		return array_values( array_unique( (array) apply_filters( 'webgram_core/analytics/allowed_events', Collector::ALLOWED ) ) );
	}

	public function frontend_data( array $data ): array {
		$data['analytics'] = [ 'enabled' => $this->enabled(), 'sample' => max( 1, min( 100, (int) $this->settings()->get( 'sample', 100 ) ) ) ];
		return $data;
	}

	private function session_hash(): string {
		$key = '';
		if ( function_exists( 'WC' ) && WC()->session && method_exists( WC()->session, 'get_customer_id' ) ) {
			$key = (string) WC()->session->get_customer_id();
		}
		if ( '' === $key ) {
			$key = isset( $_COOKIE['wg_ai_session'] ) ? (string) $_COOKIE['wg_ai_session'] : Helpers::ip_hash(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		return sha1( 'wg-analytics|' . $key . '|' . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : '' ) );
	}

	/** Store validated client events. */
	public function record( array $events ): int {
		if ( ! $events || ! $this->enabled() || ! $this->repo()->exists() ) {
			return 0;
		}
		$user = get_current_user_id();
		$hash = $this->session_hash();
		$rows = array_map( static fn( array $e ) => $e + [ 'user_id' => $user, 'session_hash' => $hash ], $events );
		return $this->repo()->insert_many( $rows );
	}

	/** Server side events from other modules: do_action( 'webgram_core/analytics/event', $event, $data, $module ). */
	public function server_event( string $event, array $data = [], string $module = '' ): void {
		if ( ! Helpers::bool( $this->settings()->get( 'enabled', true ) ) || ! $this->repo()->exists() ) {
			return;
		}
		$object_id   = (int) ( $data['product_id'] ?? $data['object_id'] ?? $data['comment_id'] ?? $data['conversation_id'] ?? 0 );
		$object_type = (string) ( $data['object_type'] ?? ( isset( $data['product_id'] ) ? 'product' : ( isset( $data['comment_id'] ) ? 'review' : ( isset( $data['conversation_id'] ) ? 'conversation' : '' ) ) ) );
		$batch       = Collector::validate( [ [ 'event' => $event, 'object_type' => $object_type, 'object_id' => $object_id, 'meta' => [ 'module' => $module ] + array_diff_key( $data, [ 'product_id' => 1, 'comment_id' => 1, 'conversation_id' => 1 ] ) ] ], $this->allowed_events() );
		if ( $batch ) {
			$this->repo()->insert_many( array_map( fn( array $e ) => $e + [ 'user_id' => get_current_user_id(), 'session_hash' => $this->session_hash() ], $batch ) );
		}
	}

	/** Search result pages: the term is kept short and never joined to a user. */
	public function search_event(): void {
		if ( is_admin() || wp_doing_ajax() || ! is_search() || ! $this->enabled() ) {
			return;
		}
		$term = trim( (string) get_search_query( false ) );
		if ( '' === $term || strlen( $term ) > 80 ) {
			return;
		}
		$this->server_event( 'search', [ 'object_type' => 'search', 'term' => mb_strtolower( $term ), 'results' => (int) $GLOBALS['wp_query']->found_posts, 'product' => isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ? 1 : 0 ], 'analytics' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/** Every add to cart (classic, AJAX and Store API) with the product and quantity. */
	public function add_to_cart_event( string $cart_item_key, int $product_id, int $quantity, int $variation_id ): void {
		$this->server_event( 'add_to_cart', [ 'product_id' => $variation_id ?: $product_id, 'parent_id' => $product_id, 'qty' => $quantity, 'buy_now' => ! empty( $_REQUEST['wg_buy_now'] ) ? 1 : 0 ], 'analytics' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['wg_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->server_event( 'buy_now', [ 'product_id' => $variation_id ?: $product_id ], 'analytics' );
		}
	}

	/** One purchase event per order, recorded on the thank you page (totals only, no customer data). */
	public function purchase_event( $order_id ): void {
		$order = wc_get_order( (int) $order_id );
		if ( ! $order || $order->get_meta( '_wg_analytics_purchase' ) ) {
			return;
		}
		$order->update_meta_data( '_wg_analytics_purchase', time() );
		$order->save_meta_data();
		$this->server_event( 'purchase', [ 'object_type' => 'order', 'object_id' => $order->get_id(), 'total' => (float) $order->get_total(), 'items' => (int) $order->get_item_count(), 'currency' => $order->get_currency(), 'payment' => (string) $order->get_payment_method() ], 'analytics' );
	}

	/** Checkout page reached with items in the cart (funnel step before purchase). */
	public function checkout_start_event(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() || ! $this->enabled() ) {
			return;
		}
		$this->server_event( 'checkout_start', [ 'object_type' => 'cart', 'items' => (int) WC()->cart->get_cart_contents_count(), 'subtotal' => (float) WC()->cart->get_subtotal() ], 'analytics' );
	}

	/** Product page views recorded server side (works without JavaScript), sampled like the client. */
	public function product_view(): void {
		if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_product' ) || ! is_product() || ! $this->enabled() ) {
			return;
		}
		$sample = max( 1, min( 100, (int) $this->settings()->get( 'sample', 100 ) ) );
		if ( wp_rand( 1, 100 ) > $sample ) {
			return;
		}
		$this->record( Collector::validate( [ [ 'event' => 'product_view', 'object_type' => 'product', 'object_id' => get_queried_object_id(), 'meta' => [] ] ], $this->allowed_events() ) );
	}

	public function trending_views( array $views ): array {
		return $views ?: $this->repo()->views_by_day( 14 );
	}

	public function retention(): void {
		if ( $this->repo()->exists() ) {
			$this->repo()->purge_older_than( max( 7, (int) $this->settings()->get( 'retention_days', 90 ) ) );
		}
	}

	public function exporter( array $exporters ): array {
		$exporters['webgram-analytics'] = [
			'exporter_friendly_name' => __( 'Webgram analytics events', 'webgram-core' ), 'callback' => function ( string $email ): array {
			$user = get_user_by( 'email', $email );
			$n    = $user ? $this->repo()->count_for_user( (int) $user->ID ) : 0;
			$data = $n ? [ [ 'group_id' => 'webgram-analytics', 'group_label' => __( 'Analytics', 'webgram-core' ), 'item_id' => 'wg-events', 'data' => [ [ 'name' => __( 'Stored events', 'webgram-core' ), 'value' => (string) $n ] ] ] ] : [];
			return [ 'data' => $data, 'done' => true ];
		},
		];
		return $exporters;
	}

	public function eraser( array $erasers ): array {
		$erasers['webgram-analytics'] = [
			'eraser_friendly_name' => __( 'Webgram analytics events', 'webgram-core' ), 'callback' => function ( string $email ): array {
			$user    = get_user_by( 'email', $email );
			$removed = $user ? $this->repo()->delete_for_user( (int) $user->ID ) > 0 : false;
			return [ 'items_removed' => $removed, 'items_retained' => false, 'messages' => [], 'done' => true ];
		},
		];
		return $erasers;
	}
}
