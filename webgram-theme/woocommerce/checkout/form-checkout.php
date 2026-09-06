<?php
/**
 * Checkout: visual steps header, two columns (details, sticky order review with coupon inside the summary).
 * Every WooCommerce hook of the original template is preserved.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'webgram' ) ) );
	return;
}

Webgram_WC_Checkout::steps( 'details' );
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout wg-checkout<?php echo webgram_option( 'checkout_sticky' ) ? ' wg-checkout--sticky' : ''; ?>" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'webgram' ); ?>">

	<div class="wg-checkout__main">
		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div class="col2-set wg-checkout__details" id="customer_details">
				<div class="col-1">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>

				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<?php endif; ?>
	</div>

	<div class="wg-checkout__side">
		<div class="wg-checkout__summary">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

			<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'webgram' ); ?></h3>

			<?php if ( 'summary' === webgram_option( 'checkout_coupon_place' ) && wc_coupons_enabled() ) : ?>
				<div class="wg-checkout__coupon" data-wg-checkout-coupon>
					<label class="screen-reader-text" for="wg-checkout-coupon"><?php esc_html_e( 'Coupon code', 'webgram' ); ?></label>
					<input type="text" id="wg-checkout-coupon" class="input-text" placeholder="<?php esc_attr_e( 'Coupon code', 'webgram' ); ?>">
					<button type="button" class="wg-btn wg-btn--outline wg-btn--sm" data-wg-apply-coupon><?php esc_html_e( 'Apply', 'webgram' ); ?></button>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

			<div id="order_review" class="woocommerce-checkout-review-order">
				<?php do_action( 'woocommerce_checkout_order_review' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>
	</div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
