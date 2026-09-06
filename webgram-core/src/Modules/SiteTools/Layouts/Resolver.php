<?php
namespace Webgram\Core\Modules\SiteTools\Layouts;

use Webgram\Core\Modules\SiteTools\Module;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Picks the layout for a template type on the current request and answers the theme's webgram/layout_for filter.
 */
final class Resolver {

	private array $resolved = [];

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'webgram/layout_for', [ $this, 'filter' ], 10, 2 );
		add_action( 'save_post_' . PostType::POST_TYPE, [ $this, 'flush' ] );
		add_action( 'deleted_post', [ $this, 'flush' ] );
		add_action( 'transition_post_status', [ $this, 'flush' ] );
	}

	public function flush(): void {
		\webgram_core()->cache()->flush_group( 'layouts' );
	}

	public function filter( int $current, string $type ): int {
		if ( $current > 0 ) {
			return $current;
		}
		return $this->for_type( $type );
	}

	/** Candidate layouts for a type, cached: [ [id, priority, rules, devices, login], ... ] ordered by priority desc. */
	public function candidates( string $type ): array {
		$cache = \webgram_core()->cache();
		$key   = 'candidates_' . sanitize_key( $type );
		$list  = $cache->get( $key, 'layouts' );
		if ( is_array( $list ) ) {
			return $list;
		}
		$ids  = get_posts(
			[
				'post_type'   => PostType::POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => 50,
				'fields'      => 'ids',
				'meta_key'    => PostType::META_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		$list = [];
		foreach ( $ids as $id ) {
			$list[] = [
				'id'       => (int) $id,
				'priority' => (int) get_post_meta( $id, PostType::META_PRIO, true ),
				'rules'    => (array) get_post_meta( $id, PostType::META_RULES, true ),
				'devices'  => (array) get_post_meta( $id, PostType::META_DEVICE, true ),
				'login'    => (string) get_post_meta( $id, PostType::META_LOGIN, true ) ?: 'any',
			];
		}
		usort( $list, static fn( $a, $b ) => $b['priority'] <=> $a['priority'] ?: $a['id'] <=> $b['id'] );
		$cache->set( $key, $list, DAY_IN_SECONDS, 'layouts' );
		return $list;
	}

	/** Pure selection over candidates and a context. */
	public static function pick( array $candidates, array $context ): int {
		foreach ( $candidates as $c ) {
			if ( ! Conditions::allowed( (array) $c['devices'], (string) $c['login'], $context ) ) {
				continue;
			}
			if ( Conditions::matches( (array) $c['rules'], $context ) ) {
				return (int) $c['id'];
			}
		}
		return 0;
	}

	public function for_type( string $type ): int {
		if ( isset( $this->resolved[ $type ] ) ) {
			return $this->resolved[ $type ];
		}
		$candidates             = $this->candidates( $type );
		$this->resolved[ $type ] = $candidates ? self::pick( $candidates, self::context() ) : 0;
		return $this->resolved[ $type ];
	}

	/** Context of the current request for the condition engine. */
	public static function context(): array {
		$ctx = [
			'front_page' => is_front_page(),
			'shop'       => function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ),
			'blog'       => is_home() || is_category() || is_tag() || is_singular( 'post' ) || is_author() || is_date(),
			'search'     => is_search(),
			'404'        => is_404(),
			'post_id'    => 0,
			'post_type'  => '',
			'terms'      => [],
			'device'     => Helpers::device(),
			'logged_in'  => is_user_logged_in(),
		];
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$ctx['post_id']   = $post->ID;
				$ctx['post_type'] = $post->post_type;
				foreach ( [ 'product_cat', 'product_tag', 'product_brand', 'category', 'post_tag' ] as $tax ) {
					if ( taxonomy_exists( $tax ) ) {
						$terms = get_the_terms( $post, $tax );
						if ( is_array( $terms ) ) {
							foreach ( $terms as $term ) {
								$ctx['terms'][ $tax ][] = (string) $term->term_id;
								$ctx['terms'][ $tax ][] = $term->slug;
								foreach ( get_ancestors( $term->term_id, $tax ) as $anc ) {
									$ctx['terms'][ $tax ][] = (string) $anc;
								}
							}
						}
					}
				}
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$ctx['terms'][ $term->taxonomy ] = array_merge( [ (string) $term->term_id, $term->slug ], array_map( 'strval', get_ancestors( $term->term_id, $term->taxonomy ) ) );
			}
		}
		return (array) apply_filters( 'webgram_core/layouts/context', $ctx );
	}
}
