<?php
/**
 * Track order: form and result area (timeline, carrier, items rendered by JS).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'track' ) ); ?>" data-wgc-track>
	<form class="<?php echo esc_attr( Helpers::css_class( 'track__form' ) ); ?>" data-wgc-track-form novalidate>
		<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="wgc-track-order"><?php esc_html_e( 'Order number', 'webgram-core' ); ?></label><input id="wgc-track-order" type="text" name="order" inputmode="numeric" placeholder="12345" required aria-describedby="wgc-track-message"></p>
		<p class="<?php echo esc_attr( Helpers::css_class( 'field' ) ); ?>"><label for="wgc-track-contact"><?php esc_html_e( 'Email or phone used at checkout', 'webgram-core' ); ?></label><input id="wgc-track-contact" type="text" name="contact" autocomplete="email" required aria-describedby="wgc-track-message"></p>
		<p class="<?php echo esc_attr( Helpers::css_class( 'track__message' ) ); ?>" id="wgc-track-message" data-wgc-track-message aria-live="polite" role="status"></p>
		<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn wg-btn--primary wg-btn--block' ) ); ?>"><?php esc_html_e( 'Track order', 'webgram-core' ); ?></button>
	</form>
	<div class="<?php echo esc_attr( Helpers::css_class( 'track__result' ) ); ?>" data-wgc-track-result hidden>
		<div class="<?php echo esc_attr( Helpers::css_class( 'track__head' ) ); ?>"><strong data-wgc-track-number></strong><span class="<?php echo esc_attr( Helpers::css_class( 'track__status' ) ); ?>" data-wgc-track-status></span></div>
		<ol class="<?php echo esc_attr( Helpers::css_class( 'timeline' ) ); ?>" data-wgc-track-timeline></ol>
		<p class="<?php echo esc_attr( Helpers::css_class( 'track__carrier' ) ); ?>" data-wgc-track-carrier hidden></p>
		<ul class="<?php echo esc_attr( Helpers::css_class( 'track__items' ) ); ?>" data-wgc-track-items></ul>
	</div>
</div>
