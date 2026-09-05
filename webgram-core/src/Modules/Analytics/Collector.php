<?php
namespace Webgram\Core\Modules\Analytics;

use Webgram\Core\Abstracts\RestController;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/** POST /events: batched client events, nonce protected, validated and sampled server side too. */
final class Collector extends RestController {

	public const ALLOWED = [ 'product_view', 'reel_impression', 'reel_play', 'reel_complete', 'reel_product_click', 'reel_add_to_cart', 'chat_open', 'chat_message', 'chat_product_click', 'chat_add_to_cart', 'wishlist_add', 'compare_add', 'review_helpful', 'search', 'voice_search', 'coupon_copy', 'quick_view', 'buy_now', 'add_to_cart' ];
	public const MAX_BATCH = 20;

	public function __construct( private Module $module ) {}

	public function register_routes(): void {
		$this->route( '/events', [ [ 'methods' => 'POST', 'callback' => [ $this, 'collect' ], 'permission_callback' => $this->require_nonce( 'wp_rest' ) ] ] );
	}

	/**
	 * Pure: validate a batch. Unknown events (not allowed and not registered through the filter) are dropped,
	 * meta is limited to scalar values and 20 keys, object ids to positive integers.
	 *
	 * @return array<int, array{event: string, object_type: string, object_id: int, meta: array}>
	 */
	public static function validate( array $events, array $allowed, int $max = self::MAX_BATCH ): array {
		$out = [];
		foreach ( array_slice( $events, 0, $max ) as $e ) {
			if ( ! is_array( $e ) ) {
				continue;
			}
			$event = sanitize_key( (string) ( $e['event'] ?? '' ) );
			if ( '' === $event || ! in_array( $event, $allowed, true ) ) {
				continue;
			}
			$meta = [];
			foreach ( array_slice( (array) ( $e['meta'] ?? [] ), 0, 20, true ) as $k => $v ) {
				$k = sanitize_key( (string) $k );
				if ( '' !== $k && is_scalar( $v ) && ! in_array( $k, [ 'email', 'phone', 'name', 'address', 'ip' ], true ) ) {
					$meta[ $k ] = is_string( $v ) ? mb_substr( sanitize_text_field( $v ), 0, 120 ) : $v;
				}
			}
			$out[] = [ 'event' => $event, 'object_type' => sanitize_key( (string) ( $e['object_type'] ?? '' ) ), 'object_id' => max( 0, (int) ( $e['object_id'] ?? 0 ) ), 'meta' => $meta ];
		}
		return $out;
	}

	public function collect( WP_REST_Request $request ) {
		$events = $request->get_param( 'events' );
		if ( ! is_array( $events ) ) {
			return $this->fail( 'invalid', __( 'No events.', 'webgram-core' ) );
		}
		if ( ! \Webgram\Core\Support\Helpers::rate_limit( 'analytics_collect', 60, MINUTE_IN_SECONDS ) ) {
			return $this->fail( 'rate_limited', __( 'Too many requests.', 'webgram-core' ), 429 );
		}
		$stored = $this->module->record( self::validate( $events, $this->module->allowed_events() ) );
		return $this->ok( [ 'stored' => $stored ] );
	}
}
