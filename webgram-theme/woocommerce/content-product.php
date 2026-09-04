<?php
/**
 * WooCommerce loop item. Delegates to the theme card renderer so the shop loop and every other product listing
 * share one markup.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}

echo '<li class="wg-loop__item">';
Webgram_WC_Product_Card::render( $product );
echo '</li>';
