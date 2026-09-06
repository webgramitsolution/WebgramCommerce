<?php
/**
 * Product page delivery check. $args: label, field_label, value, product_id, country.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'pincode' ) ); ?>" data-wgc-pincode data-product-id="<?php echo (int) $args['product_id']; ?>">
	<label class="<?php echo esc_attr( Helpers::css_class( 'pincode__label' ) ); ?>" for="wgc-pincode-input">
		<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
		<?php echo esc_html( $args['label'] ); ?>
	</label>
	<form class="<?php echo esc_attr( Helpers::css_class( 'pincode__row' ) ); ?>" data-wgc-pincode-form>
		<input id="wgc-pincode-input" type="text" name="pincode" data-wgc-pincode-input value="<?php echo esc_attr( $args['value'] ); ?>" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: field label */ __( 'Enter %s', 'webgram-core' ), $args['field_label'] ) ); ?>" inputmode="<?php echo 'IN' === $args['country'] ? 'numeric' : 'text'; ?>" autocomplete="postal-code" maxlength="12">
		<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn wg-btn--primary' ) ); ?>"><?php esc_html_e( 'Check', 'webgram-core' ); ?></button>
	</form>
	<p class="<?php echo esc_attr( Helpers::css_class( 'pincode__result' ) ); ?>" data-wgc-pincode-result aria-live="polite"></p>
</div>
