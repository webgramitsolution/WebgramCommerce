<?php
/**
 * Slide cart drawer shell (right, 420px). Content is a WooCommerce fragment (cart/drawer-content).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wg-slide-cart" class="wg-drawer wg-drawer--right wg-drawer--cart" data-wg-component="slide-cart" data-wg-drawer="slide-cart" hidden>
	<?php webgram_part( 'cart/drawer-content' ); ?>
</div>
