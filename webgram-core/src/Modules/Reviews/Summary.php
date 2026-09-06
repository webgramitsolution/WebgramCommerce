<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/** Rating summary maths. Data comes from WooCommerce's own cached rating counts, so no second cache is kept. */
final class Summary {

	/**
	 * Pure. $counts: stars => count (any subset of 1 to 5).
	 *
	 * @return array{average: float, total: int, rows: array<int, array{stars: int, count: int, percent: int}>}
	 */
	public static function compute( array $counts, ?float $average = null ): array {
		$total = 0;
		$sum   = 0;
		$clean = [];
		for ( $star = 5; $star >= 1; $star-- ) {
			$n              = max( 0, (int) ( $counts[ $star ] ?? 0 ) );
			$clean[ $star ] = $n;
			$total         += $n;
			$sum           += $n * $star;
		}
		$avg  = null !== $average ? $average : ( $total ? $sum / $total : 0.0 );
		$rows = [];
		foreach ( $clean as $star => $n ) {
			$rows[] = [ 'stars' => $star, 'count' => $n, 'percent' => $total ? (int) round( $n * 100 / $total ) : 0 ];
		}
		return [ 'average' => round( (float) $avg, 1 ), 'total' => $total, 'rows' => $rows ];
	}

	public static function for_product( \WC_Product $product ): array {
		$counts = (array) $product->get_rating_counts();
		$data   = self::compute( $counts, (float) $product->get_average_rating() );
		// Review count can exceed rated comments when ratings are optional; show WooCommerce's number.
		$data['total'] = max( $data['total'], (int) $product->get_review_count() );
		return (array) apply_filters( 'webgram_core/reviews/summary', $data, $product );
	}

	/** Pure: label "Showing 1-4 of 256 reviews" numbers. */
	public static function showing( int $page, int $per_page, int $total ): array {
		if ( $total <= 0 ) {
			return [ 'from' => 0, 'to' => 0, 'total' => 0 ];
		}
		$from = ( $page - 1 ) * $per_page + 1;
		$to   = min( $total, $page * $per_page );
		return [ 'from' => min( $from, $total ), 'to' => $to, 'total' => $total ];
	}
}
