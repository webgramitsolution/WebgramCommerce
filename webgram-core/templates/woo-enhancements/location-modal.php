<?php
/**
 * "Select delivery location" modal. $args: field_label, country, current, has_geocoder, attribution.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div id="wgc-location-modal" class="<?php echo esc_attr( Helpers::css_class( 'modal', 'wgc-modal--location wg-modal--location' ) ); ?>" data-wgc-location-modal role="dialog" aria-modal="true" aria-labelledby="wgc-location-title" hidden>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__backdrop' ) ); ?>" data-wgc-location-close></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__dialog' ) ); ?>">
		<div class="<?php echo esc_attr( Helpers::css_class( 'modal__head' ) ); ?>">
			<h3 id="wgc-location-title"><?php esc_html_e( 'Select delivery location', 'webgram-core' ); ?></h3>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'modal__close' ) ); ?>" data-wgc-location-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
		</div>
		<form class="<?php echo esc_attr( Helpers::css_class( 'location-form' ) ); ?>" data-wgc-location-form>
			<label for="wgc-location-input"><?php echo esc_html( sprintf( /* translators: %s: Pincode or Postal code */ __( 'Enter your %s', 'webgram-core' ), $args['field_label'] ) ); ?></label>
			<div class="<?php echo esc_attr( Helpers::css_class( 'location-form__row' ) ); ?>">
				<input id="wgc-location-input" type="text" name="pincode" inputmode="<?php echo 'IN' === $args['country'] ? 'numeric' : 'text'; ?>" autocomplete="postal-code" maxlength="12" value="<?php echo esc_attr( $args['current']['pincode'] ); ?>" placeholder="<?php echo 'IN' === $args['country'] ? '400001' : ''; ?>" required>
				<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn--primary' ) ); ?>"><?php esc_html_e( 'Apply', 'webgram-core' ); ?></button>
			</div>
			<p class="<?php echo esc_attr( Helpers::css_class( 'location-form__message' ) ); ?>" data-wgc-location-message aria-live="polite"></p>
		</form>
		<?php if ( $args['has_geocoder'] ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'location-form__geo' ) ); ?>">
				<span class="<?php echo esc_attr( Helpers::css_class( 'location-form__or' ) ); ?>"><?php esc_html_e( 'or', 'webgram-core' ); ?></span>
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--outline wg-btn--outline' ) ); ?>" data-wgc-location-geo>
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
					<?php esc_html_e( 'Use my current location', 'webgram-core' ); ?>
				</button>
				<?php if ( $args['attribution'] ) : ?>
					<small class="<?php echo esc_attr( Helpers::css_class( 'location-form__attribution' ) ); ?>"><?php echo wp_kses_post( $args['attribution'] ); ?></small>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php do_action( 'webgram_core/location/modal_after' ); ?>
	</div>
</div>
