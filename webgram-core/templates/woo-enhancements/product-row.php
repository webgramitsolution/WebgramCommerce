<?php
/**
 * Row of products (recently viewed, etc). Uses the standard content-product template so the active theme's card
 * renders. $args: products (WC_Product[]), title, class.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

if ( empty( $args['products'] ) ) {
	return;
}
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'product-row', 'wgc-product-row--' . sanitize_html_class( $args['class'] ) . ' wg-section-ornament' ) ); ?>">
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<h2 class="<?php echo esc_attr( Helpers::css_class( 'product-row__title' ) ); ?>"><?php echo esc_html( $args['title'] ); ?></h2>
	<?php endif; ?>
	<?php
	$wgc_columns = min( 5, count( $args['products'] ) );
	wc_set_loop_prop( 'columns', $wgc_columns );
	wc_set_loop_prop( 'name', sanitize_key( $args['class'] ) );
	woocommerce_product_loop_start();
	foreach ( $args['products'] as $wgc_product ) {
		$GLOBALS['post'] = get_post( $wgc_product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );
		wc_get_template_part( 'content', 'product' );
	}
	woocommerce_product_loop_end();
	wp_reset_postdata();
	wc_reset_loop();
	?>
</section>
