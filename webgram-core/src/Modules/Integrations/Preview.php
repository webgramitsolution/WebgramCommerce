<?php
namespace Webgram\Core\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/** Resolves the product a product-layout widget should render: the current one, or a preview product in editors. */
final class Preview {

	public static function product(): ?\WC_Product {
		global $product;
		if ( $product instanceof \WC_Product ) {
			return $product;
		}
		if ( function_exists( 'is_singular' ) && is_singular( 'product' ) ) {
			$p = wc_get_product( get_the_ID() );
			return $p ?: null;
		}
		$id = (int) apply_filters( 'webgram_core/integrations/preview_product', 0 );
		if ( $id <= 0 ) {
			$ids = wc_get_products( [ 'limit' => 1, 'status' => 'publish', 'return' => 'ids', 'orderby' => 'date', 'order' => 'DESC' ] );
			$id  = (int) ( $ids[0] ?? 0 );
		}
		$p = $id ? wc_get_product( $id ) : null;
		if ( $p ) {
			$GLOBALS['product'] = $p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$GLOBALS['post']    = get_post( $p->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $GLOBALS['post'] );
		}
		return $p ?: null;
	}
}
