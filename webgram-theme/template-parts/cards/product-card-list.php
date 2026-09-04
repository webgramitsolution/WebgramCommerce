<?php
/**
 * Product card: list variant (image left 200px, content right, short description, same actions).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
$options = $args['args'];
$rating  = $args['rating'] > 0 ? Webgram_WC_Product_Card::rating_pill( (float) $args['rating'], (int) $args['reviews'] ) : '';
?>
<article class="<?php echo esc_attr( $args['classes'] ); ?>" data-wg-component="product-card" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
	<div class="wg-card__media">
		<a class="wg-card__link" href="<?php echo esc_url( $args['permalink'] ); ?>" aria-label="<?php echo esc_attr( $args['title'] ); ?>" tabindex="-1">
			<?php echo $args['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="wg-card__badges">
			<?php if ( $args['save'] ) : ?>
				<span class="wg-badge wg-badge--sale"><?php printf( esc_html__( 'Save %s', 'webgram' ), wp_kses_post( $args['save']['amount'] ) ); ?></span>
			<?php endif; ?>
			<?php do_action( 'webgram/product_card/badges', $product ); ?>
		</div>
		<?php if ( $options['show_actions'] ) : ?>
			<div class="wg-card__actions"><?php do_action( 'webgram/product_card/actions', $product ); ?></div>
		<?php endif; ?>
	</div>
	<div class="wg-card__body">
		<h3 class="wg-card__title"><a href="<?php echo esc_url( $args['permalink'] ); ?>"><?php echo esc_html( $args['title'] ); ?></a></h3>
		<div class="wg-card__price-line">
			<div class="wg-card__price wg-price" data-wg-price>
				<?php if ( $args['price']['sale'] ) : ?>
					<span class="wg-price__sale"><?php echo wp_kses_post( $args['price']['sale'] ); ?></span>
					<?php if ( $args['price']['regular'] ) : ?><del class="wg-price__regular"><?php echo wp_kses_post( $args['price']['regular'] ); ?></del><?php endif; ?>
					<?php if ( $args['save'] ) : ?><span class="wg-price__percent"><?php echo esc_html( (string) $args['save']['percent'] ); ?>% <?php esc_html_e( 'off', 'webgram' ); ?></span><?php endif; ?>
				<?php else : ?>
					<?php echo wp_kses_post( $args['price']['html'] ); ?>
				<?php endif; ?>
			</div>
			<?php echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<div class="wg-card__excerpt"><?php echo wp_kses_post( wp_trim_words( $product->get_short_description() ?: $product->get_description(), 30 ) ); ?></div>
		<?php do_action( 'webgram/product_card/after_price', $product ); ?>
		<div class="wg-card__buttons">
			<?php
			if ( $options['show_cart'] ) {
				woocommerce_template_loop_add_to_cart( [ 'class' => Webgram_WC_Product_Card::cart_button_class( $product ) ] );
			}
			if ( $options['show_buy_now'] ) {
				ob_start();
				do_action( 'webgram/product_card/buy_now', $product );
				$webgram_buy = trim( (string) ob_get_clean() );
				echo '' !== $webgram_buy ? $webgram_buy : '<a class="wg-btn wg-btn--primary wg-card__buy" href="' . esc_url( $args['permalink'] ) . '">' . esc_html( (string) webgram_option( 'card_buy_now_label' ) ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</article>
