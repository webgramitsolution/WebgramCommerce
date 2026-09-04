<?php
namespace Webgram\Core\Modules\SiteTools\Layouts;

defined( 'ABSPATH' ) || exit;

/**
 * Pure assignment condition engine. A rule is [ 'op' => include|exclude, 'type' => ..., 'value' => string[] ].
 * The context is a plain array built from the current request (see Resolver::context()), so this class is
 * testable without WordPress.
 */
final class Conditions {

	/** @return array<string, string> */
	public static function types(): array {
		return (array) apply_filters(
			'webgram_core/layouts/condition_types',
			[
				'all'         => __( 'Entire site', 'webgram-core' ),
				'front_page'  => __( 'Front page', 'webgram-core' ),
				'shop'        => __( 'Shop page', 'webgram-core' ),
				'product'     => __( 'Products (ids)', 'webgram-core' ),
				'product_cat' => __( 'Product categories (ids or slugs)', 'webgram-core' ),
				'product_tag' => __( 'Product tags (ids or slugs)', 'webgram-core' ),
				'brand'       => __( 'Brands (ids or slugs)', 'webgram-core' ),
				'page'        => __( 'Pages (ids)', 'webgram-core' ),
				'post'        => __( 'Posts (ids)', 'webgram-core' ),
				'post_type'   => __( 'Post types (slugs)', 'webgram-core' ),
				'blog'        => __( 'Blog views', 'webgram-core' ),
				'search'      => __( 'Search results', 'webgram-core' ),
				'404'         => __( '404 page', 'webgram-core' ),
			]
		);
	}

	/** @return array<int, array{op: string, type: string, value: string[]}> */
	public static function sanitize( array $raw ): array {
		$rules = [];
		foreach ( $raw as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['type'] ) ) {
				continue;
			}
			$type = sanitize_key( (string) $rule['type'] );
			if ( ! array_key_exists( $type, self::types() ) ) {
				continue;
			}
			$value = $rule['value'] ?? '';
			$value = is_array( $value ) ? $value : explode( ',', (string) $value );
			$value = array_values( array_filter( array_map( static fn( $v ) => sanitize_title( trim( (string) $v ) ), $value ), 'strlen' ) );
			$rules[] = [
				'op'    => 'exclude' === ( $rule['op'] ?? '' ) ? 'exclude' : 'include',
				'type'  => $type,
				'value' => $value,
			];
		}
		return $rules;
	}

	/**
	 * @param array $rules   sanitized rules
	 * @param array $context keys: front_page, shop, blog, search, 404 (bool), post_id, post_type, terms (taxonomy => [id, slug...]), device, logged_in
	 */
	public static function matches( array $rules, array $context ): bool {
		$includes = array_filter( $rules, static fn( $r ) => 'include' === ( $r['op'] ?? 'include' ) );
		$excludes = array_filter( $rules, static fn( $r ) => 'exclude' === ( $r['op'] ?? '' ) );

		if ( ! $includes ) {
			return false;
		}
		foreach ( $excludes as $rule ) {
			if ( self::rule_matches( $rule, $context ) ) {
				return false;
			}
		}
		foreach ( $includes as $rule ) {
			if ( self::rule_matches( $rule, $context ) ) {
				return true;
			}
		}
		return false;
	}

	private static function rule_matches( array $rule, array $ctx ): bool {
		$type   = (string) ( $rule['type'] ?? '' );
		$values = array_map( 'strval', (array) ( $rule['value'] ?? [] ) );
		$has    = static fn( string $key ) => ! empty( $ctx[ $key ] );

		switch ( $type ) {
			case 'all':
				return true;
			case 'front_page':
			case 'shop':
			case 'blog':
			case 'search':
			case '404':
				return $has( $type );
			case 'product':
			case 'page':
			case 'post':
				if ( ( $ctx['post_type'] ?? '' ) !== $type || empty( $ctx['post_id'] ) ) {
					return false;
				}
				return ! $values || in_array( (string) $ctx['post_id'], $values, true );
			case 'post_type':
				return ! empty( $ctx['post_type'] ) && ( ! $values || in_array( (string) $ctx['post_type'], $values, true ) );
			case 'product_cat':
			case 'product_tag':
			case 'brand':
				$tax   = 'brand' === $type ? ( $ctx['brand_taxonomy'] ?? 'product_brand' ) : $type;
				$terms = array_map( 'strval', (array) ( $ctx['terms'][ $tax ] ?? [] ) );
				if ( ! $terms ) {
					return false;
				}
				return ! $values || (bool) array_intersect( $values, $terms );
		}
		return (bool) apply_filters( 'webgram_core/layouts/rule_matches', false, $rule, $ctx );
	}

	/** Device and login gates applied on top of the rules. */
	public static function allowed( array $devices, string $login_state, array $context ): bool {
		if ( $devices && ! in_array( (string) ( $context['device'] ?? 'desktop' ), $devices, true ) ) {
			return false;
		}
		if ( 'in' === $login_state && empty( $context['logged_in'] ) ) {
			return false;
		}
		if ( 'out' === $login_state && ! empty( $context['logged_in'] ) ) {
			return false;
		}
		return true;
	}
}
