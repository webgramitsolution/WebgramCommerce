<?php
/**
 * Bulk inquiry form. $args: modal (bool), product (WC_Product|null), products (id => title for the page variant).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_modal = ! empty( $args['modal'] );
$wgc_uid   = wp_unique_id( 'wgc-bulk-' );
?>
<?php if ( $wgc_modal ) : ?>
<div id="wgc-bulk-modal" class="<?php echo esc_attr( Helpers::css_class( 'modal', 'wgc-modal--bulk wg-modal--bulk' ) ); ?>" data-wgc-bulk-modal role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $wgc_uid ); ?>-title" hidden>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__backdrop' ) ); ?>" data-wgc-bulk-close></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__dialog' ) ); ?>">
		<div class="<?php echo esc_attr( Helpers::css_class( 'modal__head' ) ); ?>">
			<h3 id="<?php echo esc_attr( $wgc_uid ); ?>-title"><?php esc_html_e( 'Ask for a bulk quote', 'webgram-core' ); ?></h3>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'modal__close' ) ); ?>" data-wgc-bulk-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
		</div>
<?php endif; ?>
		<form class="<?php echo esc_attr( Helpers::css_class( 'bulk-form' ) ); ?>" data-wgc-bulk-form novalidate>
			<?php if ( $args['product'] ) : ?>
				<input type="hidden" name="product_id" value="<?php echo (int) $args['product']->get_id(); ?>">
				<p class="<?php echo esc_attr( Helpers::css_class( 'bulk-form__product' ) ); ?>"><?php echo esc_html( $args['product']->get_name() ); ?></p>
			<?php elseif ( $args['products'] ) : ?>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-product"><?php esc_html_e( 'Product', 'webgram-core' ); ?></label>
					<input id="<?php echo esc_attr( $wgc_uid ); ?>-product" type="text" name="product_name" list="<?php echo esc_attr( $wgc_uid ); ?>-list" placeholder="<?php esc_attr_e( 'Start typing a product name', 'webgram-core' ); ?>" required>
					<datalist id="<?php echo esc_attr( $wgc_uid ); ?>-list">
					<?php
					foreach ( $args['products'] as $wgc_id => $wgc_title ) :
?>
<option value="<?php echo esc_attr( $wgc_title ); ?>"></option><?php endforeach; ?></datalist>
				</p>
			<?php else : ?>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-product"><?php esc_html_e( 'Product', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-product" type="text" name="product_name" required></p>
			<?php endif; ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'bulk-form__grid' ) ); ?>">
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-name"><?php esc_html_e( 'Your name', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-name" type="text" name="name" autocomplete="name" required></p>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-company"><?php esc_html_e( 'Company (optional)', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-company" type="text" name="company" autocomplete="organization"></p>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-phone"><?php esc_html_e( 'Phone', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-phone" type="tel" name="phone" autocomplete="tel" required></p>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-email"><?php esc_html_e( 'Email', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-email" type="email" name="email" autocomplete="email" required></p>
				<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-qty"><?php esc_html_e( 'Quantity', 'webgram-core' ); ?></label><input id="<?php echo esc_attr( $wgc_uid ); ?>-qty" type="number" name="quantity" min="1" step="1" value="50" required></p>
			</div>
			<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="<?php echo esc_attr( $wgc_uid ); ?>-msg"><?php esc_html_e( 'Message', 'webgram-core' ); ?></label><textarea id="<?php echo esc_attr( $wgc_uid ); ?>-msg" name="message" rows="3"></textarea></p>
			<p class="<?php echo esc_attr( Helpers::css_class( 'bulk-form__hp' ) ); ?>" aria-hidden="true" style="position:absolute;left:-9999px"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<p class="<?php echo esc_attr( Helpers::css_class( 'bulk-form__message' ) ); ?>" data-wgc-bulk-message aria-live="polite"></p>
			<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn wg-btn--primary wg-btn--block' ) ); ?>"><?php esc_html_e( 'Request quote', 'webgram-core' ); ?></button>
		</form>
<?php if ( $wgc_modal ) : ?>
	</div>
</div>
<?php endif; ?>
