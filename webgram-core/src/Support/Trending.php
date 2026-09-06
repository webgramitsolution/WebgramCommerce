<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Daily trending score per product stored in _wg_trend_score. Scores come from the Analytics module (product_view
 * events) through webgram_core/trending/scores; without it, recent sales weighted by recency are used.
 */
final class Trending {

	public const HOOK = 'webgram_core_trending_daily';
	public const META = '_wg_trend_score';

	public static function register(): void {
		add_action( self::HOOK, [ self::class, 'run' ] );
		add_filter( 'webgram_core/cron_hooks', static fn( array $hooks ) => array_merge( $hooks, [ self::HOOK ] ) );
		add_action( 'init', [ self::class, 'schedule' ] );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Pure: combine view counts and sales into a score. Views over the window count 1 each, sales 5 each,
	 * both decayed so newer signals matter more (weights per day: today 1.0 down to 0.3 at the window end).
	 *
	 * @param array<int, array<int, int>> $views product id => [days_ago => count]
	 * @param array<int, array<int, int>> $sales product id => [days_ago => count]
	 */
	public static function scores( array $views, array $sales, int $window_days = 14 ): array {
		$out   = [];
		$decay = static function ( int $days_ago ) use ( $window_days ): float {
			$days_ago = max( 0, min( $window_days, $days_ago ) );
			return 1.0 - 0.7 * ( $days_ago / max( 1, $window_days ) );
		};
		foreach ( [ 1 => $views, 5 => $sales ] as $weight => $set ) {
			foreach ( $set as $product_id => $buckets ) {
				foreach ( (array) $buckets as $days_ago => $count ) {
					$out[ (int) $product_id ] = ( $out[ (int) $product_id ] ?? 0.0 ) + $weight * (int) $count * $decay( (int) $days_ago );
				}
			}
		}
		arsort( $out );
		return array_map( static fn( float $v ) => round( $v, 2 ), $out );
	}

	public static function run(): void {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}
		$views  = (array) apply_filters( 'webgram_core/trending/views', [] );
		$sales  = self::recent_sales( 14 );
		$scores = self::scores( $views, $sales, 14 );
		$scores = (array) apply_filters( 'webgram_core/trending/scores', $scores, $views, $sales );
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( array_slice( $scores, 0, 500, true ) as $product_id => $score ) {
			update_post_meta( (int) $product_id, self::META, (float) $score );
		}
		ProductQuery::flush();
	}

	/** @return array<int, array<int, int>> product id => [days_ago => qty] from paid orders in the window */
	private static function recent_sales( int $days ): array {
		$orders = wc_get_orders( [ 'limit' => 500, 'status' => [ 'wc-processing', 'wc-completed' ], 'date_created' => '>' . ( time() - $days * DAY_IN_SECONDS ), 'return' => 'objects' ] );
		$out    = [];
		foreach ( (array) $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}
			$days_ago = (int) floor( ( time() - $order->get_date_created()->getTimestamp() ) / DAY_IN_SECONDS );
			foreach ( $order->get_items() as $item ) {
				if ( $item instanceof \WC_Order_Item_Product ) {
					$pid                          = (int) $item->get_product_id();
					$out[ $pid ][ $days_ago ]     = ( $out[ $pid ][ $days_ago ] ?? 0 ) + (int) $item->get_quantity();
				}
			}
		}
		return $out;
	}
}
