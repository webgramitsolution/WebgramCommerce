<?php
/**
 * Drawer content (spec 4.8): header "Your Cart (N Items)", offer progress and recommendations (Core hooks),
 * line items, subtotal, note, PLACE ORDER with subline and payment icons.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_items = Webgram_WC_Cart::items();
$webgram_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>
<div class="wg-cart" data-wg-drawer-content>
	<div class="wg-drawer__head wg-cart__head">
		<span class="wg-drawer__title">
			<?php
			/* translators: %s: item count */
			printf( esc_html( _n( 'Your Cart (%s Item)', 'Your Cart (%s Items)', $webgram_count, 'webgram' ) ), '<span class="wg-cart__count">' . esc_html( (string) $webgram_count ) . '</span>' );
			?>
		</span>
		<button class="wg-icon-btn wg-icon-btn--no-label" type="button" data-wg-close="drawer"><?php webgram_icon( 'close' ); ?><span class="wg-sr-only"><?php esc_html_e( 'Close', 'webgram' ); ?></span></button>
	</div>

	<div class="wg-drawer__body wg-cart__body">
		<?php if ( $webgram_items ) : ?>
			<?php if ( webgram_option( 'cart_drawer_progress' ) ) : ?>
				<?php do_action( 'webgram/cart/before_items' ); ?>
			<?php endif; ?>
			<?php if ( webgram_option( 'cart_drawer_recommend' ) ) : ?>
				<?php do_action( 'webgram/cart/recommendations' ); ?>
			<?php endif; ?>
			<?php webgram_render_block( (int) webgram_option( 'cart_drawer_block_top' ) ); ?>

			<ul class="wg-cart__items">
				<?php foreach ( $webgram_items as $webgram_item ) : ?>
					<li class="wg-cart__item" data-key="<?php echo esc_attr( $webgram_item['key'] ); ?>">
						<div class="wg-cart__thumb">
							<?php
							if ( $webgram_item['url'] ) :
