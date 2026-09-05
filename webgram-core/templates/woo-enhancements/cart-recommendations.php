<?php
/**
 * Cart drawer recommendations: horizontal row of compact cards with ADD buttons. $args: products, title.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'cart-reco' ) ); ?>" data-wgc-cart-reco>
	<div class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__head' ) ); ?>">
		<span><?php echo esc_html( $args['title'] ); ?></span>
		<span class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__arrows' ) ); ?>">
			<button type="button" data-wgc-reco-prev aria-label="<?php esc_attr_e( 'Previous', 'webgram-core' ); ?>">&lsaquo;</button>
			<button type="button" data-wgc-reco-next aria-label="<?php esc_attr_e( 'Next', 'webgram-core' ); ?>">&rsaquo;</button>
		</span>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__track' ) ); ?>" data-wgc-reco-track>
		<?php foreach ( $args['products'] as $wgc_product ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__item' ) ); ?>">
				<a class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__thumb' ) ); ?>" href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>" tabindex="-1"><?php echo $wgc_product->get_image( 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				<div class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__body' ) ); ?>">
					<a class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__title' ) ); ?>" href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php echo esc_html( $wgc_product->get_name() ); ?></a>
					<span class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__price' ) ); ?>"><?php echo wp_kses_post( $wgc_product->get_price_html() ); ?></span>
				</div>
				<?php if ( $wgc_product->is_type( 'simple' ) ) : ?>
					<a class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__add', 'button product_type_simple add_to_cart_button ajax_add_to_cart wg-btn wg-btn--secondary wg-btn--sm' ) ); ?>" href="<?php echo esc_url( $wgc_product->add_to_cart_url() ); ?>" data-product_id="<?php echo (int) $wgc_product->get_id(); ?>" data-quantity="1" rel="nofollow" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product */ __( 'Add %s to cart', 'webgram-core' ), $wgc_product->get_name() ) ); ?>"><?php esc_html_e( 'ADD', 'webgram-core' ); ?></a>
				<?php else : ?>
					<a class="<?php echo esc_attr( Helpers::css_class( 'cart-reco__add', 'wg-btn wg-btn--outline wg-btn--sm' ) ); ?>" href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php esc_html_e( 'View', 'webgram-core' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
