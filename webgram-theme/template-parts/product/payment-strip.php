<?php
/**
 * Payment strip: "100% secure payment by" + processor logo + payment method icons + caption.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_logo  = (int) webgram_option( 'product_payment_logo' );
$webgram_icons = (array) webgram_option( 'product_payment_icons' );
?>
<div class="wg-payment-strip">
	<div class="wg-payment-strip__head">
		<?php webgram_icon( 'lock' ); ?>
		<span><?php echo esc_html( (string) webgram_option( 'product_payment_title' ) ); ?></span>
		<?php if ( $webgram_logo ) : ?>
			<?php echo wp_get_attachment_image( $webgram_logo, 'medium', false, [ 'class' => 'wg-payment-strip__logo', 'loading' => 'lazy' ] ); ?>
		<?php endif; ?>
	</div>
	<?php if ( $webgram_icons ) : ?>
		<div class="wg-payments wg-payment-strip__icons">
			<?php foreach ( $webgram_icons as $webgram_slug ) { webgram_payment_icon( (string) $webgram_slug ); } ?>
		</div>
	<?php endif; ?>
	<?php if ( webgram_option( 'product_payment_caption' ) ) : ?>
		<p class="wg-payment-strip__caption"><?php echo esc_html( (string) webgram_option( 'product_payment_caption' ) ); ?></p>
	<?php endif; ?>
</div>
