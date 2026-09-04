<?php
/**
 * Product card: compact variant (64px thumb, title 2 lines, price, ADD button). Used by drawer recommendations,
 * reels and the assistant.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
?>
<article class="<?php echo esc_attr( $args['classes'] ); ?>" data-wg-component="product-card" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
	<a class="wg-card__media" href="<?php echo esc_url( $args['permalink'] ); ?>" tabindex="-1" aria-hidden="true">
		<?php echo wp_get_attachment_image( (int) $product->get_image_id(), 'webgram-thumb', false, [ 'class' => 'wg-card__img', 'loading' => 'lazy' ] ) ?: wc_placeholder_img( 'webgram-thumb' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<div class="wg-card__body">
		<h3 class="wg-card__title"><a href="<?php echo esc_url( $args['permalink'] ); ?>"><?php echo esc_html( $args['title'] ); ?></a></h3>
		<div class="wg-card__price wg-price">
			<?php if ( $args['price']['sale'] ) : ?>
				<span class="wg-price__sale"><?php echo wp_kses_post( $args['price']['sale'] ); ?></span>
				<?php if ( $args['price']['regular'] ) : ?><del class="wg-price__regular"><?php echo wp_kses_post( $args['price']['regular'] ); ?></del><?php endif; ?>
			<?php else : ?>
				<?php echo wp_kses_post( $args['price']['html'] ); ?>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) : ?>
		<a class="wg-btn wg-btn--sm wg-btn--secondary wg-card__add button product_type_simple add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( (string) $product->get_id() ); ?>" data-quantity="1" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product */ __( 'Add %s to cart', 'webgram' ), $args['title'] ) ); ?>" rel="nofollow"><?php esc_html_e( 'ADD', 'webgram' ); ?></a>
	<?php else : ?>
		<a class="wg-btn wg-btn--sm wg-btn--outline wg-card__add" href="<?php echo esc_url( $args['permalink'] ); ?>"><?php esc_html_e( 'View', 'webgram' ); ?></a>
	<?php endif; ?>
</article>
