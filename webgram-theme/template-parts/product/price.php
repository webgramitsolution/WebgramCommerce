<?php
/**
 * PDP price line: sale price, strike regular, percent off, rating pill at the end. $args: product.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
$parts   = Webgram_WC_Product_Card::price_parts( $product );
$save    = Webgram_WC_Product_Card::savings( $product );
$rating  = wc_review_ratings_enabled() ? (float) $product->get_average_rating() : 0.0;
?>
<div class="wg-product__price-line">
	<div class="wg-product__price wg-price" data-wg-product-price>
		<?php if ( $parts['sale'] ) : ?>
			<span class="wg-price__sale"><?php echo wp_kses_post( $parts['sale'] ); ?></span>
			<?php
			if ( $parts['regular'] ) :
?>
<del class="wg-price__regular"><?php echo wp_kses_post( $parts['regular'] ); ?></del><?php endif; ?>
			<?php
			if ( $save && (int) $save['percent'] > 0 ) :
?>
<span class="wg-price__percent"><?php echo $product->is_type( 'variable' ) ? esc_html( sprintf( /* translators: %d: percent. */ __( 'Up to %d%% OFF', 'webgram' ), (int) $save['percent'] ) ) : esc_html( (string) $save['percent'] ) . '% ' . esc_html__( 'OFF', 'webgram' ); ?></span><?php endif; ?>
		<?php else : ?>
			<?php echo wp_kses_post( $parts['html'] ); ?>
		<?php endif; ?>
	</div>
	<?php echo Webgram_WC_Product_Card::rating_pill( $rating, (int) $product->get_review_count(), '#reviews-anchor', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
