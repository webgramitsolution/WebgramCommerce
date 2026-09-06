<?php
/**
 * Breadcrumb: Yoast or Rank Math when active, WooCommerce breadcrumb on shop pages, otherwise a simple theme trail.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

if ( is_front_page() ) {
	return;
}
echo '<nav class="wg-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'webgram' ) . '">';
if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
	rank_math_the_breadcrumbs();
} elseif ( function_exists( 'yoast_breadcrumb' ) && function_exists( 'YoastSEO' ) ) {
	yoast_breadcrumb( '<div class="wg-breadcrumb__list">', '</div>' );
} elseif ( function_exists( 'woocommerce_breadcrumb' ) && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
	woocommerce_breadcrumb( [ 'wrap_before' => '<div class="wg-breadcrumb__list">', 'wrap_after' => '</div>', 'delimiter' => '<span class="wg-breadcrumb__sep">/</span>' ] );
} else {
	$webgram_items = [ [ home_url( '/' ), __( 'Home', 'webgram' ) ] ];
	if ( is_singular() ) {
		$webgram_post = get_queried_object();
		if ( $webgram_post instanceof WP_Post ) {
			if ( 'post' === $webgram_post->post_type ) {
				$webgram_cat = get_the_category( $webgram_post->ID );
				if ( $webgram_cat ) {
					$webgram_items[] = [ get_category_link( $webgram_cat[0] ), $webgram_cat[0]->name ];
				}
			} elseif ( $webgram_post->post_parent ) {
				foreach ( array_reverse( get_post_ancestors( $webgram_post ) ) as $webgram_anc ) {
					$webgram_items[] = [ get_permalink( $webgram_anc ), get_the_title( $webgram_anc ) ];
				}
			}
			$webgram_items[] = [ '', get_the_title( $webgram_post ) ];
		}
	} elseif ( is_archive() ) {
		$webgram_items[] = [ '', wp_strip_all_tags( get_the_archive_title() ) ];
	} elseif ( is_search() ) {
		$webgram_items[] = [ '', sprintf( /* translators: %s: search query */ __( 'Search: %s', 'webgram' ), get_search_query() ) ];
	} elseif ( is_404() ) {
		$webgram_items[] = [ '', __( 'Page not found', 'webgram' ) ];
	}
	echo '<div class="wg-breadcrumb__list">';
	$webgram_last = count( $webgram_items ) - 1;
	foreach ( $webgram_items as $webgram_i => [ $webgram_url, $webgram_label ] ) {
		if ( $webgram_url && $webgram_i < $webgram_last ) {
			printf( '<a href="%s">%s</a><span class="wg-breadcrumb__sep">/</span>', esc_url( (string) $webgram_url ), esc_html( (string) $webgram_label ) );
		} else {
			printf( '<span aria-current="page">%s</span>', esc_html( (string) $webgram_label ) );
		}
	}
	echo '</div>';
}
echo '</nav>';
