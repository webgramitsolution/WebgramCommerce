<?php
/**
 * Compare page. $args: products, rows, highlight, shop_url, max, icon.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'compare', $args['highlight'] ? 'is-highlight' : '' ) ); ?>" data-wgc-compare-page>
	<?php if ( ! $args['products'] ) : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'compare__empty' ) ); ?>">
			<?php echo $args['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG. ?>
			<h3><?php esc_html_e( 'Nothing to compare yet', 'webgram-core' ); ?></h3>
			<p><?php echo esc_html( sprintf( /* translators: %d: maximum products */ __( 'Add up to %d products from the shop to compare them side by side.', 'webgram-core' ), (int) $args['max'] ) ); ?></p>
			<a class="wg-btn wg-btn--primary" href="<?php echo esc_url( $args['shop_url'] ); ?>"><?php esc_html_e( 'Browse products', 'webgram-core' ); ?></a>
		</div>
	<?php else : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'compare__toolbar' ) ); ?>">
			<label class="<?php echo esc_attr( Helpers::css_class( 'compare__toggle' ) ); ?>">
				<input type="checkbox" data-wgc-compare-highlight <?php checked( $args['highlight'] ); ?>>
				<?php esc_html_e( 'Highlight differences', 'webgram-core' ); ?>
			</label>
		</div>
		<div class="<?php echo esc_attr( Helpers::css_class( 'compare__scroll' ) ); ?>">
			<table class="<?php echo esc_attr( Helpers::css_class( 'compare__table' ) ); ?>">
				<thead>
					<tr>
						<th class="<?php echo esc_attr( Helpers::css_class( 'compare__label' ) ); ?>"><span class="wg-sr-only"><?php esc_html_e( 'Feature', 'webgram-core' ); ?></span></th>
						<?php foreach ( $args['products'] as $wgc_product ) : ?>
							<th class="<?php echo esc_attr( Helpers::css_class( 'compare__product' ) ); ?>" data-wgc-compare-col="<?php echo (int) $wgc_product->get_id(); ?>">
								<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'compare__remove' ) ); ?>" data-wgc-compare="<?php echo (int) $wgc_product->get_id(); ?>" data-op="remove" aria-label="<?php esc_attr_e( 'Remove from compare', 'webgram-core' ); ?>">&times;</button>
								<a href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php echo $wgc_product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
								<a class="<?php echo esc_attr( Helpers::css_class( 'compare__name' ) ); ?>" href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php echo esc_html( $wgc_product->get_name() ); ?></a>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $args['rows'] as $wgc_row ) : ?>
						<tr class="<?php echo esc_attr( Helpers::css_class( 'compare__row', $wgc_row['differs'] ? 'is-different' : '' ) ); ?>" data-row="<?php echo esc_attr( $wgc_row['key'] ); ?>">
							<th class="<?php echo esc_attr( Helpers::css_class( 'compare__label' ) ); ?>"><?php echo esc_html( $wgc_row['label'] ); ?></th>
							<?php foreach ( $wgc_row['cells'] as $wgc_cell ) : ?>
								<td><?php echo $wgc_row['html'] ? wp_kses_post( (string) $wgc_cell ) : esc_html( '' === (string) $wgc_cell ? '-' : (string) $wgc_cell ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					<tr class="<?php echo esc_attr( Helpers::css_class( 'compare__row', 'wgc-compare__row--actions' ) ); ?>">
						<th class="<?php echo esc_attr( Helpers::css_class( 'compare__label' ) ); ?>"><span class="wg-sr-only"><?php esc_html_e( 'Actions', 'webgram-core' ); ?></span></th>
						<?php foreach ( $args['products'] as $wgc_product ) : ?>
							<td>
								<?php if ( $wgc_product->is_purchasable() && $wgc_product->is_in_stock() ) : ?>
									<a href="<?php echo esc_url( $wgc_product->add_to_cart_url() ); ?>" data-quantity="1" class="wg-btn wg-btn--primary wg-btn--sm button product_type_<?php echo esc_attr( $wgc_product->get_type() ); ?><?php echo $wgc_product->supports( 'ajax_add_to_cart' ) ? ' ajax_add_to_cart add_to_cart_button' : ''; ?>" data-product_id="<?php echo (int) $wgc_product->get_id(); ?>" rel="nofollow"><?php echo esc_html( $wgc_product->add_to_cart_text() ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>" class="wg-btn wg-btn--outline wg-btn--sm"><?php esc_html_e( 'View', 'webgram-core' ); ?></a>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
	<?php do_action( 'webgram_core/compare/after_table', $args['products'] ); ?>
</div>
