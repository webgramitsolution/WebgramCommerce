<?php
/**
 * Theme-level AJAX: live search results. Uses wc-ajax when WooCommerce is present (no admin bootstrap) with an
 * admin-ajax fallback so search still works without WooCommerce. Read-only, nonce checked, cached 5 minutes.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_WC_Ajax {

	public static function init(): void {
		add_action( 'wc_ajax_webgram_live_search', [ self::class, 'live_search' ] );
		add_action( 'wp_ajax_webgram_live_search', [ self::class, 'live_search' ] );
		add_action( 'wp_ajax_nopriv_webgram_live_search', [ self::class, 'live_search' ] );
	}

	public static function live_search(): void {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'webgram_nonce' ) ) {
			wp_send_json( [ 'success' => false, 'message' => __( 'Session expired, please reload the page.', 'webgram' ) ], 403 );
		}
		$term = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$term = trim( mb_substr( $term, 0, 80 ) );
		if ( mb_strlen( $term ) < max( 1, (int) webgram_option( 'search_min_chars' ) ) ) {
			wp_send_json( [ 'success' => true, 'data' => [ 'products' => [], 'categories' => [], 'posts' => [] ] ] );
		}

		$key  = 'webgram_ls_' . md5( strtolower( $term ) . get_locale() );
		$data = get_transient( $key );
		if ( ! is_array( $data ) ) {
			$data = self::results( $term );
			set_transient( $key, $data, 5 * MINUTE_IN_SECONDS );
		}
		wp_send_json( [ 'success' => true, 'data' => $data ] );
	}

	/** @return array{products: array, categories: array, posts: array, view_all: string} */
	public static function results( string $term ): array {
		$limit = max( 3, min( 12, (int) webgram_option( 'search_results_count' ) ) );
		$out   = [ 'products' => [], 'categories' => [], 'posts' => [], 'view_all' => '' ];

		if ( class_exists( 'WooCommerce' ) ) {
			$out['view_all'] = add_query_arg( [ 's' => $term, 'post_type' => 'product' ], home_url( '/' ) );
			$store           = WC_Data_Store::load( 'product' );
			$ids             = (array) $store->search_products( $term, '', true, true, $limit * 2 );
			$ids             = array_filter( array_map( 'absint', $ids ) );
			if ( $ids ) {
				$products = wc_get_products( [ 'include' => $ids, 'limit' => $limit, 'status' => 'publish', 'orderby' => 'include' ] );
				foreach ( $products as $product ) {
					if ( ! $product->is_visible() ) {
						continue;
					}
					$out['products'][] = [
						'id'    => $product->get_id(),
						'title' => $product->get_name(),
						'url'   => $product->get_permalink(),
						'image' => (string) wp_get_attachment_image_url( (int) $product->get_image_id(), 'webgram-thumb' ) ?: wc_placeholder_img_src( 'webgram-thumb' ),
						'price' => wp_strip_all_tags( $product->get_price_html() ),
					];
				}
			}
			if ( webgram_option( 'search_categories' ) ) {
				$terms = get_terms( [ 'taxonomy' => 'product_cat', 'name__like' => $term, 'number' => 4, 'hide_empty' => true ] );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $cat ) {
						$out['categories'][] = [ 'name' => $cat->name, 'url' => (string) get_term_link( $cat ), 'count' => (int) $cat->count ];
					}
				}
			}
		} else {
			$out['view_all'] = add_query_arg( 's', $term, home_url( '/' ) );
		}

		if ( 'all' === webgram_option( 'search_scope' ) || ! class_exists( 'WooCommerce' ) ) {
			$query = new WP_Query( [ 's' => $term, 'post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish', 'no_found_rows' => true ] );
			foreach ( $query->posts as $post ) {
				$out['posts'][] = [ 'title' => get_the_title( $post ), 'url' => (string) get_permalink( $post ) ];
			}
		}

		return (array) apply_filters( 'webgram/live_search/results', $out, $term );
	}
}

Webgram_WC_Ajax::init();