?>
<a href="<?php echo esc_url( $webgram_item['url'] ); ?>" tabindex="-1"><?php endif; ?>
							<?php echo $webgram_item['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image output. ?>
							<?php
							if ( $webgram_item['url'] ) :
?>
</a><?php endif; ?>
						</div>
						<div class="wg-cart__info">
							<div class="wg-cart__row">
								<span class="wg-cart__name"><?php echo $webgram_item['url'] ? '<a href="' . esc_url( $webgram_item['url'] ) . '">' . wp_kses_post( $webgram_item['name'] ) . '</a>' : wp_kses_post( $webgram_item['name'] ); ?></span>
								<span class="wg-cart__price"><?php echo wp_kses_post( $webgram_item['subtotal'] ); ?></span>
							</div>
							<?php
							if ( $webgram_item['meta'] ) :
?>
<div class="wg-cart__meta"><?php echo wp_kses_post( $webgram_item['meta'] ); ?></div><?php endif; ?>
							<div class="wg-cart__row wg-cart__row--actions">
								<?php if ( $webgram_item['sold_individually'] ) : ?>
									<span class="wg-cart__qty-fixed">&times; 1</span>
								<?php else : ?>
									<div class="wg-cart__qty" data-wg-cart-qty>
										<button type="button" class="wg-cart__qty-btn" data-wg-cart-minus aria-label="<?php esc_attr_e( 'Decrease quantity', 'webgram' ); ?>"><?php webgram_icon( 'minus' ); ?></button>
										<input type="number" class="wg-cart__qty-input" value="<?php echo esc_attr( (string) $webgram_item['quantity'] ); ?>" min="0" <?php echo $webgram_item['max'] > 0 ? 'max="' . esc_attr( (string) $webgram_item['max'] ) . '"' : ''; ?> step="1" aria-label="<?php esc_attr_e( 'Quantity', 'webgram' ); ?>" data-wg-cart-input>
										<button type="button" class="wg-cart__qty-btn" data-wg-cart-plus aria-label="<?php esc_attr_e( 'Increase quantity', 'webgram' ); ?>"><?php webgram_icon( 'plus' ); ?></button>
									</div>
								<?php endif; ?>
								<button type="button" class="wg-cart__remove" data-wg-cart-remove aria-label="<?php esc_attr_e( 'Remove item', 'webgram' ); ?>"><?php webgram_icon( 'trash' ); ?></button>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php do_action( 'webgram/cart/after_items' ); ?>
			<?php webgram_render_block( (int) webgram_option( 'cart_drawer_block_bottom' ) ); ?>
		<?php else : ?>
			<div class="wg-cart__empty">
				<?php webgram_icon( 'cart', 'wg-cart__empty-icon' ); ?>
				<p class="wg-cart__empty-title"><?php esc_html_e( 'Your cart is empty', 'webgram' ); ?></p>
				<p class="wg-cart__empty-text"><?php esc_html_e( 'Looks like you have not added anything yet.', 'webgram' ); ?></p>
				<a class="wg-btn wg-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" data-wg-close="drawer"><?php esc_html_e( 'Start shopping', 'webgram' ); ?></a>
				<?php do_action( 'webgram/cart/empty' ); ?>
				<?php webgram_render_block( (int) webgram_option( 'cart_drawer_empty_block' ) ); ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $webgram_items ) : ?>
		<div class="wg-drawer__foot wg-cart__foot">
			<?php do_action( 'webgram/cart/before_totals' ); ?>
			<?php if ( webgram_option( 'cart_drawer_coupon' ) ) : ?>
				<form class="wg-cart__coupon" data-wg-cart-coupon>
					<input type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Coupon code', 'webgram' ); ?>" aria-label="<?php esc_attr_e( 'Coupon code', 'webgram' ); ?>">
					<button type="submit" class="wg-btn wg-btn--outline wg-btn--sm"><?php esc_html_e( 'Apply', 'webgram' ); ?></button>
				</form>
			<?php endif; ?>
			<div class="wg-cart__subtotal">
				<span><?php esc_html_e( 'SUBTOTAL', 'webgram' ); ?></span>
				<strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
			</div>
			<?php $webgram_saved = webgram_option( 'cart_drawer_savings' ) ? Webgram_WC_Cart::savings() : 0.0; ?>
			<?php if ( $webgram_saved > 0 ) : ?>
				<div class="wg-cart__savings">
					<span><?php esc_html_e( 'You save', 'webgram' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( $webgram_saved ) ); ?></strong>
				</div>
			<?php endif; ?>
			<?php if ( webgram_option( 'cart_drawer_note' ) ) : ?>
				<p class="wg-cart__note"><?php echo esc_html( (string) webgram_option( 'cart_drawer_note' ) ); ?></p>
			<?php endif; ?>
			<?php do_action( 'webgram/cart/after_totals' ); ?>
			<?php do_action( 'webgram/cart/before_checkout_button' ); ?>
			<a class="wg-btn wg-btn--secondary wg-btn--block wg-cart__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
				<span class="wg-cart__checkout-main"><?php echo esc_html( (string) webgram_option( 'cart_drawer_button' ) ); ?></span>
				<?php if ( webgram_option( 'cart_drawer_subline' ) || webgram_option( 'cart_drawer_payments' ) ) : ?>
					<span class="wg-cart__checkout-sub">
						<?php
						if ( webgram_option( 'cart_drawer_subline' ) ) :
?>
<span><?php echo esc_html( (string) webgram_option( 'cart_drawer_subline' ) ); ?></span><?php endif; ?>
						<?php
						foreach ( (array) webgram_option( 'cart_drawer_payments' ) as $webgram_pay ) {
webgram_payment_icon( (string) $webgram_pay ); }
?>
					</span>
				<?php endif; ?>
				<?php webgram_icon( 'chevron-right', 'wg-cart__checkout-arrow' ); ?>
			</a>
			<?php if ( webgram_option( 'cart_drawer_view_cart' ) ) : ?>
				<a class="wg-cart__view" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'webgram' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
