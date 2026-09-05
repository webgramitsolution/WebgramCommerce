<?php
/**
 * Thank you page: summary card, status timeline, Core hook (invoice, track link, WhatsApp consent), then the
 * standard WooCommerce order details, and a continue shopping button.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-order wg-thankyou">

	<?php
	if ( $order ) :
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="wg-thankyou__head wg-thankyou__head--failed">
				<?php webgram_icon( 'x-circle', 'wg-thankyou__icon' ); ?>
				<h1 class="wg-thankyou__title"><?php esc_html_e( 'Payment failed', 'webgram' ); ?></h1>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank or merchant has declined your transaction. Please attempt your purchase again.', 'webgram' ); ?></p>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay wg-btn wg-btn--primary"><?php esc_html_e( 'Pay', 'webgram' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay wg-btn wg-btn--outline"><?php esc_html_e( 'My account', 'webgram' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

		<?php else : ?>

			<?php Webgram_WC_Checkout::steps( 'done' ); ?>

			<div class="wg-thankyou__head">
				<?php webgram_icon( 'check-circle', 'wg-thankyou__icon' ); ?>
				<h1 class="wg-thankyou__title"><?php esc_html_e( 'Thank you for your order', 'webgram' ); ?></h1>
				<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Your order has been received. A confirmation is on its way to your inbox.', 'webgram' ), $order ) ); ?></p>
			</div>

			<div class="wg-thankyou__grid">
				<div class="wg-thankyou__card">
					<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details wg-thankyou__overview">
						<li class="woocommerce-order-overview__order order"><span><?php esc_html_e( 'Order number', 'webgram' ); ?></span><strong><?php echo esc_html( $order->get_order_number() ); ?></strong></li>
						<li class="woocommerce-order-overview__date date"><span><?php esc_html_e( 'Date', 'webgram' ); ?></span><strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong></li>
						<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
							<li class="woocommerce-order-overview__email email"><span><?php esc_html_e( 'Email', 'webgram' ); ?></span><strong><?php echo esc_html( $order->get_billing_email() ); ?></strong></li>
						<?php endif; ?>
						<li class="woocommerce-order-overview__total total"><span><?php esc_html_e( 'Total', 'webgram' ); ?></span><strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></li>
						<?php if ( $order->get_payment_method_title() ) : ?>
							<li class="woocommerce-order-overview__payment-method method"><span><?php esc_html_e( 'Payment method', 'webgram' ); ?></span><strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong></li>
						<?php endif; ?>
					</ul>
					<?php do_action( 'webgram/thankyou/after_details', $order ); ?>
				</div>

				<?php if ( webgram_option( 'thankyou_timeline' ) ) : ?>
					<div class="wg-thankyou__card">
						<h2 class="wg-thankyou__card-title"><?php esc_html_e( 'Order status', 'webgram' ); ?></h2>
						<ol class="wg-timeline">
							<?php foreach ( Webgram_WC_Checkout::timeline( $order->get_status(), (bool) $order->get_date_paid() ) as $webgram_step ) : ?>
								<li class="<?php echo $webgram_step['done'] ? 'is-done' : ''; ?><?php echo $webgram_step['current'] ? ' is-current' : ''; ?>"><span class="wgc-timeline__dot"></span><span class="wgc-timeline__label"><?php echo esc_html( $webgram_step['label'] ); ?></span></li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>
			</div>

		<?php endif; ?>

		<div class="wg-thankyou__details">
			<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
			<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		</div>

		<?php if ( webgram_option( 'thankyou_continue' ) ) : ?>
			<p class="wg-thankyou__continue"><a class="wg-btn wg-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'webgram' ); ?></a></p>
		<?php endif; ?>

	<?php else : ?>

		<div class="wg-thankyou__head">
			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'webgram' ), null ) ); ?></p>
		</div>

	<?php endif; ?>

</div>
