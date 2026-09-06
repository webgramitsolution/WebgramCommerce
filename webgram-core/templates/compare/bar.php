<?php
/**
 * Floating compare bar. $args: products (WC_Product[]), url, max.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'compare-bar' ) ); ?>" data-wgc-compare-bar<?php echo $args['products'] ? '' : ' hidden'; ?>>
	<div class="<?php echo esc_attr( Helpers::css_class( 'compare-bar__items' ) ); ?>">
		<?php foreach ( $args['products'] as $wgc_product ) : ?>
			<span class="<?php echo esc_attr( Helpers::css_class( 'compare-bar__item' ) ); ?>">
				<?php echo $wgc_product->get_image( 'woocommerce_gallery_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button type="button" data-wgc-compare="<?php echo (int) $wgc_product->get_id(); ?>" data-op="remove" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove %s from compare', 'webgram-core' ), $wgc_product->get_name() ) ); ?>">&times;</button>
			</span>
		<?php endforeach; ?>
	</div>
	<span class="<?php echo esc_attr( Helpers::css_class( 'compare-bar__count' ) ); ?>"><?php echo esc_html( sprintf( /* translators: 1: selected count, 2: maximum */ __( '%1$d of %2$d selected', 'webgram-core' ), count( $args['products'] ), (int) $args['max'] ) ); ?></span>
	<a class="wg-btn wg-btn--primary wg-btn--sm" href="<?php echo esc_url( $args['url'] ?: '#' ); ?>"><?php esc_html_e( 'Compare', 'webgram-core' ); ?></a>
</div>
