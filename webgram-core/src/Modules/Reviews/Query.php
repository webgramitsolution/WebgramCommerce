<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/** Builds and runs the review list query (sort, star filter, with media, pagination). */
final class Query {

	public const SORTS = [ 'newest', 'oldest', 'highest', 'lowest', 'helpful', 'media' ];

	/** Pure: normalize untrusted list parameters. */
	public static function params( array $raw, int $default_per_page ): array {
		$sort  = (string) ( $raw['sort'] ?? 'newest' );
		$stars = (int) ( $raw['stars'] ?? 0 );
		return [
			'sort'     => in_array( $sort, self::SORTS, true ) ? $sort : 'newest',
			'stars'    => $stars >= 1 && $stars <= 5 ? $stars : 0,
			'media'    => filter_var( $raw['media'] ?? false, FILTER_VALIDATE_BOOLEAN ),
			'page'     => max( 1, (int) ( $raw['page'] ?? 1 ) ),
			'per_page' => max( 1, min( 50, (int) ( $raw['per_page'] ?? $default_per_page ) ) ),
		];
	}

	/** Pure: WP_Comment_Query arguments for the given product and normalized params. */
	public static function args( int $product_id, array $p ): array {
		$meta = [ 'relation' => 'AND' ];
		if ( $p['stars'] > 0 ) {
			$meta['rating_filter'] = [ 'key' => 'rating', 'value' => $p['stars'], 'compare' => '=', 'type' => 'NUMERIC' ];
		}
		if ( $p['media'] ) {
			$meta['media_filter'] = [ 'key' => '_wg_media', 'compare' => 'EXISTS' ];
		}
		$orderby = [ 'comment_date_gmt' => 'DESC' ];
		switch ( $p['sort'] ) {
			case 'oldest':
				$orderby = [ 'comment_date_gmt' => 'ASC' ];
				break;
			case 'highest':
			case 'lowest':
				$meta['rating_sort'] = [ 'key' => 'rating', 'type' => 'NUMERIC', 'compare' => 'EXISTS' ];
				$orderby             = [ 'rating_sort' => 'highest' === $p['sort'] ? 'DESC' : 'ASC', 'comment_date_gmt' => 'DESC' ];
				break;
			case 'helpful':
				$meta[]  = [ 'relation' => 'OR', 'helpful_sort' => [ 'key' => '_wg_helpful', 'type' => 'NUMERIC', 'compare' => 'EXISTS' ], 'helpful_none' => [ 'key' => '_wg_helpful', 'compare' => 'NOT EXISTS' ] ];
				$orderby = [ 'helpful_sort' => 'DESC', 'comment_date_gmt' => 'DESC' ];
				break;
			case 'media':
				if ( ! $p['media'] ) {
					$meta[] = [ 'relation' => 'OR', 'media_sort' => [ 'key' => '_wg_media', 'compare' => 'EXISTS' ], 'media_none' => [ 'key' => '_wg_media', 'compare' => 'NOT EXISTS' ] ];
				}
				$orderby = [ $p['media'] ? 'comment_date_gmt' : 'media_sort' => 'DESC', 'comment_date_gmt' => 'DESC' ];
				break;
		}
		return [
			'post_id'  => $product_id,
			'status'   => 'approve',
			'type__in' => [ 'review', 'comment' ],
			'parent'   => 0,
			'number'   => $p['per_page'],
			'offset'   => ( $p['page'] - 1 ) * $p['per_page'],
			'orderby'  => $orderby,
			'meta_query' => count( $meta ) > 1 ? $meta : [], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		];
	}

	/** @return array{items: \WP_Comment[], total: int, page: int, per_page: int, pages: int} */
	public static function fetch( int $product_id, array $p ): array {
		$args  = (array) apply_filters( 'webgram_core/reviews/query_args', self::args( $product_id, $p ), $product_id, $p );
		$items = get_comments( $args );
		$count = (int) get_comments( array_merge( $args, [ 'count' => true, 'number' => 0, 'offset' => 0, 'orderby' => 'comment_date_gmt' ] ) );
		return [ 'items' => is_array( $items ) ? $items : [], 'total' => $count, 'page' => $p['page'], 'per_page' => $p['per_page'], 'pages' => $p['per_page'] ? (int) ceil( $count / $p['per_page'] ) : 1 ];
	}
}
