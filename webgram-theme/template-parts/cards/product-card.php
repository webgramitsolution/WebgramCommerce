<?php
/**
 * Product card: standard variant (spec 4.2).
 * $args from Webgram_WC_Product_Card::render(): product, args, permalink, title, image, gallery, price, save, rating,
 * reviews, chips, classes.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
$options = $args['args'];
$rating  = $args['rating'] > 0 ? Webgram_WC_Product_Card::rating_pill( (float) $args['rating'], (int) $args['reviews'] ) : '';
$chips   = $args['chips'];
?>
<article class="<?php echo esc_attr( $args['classes'] ); ?>" data-wg-component="product-card" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"<?php echo $args['gallery'] ? ' data-gallery="' . esc_attr( (string) wp_json_encode( $args['gallery'] ) ) . '" data-interval="' . esc_attr( (string) webgram_option( 'card_slideshow_interval' ) ) . '"' : ''; ?>>
	<div class="wg-card__media">
		<a class="wg-card__link" href="<?php echo esc_url( $args['permalink'] ); ?>" aria-label="<?php echo esc_attr( $args['title'] ); ?>" tabindex="-1">
			<?php echo $args['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image output. ?>
		</a>
		<?php if ( $args['gallery'] ) : ?>
			<div class="wg-card__dots" aria-hidden="true"></div>
		<?php endif; ?>

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

		<?php if ( $options['show_actions'] ) : ?>
			<div class="wg-card__actions">
				<?php do_action( 'webgram/product_card/actions', $product ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! $product->is_in_stock() ) : ?>
			<span class="wg-card__stock"><?php esc_html_e( 'Out of stock', 'webgram' ); ?></span>
		<?php endif; ?>
	</div>

	<div class="wg-card__body">
		<h3 class="wg-card__title" style="--wg-title-lines:<?php echo (int) webgram_option( 'card_title_lines' ); ?>"><a href="<?php echo esc_url( $args['permalink'] ); ?>"><?php echo esc_html( $args['title'] ); ?></a></h3>

		<?php if ( $rating && 'under_title' === webgram_option( 'card_rating_position' ) ) : ?>
			<div class="wg-card__rating"><?php echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></div>
		<?php endif; ?>

		<div class="wg-card__price-line">
			<div class="wg-card__price wg-price" data-wg-price>
				<?php if ( $args['price']['sale'] ) : ?>
					<span class="wg-price__sale"><?php echo wp_kses_post( $args['price']['sale'] ); ?></span>
					<?php if ( $args['price']['regular'] ) : ?>
						<del class="wg-price__regular"><?php echo wp_kses_post( $args['price']['regular'] ); ?></del>
					<?php endif; ?>
					<?php if ( $args['save'] ) : ?>
						<span class="wg-price__percent"><?php echo esc_html( (string) $args['save']['percent'] ); ?>% <?php esc_html_e( 'off', 'webgram' ); ?></span>
					<?php endif; ?>
				<?php else : ?>
					<?php echo wp_kses_post( $args['price']['html'] ); ?>
				<?php endif; ?>
			</div>
			<?php if ( $rating && 'price_line' === webgram_option( 'card_rating_position' ) ) : ?>
				<?php echo $rating; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
			<?php endif; ?>
		</div>

		<?php if ( $chips && $chips['chips'] ) : ?>
			<div class="wg-card__chips wg-chips wg-chips--<?php echo esc_attr( $chips['style'] ); ?>" data-wg-chips data-attribute="<?php echo esc_attr( $chips['key'] ); ?>" role="group" aria-label="<?php echo esc_attr( $chips['label'] ); ?>">
				<?php foreach ( $chips['chips'] as $webgram_i => $webgram_chip ) : ?>
					<button type="button" class="wg-chip<?php echo 0 === $webgram_i ? ' is-selected' : ''; ?>" data-value="<?php echo esc_attr( $webgram_chip['value'] ); ?>" aria-pressed="<?php echo 0 === $webgram_i ? 'true' : 'false'; ?>" title="<?php echo esc_attr( $webgram_chip['label'] ); ?>"<?php echo $webgram_chip['color'] ? ' style="--wg-chip-color:' . esc_attr( $webgram_chip['color'] ) . '"' : ''; ?>>
						<?php if ( $webgram_chip['image'] ) : ?>
							<img src="<?php echo esc_url( $webgram_chip['image'] ); ?>" alt="" loading="lazy">
						<?php elseif ( ! $webgram_chip['color'] ) : ?>
							<?php echo esc_html( $webgram_chip['label'] ); ?>
						<?php else : ?>
							<span class="wg-sr-only"><?php echo esc_html( $webgram_chip['label'] ); ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
				<?php if ( $chips['more'] > 0 ) : ?>
					<a class="wg-chip wg-chip--more" href="<?php echo esc_url( $args['permalink'] ); ?>">+<?php echo (int) $chips['more']; ?></a>
				<?php endif; ?>
			</div>
			<script type="application/json" data-wg-variations><?php echo wp_json_encode( $chips['variations'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON inside a data script tag. ?></script>
		<?php endif; ?>

		<?php do_action( 'webgram/product_card/after_price', $product ); ?>

		<?php if ( $options['show_cart'] || $options['show_buy_now'] ) : ?>
			<div class="wg-card__buttons">
				<?php
				if ( $options['show_cart'] ) {
					// Standard WooCommerce loop button: keeps AJAX add-to-cart and every third-party filter working.
					woocommerce_template_loop_add_to_cart( [ 'class' => Webgram_WC_Product_Card::cart_button_class( $product, $chips && $chips['variations'] ? 'wg-card__cart--variable' : '' ) ] );
				}
				if ( $options['show_buy_now'] ) {
					ob_start();
					do_action( 'webgram/product_card/buy_now', $product );
					$webgram_buy = trim( (string) ob_get_clean() );
					if ( '' === $webgram_buy ) {
						// Without Core, Buy now leads to the product page.
						$webgram_buy = '<a class="wg-btn wg-btn--primary wg-card__buy" href="' . esc_url( $args['permalink'] ) . '">' . esc_html( (string) webgram_option( 'card_buy_now_label' ) ) . '</a>';
					}
					echo $webgram_buy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above or by Core.
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</article>
