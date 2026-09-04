<?php
/**
 * Single product wrapper (spec 4.4): sticky gallery column and summary column inside a soft cream section, then the
 * full-width blocks (related, reviews, reels, recently viewed) in the order chosen in Theme Settings.
 * A matching Webgram Core Layout of type "single_product" replaces the columns.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) :
	the_post();
	$webgram_layout = 'layout' === webgram_option( 'product_layout' ) ? webgram_layout_id( 'single_product' ) : 0;
	?>
	<?php do_action( 'woocommerce_before_single_product' ); ?>
	<?php if ( post_password_required() ) : ?>
		<?php echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php else : ?>
		<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'wg-product wg-product--' . sanitize_html_class( (string) webgram_option( 'product_panels' ) ) . ' wg-product--gallery-' . sanitize_html_class( (string) webgram_option( 'product_gallery_style' ) ), get_the_ID() ); ?> data-wg-component="product">
			<?php if ( $webgram_layout ) : ?>
				<?php webgram_render_block( $webgram_layout ); ?>
			<?php else : ?>
				<div class="wg-product__columns">
					<div class="wg-product__gallery-col" data-wg-sticky-col>
						<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
					</div>
					<div class="wg-product__summary-col">
						<div class="summary entry-summary wg-product__summary">
							<?php do_action( 'woocommerce_single_product_summary' ); ?>
						</div>
					</div>
				</div>
				<?php do_action( 'webgram/product/after_columns', get_the_ID() ); ?>
			<?php endif; ?>
			<div class="wg-product__below">
				<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
			</div>
		</div>
	<?php endif; ?>
	<?php do_action( 'woocommerce_after_single_product' ); ?>
	<?php
endwhile;

do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
