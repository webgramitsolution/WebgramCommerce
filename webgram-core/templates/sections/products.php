<?php
/**
 * Product section: grid (slider on mobile), slider, or the dark "Best Sellers" band (spec 4.3 items 4 to 6).
 * $args: products (WC_Product[]), a (args), heading, layout.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_a       = $args['a'];
$wgc_cols    = max( 1, (int) $wgc_a['columns'] );
$wgc_style   = sprintf( '--wg-cols-desktop:%1$d;--wg-cols-tablet:%2$d;--wg-cols-mobile:2;--wgc-cols:%1$d', $wgc_cols, min( 3, $wgc_cols ) );
$wgc_carousel = 'grid' === $args['layout'] ? 'mobile' : ( 'slider' === $args['layout'] ? 'snap' : 'dots' );
$wgc_loop    = static function () use ( $args, $wgc_cols ): void {
	wc_set_loop_prop( 'columns', $wgc_cols );
	wc_set_loop_prop( 'name', 'webgram_section' );
	woocommerce_product_loop_start();
	foreach ( $args['products'] as $wgc_product ) {
		$GLOBALS['post'] = get_post( $wgc_product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	wp_reset_postdata();
	wc_reset_loop();
};
?>
<?php if ( 'band' === $args['layout'] ) : ?>
	<section class="<?php echo esc_attr( Helpers::css_class( 'products', 'wgc-products--band wg-band' ) ); ?>" style="<?php echo esc_attr( $wgc_style ); ?>">
		<div class="<?php echo esc_attr( Helpers::css_class( 'band__inner' ) ); ?>">
			<div class="<?php echo esc_attr( Helpers::css_class( 'band__title' ) ); ?>">
				<span class="<?php echo esc_attr( Helpers::css_class( 'band__line1' ) ); ?>"><?php echo esc_html( $wgc_a['band_line1'] ); ?></span>
				<span class="<?php echo esc_attr( Helpers::css_class( 'band__line2' ) ); ?>"><?php echo esc_html( $wgc_a['band_line2'] ); ?></span>
				<?php if ( $args['heading']['subtitle'] ) : ?>
					<p class="<?php echo esc_attr( Helpers::css_class( 'band__subtitle' ) ); ?>"><?php echo esc_html( $args['heading']['subtitle'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="<?php echo esc_attr( Helpers::css_class( 'band__products' ) ); ?>" data-wg-component="carousel" data-wg-carousel="dots">
				<?php $wgc_loop(); ?>
			</div>
		</div>
		<?php if ( $args['heading']['link_url'] && $args['heading']['link_text'] ) : ?>
			<p class="<?php echo esc_attr( Helpers::css_class( 'band__more' ) ); ?>"><a class="wg-btn wg-btn--outline" href="<?php echo esc_url( $args['heading']['link_url'] ); ?>"><?php echo esc_html( $args['heading']['link_text'] ); ?></a></p>
		<?php endif; ?>
	</section>
<?php else : ?>
	<section class="<?php echo esc_attr( Helpers::css_class( 'products', 'wgc-products--' . $args['layout'] ) ); ?>" style="<?php echo esc_attr( $wgc_style ); ?>">
		<?php webgram_core()->view( 'sections/heading', $args['heading'] ); ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'products__row' ) ); ?>" data-wg-component="carousel" data-wg-carousel="<?php echo esc_attr( $wgc_carousel ); ?>">
			<?php $wgc_loop(); ?>
		</div>
	</section>
<?php endif; ?>
