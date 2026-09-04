<?php
/**
 * Product card: standard variant.
 * Receives $args from Webgram_WC_Product_Card::render(): product, args, permalink, title, image, price, save, rating, reviews, classes.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
?>
<article class="<?php echo esc_attr( $args['classes'] ); ?>" data-wg-component="product-card" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
	<div class="wg-card__media">
		<a class="wg-card__link" href="<?php echo esc_url( $args['permalink'] ); ?>" aria-label="<?php echo esc_attr( $args['title'] ); ?>">
			<?php echo $args['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image output. ?>
		</a>

		<div class="wg-card__badges">
			<?php if ( $args['save'] ) : ?>
				<span class="wg-badge wg-badge--sale">
					<?php
					/* translators: %s: formatted amount saved */
					printf( esc_html__( 'Save %s', 'webgram' ), wp_kses_post( $args['save']['amount'] ) );
					?>
				</span>
			<?php endif; ?>
			<?php do_action( 'webgram/product_card/badges', $product ); ?>
		</div>

		<div class="wg-card__actions">
			<?php do_action( 'webgram/product_card/actions', $product ); ?>
		</div>
	</div>

	<div class="wg-card__body">
		<h3 class="wg-card__title"><a href="<?php echo esc_url( $args['permalink'] ); ?>"><?php echo esc_html( $args['title'] ); ?></a></h3>

		<?php if ( $args['rating'] > 0 ) : ?>
			<div class="wg-rating wg-rating--sm" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: rating, 2: review count */ __( 'Rated %1$s out of 5 from %2$d reviews', 'webgram' ), $args['rating'], $args['reviews'] ) ); ?>">
				<span class="wg-rating__stars"><span class="wg-rating__fill" style="width:<?php echo esc_attr( (string) ( $args['rating'] / 5 * 100 ) ); ?>%"></span></span>
				<span class="wg-rating__count"><?php echo esc_html( number_format_i18n( $args['rating'], 1 ) ); ?> (<?php echo esc_html( (string) $args['reviews'] ); ?>)</span>
			</div>
		<?php endif; ?>

		<div class="wg-card__price wg-price">
			<?php echo wp_kses_post( $args['price'] ); ?>
			<?php if ( $args['save'] ) : ?>
				<span class="wg-price__percent"><?php echo esc_html( $args['save']['percent'] ); ?>% <?php esc_html_e( 'off', 'webgram' ); ?></span>
			<?php endif; ?>
		</div>

		<?php do_action( 'webgram/product_card/after_price', $product ); ?>

		<div class="wg-card__buttons">
			<?php
			// Standard WooCommerce loop button: keeps AJAX add-to-cart and every third-party filter working.
			woocommerce_template_loop_add_to_cart( [ 'class' => 'wg-btn wg-btn--icon wg-card__cart button' ] );

			if ( $args['args']['show_buy_now'] ) :
				do_action( 'webgram/product_card/buy_now', $product );
			endif;
			?>
		</div>
	</div>
</article>
