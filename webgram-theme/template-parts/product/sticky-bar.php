<?php
/**
 * Mobile sticky bar: thumb, price, ADD TO CART and BUY NOW proxies that trigger the real form buttons.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
?>
<div class="wg-product-bar" data-wg-component="product-bar" hidden>
	<div class="wg-product-bar__info">
		<?php echo wp_get_attachment_image( (int) $product->get_image_id(), 'webgram-thumb', false, [ 'class' => 'wg-product-bar__thumb', 'loading' => 'lazy' ] ); ?>
		<div class="wg-product-bar__text">
			<span class="wg-product-bar__title"><?php echo esc_html( $product->get_name() ); ?></span>
			<span class="wg-product-bar__price wg-price" data-wg-bar-price><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		</div>
	</div>
	<div class="wg-product-bar__actions">
		<button type="button" class="wg-btn wg-btn--primary wg-product-bar__cart" data-wg-bar-cart><?php esc_html_e( 'Add to cart', 'webgram' ); ?></button>
		<button type="button" class="wg-btn wg-btn--secondary wg-product-bar__buy" data-wg-bar-buy hidden><?php esc_html_e( 'Buy now', 'webgram' ); ?></button>
	</div>
</div>
