<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * One product query for every section, widget, block and shortcode. Sources: recent, best_selling, trending,
 * on_sale, featured, category, tag, ids. Results are cached by argument hash and flushed on product changes.
 */
final class ProductQuery {

	public const SOURCES = [ 'recent', 'best_selling', 'trending', 'on_sale', 'featured', 'category', 'tag', 'ids', 'random', 'top_rated' ];
	public const GROUP   = 'products';

	public static function register(): void {
		foreach ( [ 'save_post_product', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'woocommerce_product_set_stock_status', 'deleted_post', 'woocommerce_delete_product_transients' ] as $hook ) {
			add_action( $hook, [ self::class, 'flush' ] );
		}
	}

	public static function flush(): void {
		\webgram_core()->cache()->flush_group( self::GROUP );
	}

	/** Pure: normalize untrusted section arguments. */
	public static function normalize( array $args ): array {
		$source = (string) ( $args['source'] ?? 'recent' );
		$ids    = array_values( array_filter( array_map( 'intval', is_array( $args['ids'] ?? null ) ? $args['ids'] : explode( ',', (string) ( $args['ids'] ?? '' ) ) ) ) );
		$cats   = self::terms( $args['category'] ?? '' );
		$tags   = self::terms( $args['tag'] ?? '' );
		return [
			'source'   => in_array( $source, self::SOURCES, true ) ? $source : 'recent',
			'limit'    => max( 1, min( 48, (int) ( $args['limit'] ?? 8 ) ) ),
			'category' => $cats,
			'tag'      => $tags,
			'ids'      => $ids,
			'exclude'  => array_values( array_filter( array_map( 'intval', (array) ( $args['exclude'] ?? [] ) ) ) ),
			'in_stock' => filter_var( $args['in_stock'] ?? true, FILTER_VALIDATE_BOOLEAN ),
		];
	}

	/** @return array<int, string> slugs or numeric ids as strings */
	private static function terms( mixed $value ): array {
		$list = is_array( $value ) ? $value : explode( ',', (string) $value );
		return array_values( array_filter( array_map( static fn( $v ) => sanitize_title( trim( (string) $v ) ), $list ), 'strlen' ) );
	}

	/** Pure: wc_get_products arguments for a normalized argument set. */
	public static function args( array $a, ?string $source = null ): array {
		$source = $source ?? $a['source'];
		$args   = [
			'status'     => 'publish',
			'limit'      => $a['limit'],
			'return'     => 'ids',
			'exclude'    => $a['exclude'],
			'visibility' => 'catalog',
			'orderby'    => 'date',
			'order'      => 'DESC',
		];
		if ( $a['in_stock'] ) {
			$args['stock_status'] = 'instock';
		}
		if ( $a['category'] ) {
			$args['category'] = self::slugs( $a['category'], 'product_cat' );
		}
		if ( $a['tag'] ) {
			$args['tag'] = self::slugs( $a['tag'], 'product_tag' );
		}
		switch ( $source ) {
			case 'best_selling':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				break;
			case 'trending':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wg_trend_score'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				break;
			case 'on_sale':
				$args['include'] = function_exists( 'wc_get_product_ids_on_sale' ) ? array_map( 'intval', (array) wc_get_product_ids_on_sale() ) : [ 0 ];
				if ( ! $args['include'] ) {
					$args['include'] = [ 0 ];
				}
				break;
			case 'featured':
				$args['featured'] = true;
				break;
			case 'ids':
				$args['include'] = $a['ids'] ?: [ 0 ];
				$args['orderby'] = 'post__in';
				unset( $args['stock_status'] );
				break;
			case 'random':
				$args['orderby'] = 'rand';
				break;
			case 'top_rated':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				break;
		}
		return (array) apply_filters( 'webgram_core/product_query/args', $args, $a, $source );
	}

	/** Numeric term ids become slugs, which wc_get_products expects. */
	private static function slugs( array $terms, string $taxonomy ): array {
		$out = [];
		foreach ( $terms as $term ) {
			if ( is_numeric( $term ) && function_exists( 'get_term' ) ) {
				$t = get_term( (int) $term, $taxonomy );
				if ( $t instanceof \WP_Term ) {
					$out[] = $t->slug;
					continue;
				}
			}
			$out[] = (string) $term;
		}
		return $out;
	}

	/** @return int[] */
	public static function ids( array $args ): array {
		$a = self::normalize( $args );
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [];
		}
		$key = wp_json_encode( $a );
		return (array) \webgram_core()->cache()->remember(
			(string) $key,
			15 * MINUTE_IN_SECONDS,
			static function () use ( $a ): array {
				$ids = array_map( 'intval', (array) wc_get_products( self::args( $a ) ) );
				// Trending falls back to best sellers, then newest, so the section never renders short.
				if ( 'trending' === $a['source'] && count( $ids ) < $a['limit'] ) {
					foreach ( [ 'best_selling', 'recent' ] as $fallback ) {
						$fill = self::args( array_merge( $a, [ 'exclude' => array_merge( $a['exclude'], $ids ), 'limit' => $a['limit'] - count( $ids ) ] ), $fallback );
						$ids  = array_merge( $ids, array_map( 'intval', (array) wc_get_products( $fill ) ) );
						if ( count( $ids ) >= $a['limit'] ) {
							break;
						}
					}
				}
				return array_values( array_unique( $ids ) );
			},
			self::GROUP
		);
	}

	/** @return \WC_Product[] */
	public static function products( array $args ): array {
		$out = [];
		foreach ( self::ids( $args ) as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_visible() ) {
				$out[] = $product;
			}
		}
		return $out;
	}
}
