<?php
/**
 * Wishlist page. $args: products (WC_Product[]), shared (bool), invalid (bool), share_url, shop_url, icon.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'wishlist' ) ); ?>" data-wgc-wishlist-page<?php echo $args['shared'] ? ' data-shared="1"' : ''; ?>>
	<?php if ( $args['invalid'] ) : ?>
		<p class="<?php echo esc_attr( Helpers::css_class( 'notice' ) ); ?>"><?php esc_html_e( 'This shared wishlist link is invalid or has expired.', 'webgram-core' ); ?></p>
	<?php elseif ( $args['shared'] ) : ?>
		<p class="<?php echo esc_attr( Helpers::css_class( 'notice', 'wg-notice--info' ) ); ?>"><?php esc_html_e( 'You are viewing a shared wishlist.', 'webgram-core' ); ?></p>
	<?php endif; ?>

	<?php if ( ! $args['products'] ) : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'wishlist__empty' ) ); ?>">
			<?php echo $args['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG. ?>
			<h3><?php esc_html_e( 'Your wishlist is empty', 'webgram-core' ); ?></h3>
			<p><?php esc_html_e( 'Tap the heart on any product to save it here.', 'webgram-core' ); ?></p>
			<a class="wg-btn wg-btn--primary" href="<?php echo esc_url( $args['shop_url'] ); ?>"><?php esc_html_e( 'Continue shopping', 'webgram-core' ); ?></a>
		</div>
	<?php else : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'wishlist__toolbar' ) ); ?>">
			<span class="<?php echo esc_attr( Helpers::css_class( 'wishlist__count' ) ); ?>">
				<?php echo esc_html( sprintf( /* translators: %d: number of items */ _n( '%d item', '%d items', count( $args['products'] ), 'webgram-core' ), count( $args['products'] ) ) ); ?>
			</span>
			<?php if ( $args['share_url'] ) : ?>
				<button type="button" class="wg-btn wg-btn--outline wg-btn--sm" data-wgc-copy="<?php echo esc_attr( $args['share_url'] ); ?>" data-copied="<?php esc_attr_e( 'Link copied', 'webgram-core' ); ?>">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.59 13.51l6.83 3.98M15.41 6.51L8.59 10.49"/></svg>
					<?php esc_html_e( 'Share wishlist', 'webgram-core' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<table class="<?php echo esc_attr( Helpers::css_class( 'wishlist__table', 'shop_table' ) ); ?>">
			<thead>
				<tr>
					<th class="wgc-col-image"><span class="wg-sr-only"><?php esc_html_e( 'Image', 'webgram-core' ); ?></span></th>
					<th><?php esc_html_e( 'Product', 'webgram-core' ); ?></th>
					<th><?php esc_html_e( 'Price', 'webgram-core' ); ?></th>
					<th><?php esc_html_e( 'Stock', 'webgram-core' ); ?></th>
					<th class="wgc-col-actions"><span class="wg-sr-only"><?php esc_html_e( 'Actions', 'webgram-core' ); ?></span></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $args['products'] as $wgc_product ) : ?>
					<tr data-wgc-wishlist-row="<?php echo (int) $wgc_product->get_id(); ?>">
						<td class="wgc-col-image" data-title="<?php esc_attr_e( 'Image', 'webgram-core' ); ?>"><a href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php echo $wgc_product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></td>
						<td data-title="<?php esc_attr_e( 'Product', 'webgram-core' ); ?>"><a class="<?php echo esc_attr( Helpers::css_class( 'wishlist__name' ) ); ?>" href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>"><?php echo esc_html( $wgc_product->get_name() ); ?></a></td>
						<td data-title="<?php esc_attr_e( 'Price', 'webgram-core' ); ?>"><?php echo wp_kses_post( $wgc_product->get_price_html() ); ?></td>
						<td data-title="<?php esc_attr_e( 'Stock', 'webgram-core' ); ?>"><?php echo wp_kses_post( wc_get_stock_html( $wgc_product ) ?: '<span class="wgc-in-stock">' . esc_html__( 'In stock', 'webgram-core' ) . '</span>' ); ?></td>
						<td class="wgc-col-actions">
							<?php if ( $wgc_product->is_purchasable() && $wgc_product->is_in_stock() ) : ?>
								<a href="<?php echo esc_url( $wgc_product->add_to_cart_url() ); ?>" data-quantity="1" class="wg-btn wg-btn--primary wg-btn--sm button product_type_<?php echo esc_attr( $wgc_product->get_type() ); ?><?php echo $wgc_product->supports( 'ajax_add_to_cart' ) ? ' ajax_add_to_cart add_to_cart_button' : ''; ?>" data-product_id="<?php echo (int) $wgc_product->get_id(); ?>" data-product_sku="<?php echo esc_attr( $wgc_product->get_sku() ); ?>" rel="nofollow"><?php echo esc_html( $wgc_product->add_to_cart_text() ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( $wgc_product->get_permalink() ); ?>" class="wg-btn wg-btn--outline wg-btn--sm"><?php esc_html_e( 'View', 'webgram-core' ); ?></a>
							<?php endif; ?>
							<?php if ( ! $args['shared'] ) : ?>
								<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'wishlist__remove' ) ); ?>" data-wgc-wishlist="<?php echo (int) $wgc_product->get_id(); ?>" data-op="remove" aria-label="<?php esc_attr_e( 'Remove from wishlist', 'webgram-core' ); ?>">
									<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
								</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<?php do_action( 'webgram_core/wishlist/after_table', $args['products'], $args['shared'] ); ?>
</div>
