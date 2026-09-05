<?php
/**
 * Popup. Overridable at {theme}/webgram-core/site-tools/promo-popup.php.
 * $args: id, label, content, image, width, trigger, delay, scroll, selector, frequency, days, devices, key.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'popup' ) ); ?>" id="wgc-popup-<?php echo (int) $args['id']; ?>" data-wgc-popup data-trigger="<?php echo esc_attr( $args['trigger'] ); ?>" data-delay="<?php echo (int) $args['delay']; ?>" data-scroll="<?php echo (int) $args['scroll']; ?>" data-selector="<?php echo esc_attr( (string) $args['selector'] ); ?>" data-frequency="<?php echo esc_attr( $args['frequency'] ); ?>" data-days="<?php echo (int) $args['days']; ?>" data-devices="<?php echo esc_attr( implode( ',', (array) $args['devices'] ) ); ?>" data-key="<?php echo esc_attr( $args['key'] ); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( (string) ( $args['label'] ?: __( 'Special offer', 'webgram-core' ) ) ); ?>" hidden>
	<div class="<?php echo esc_attr( Helpers::css_class( 'popup__backdrop' ) ); ?>" data-wgc-popup-close></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'popup__dialog' ) ); ?><?php echo $args['image'] ? ' has-image' : ''; ?>" style="--wgc-popup-width:<?php echo (int) $args['width']; ?>px">
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'popup__close' ) ); ?>" data-wgc-popup-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
		<?php if ( $args['image'] ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'popup__media' ) ); ?>"><?php echo wp_get_attachment_image( (int) $args['image'], 'large' ); ?></div>
		<?php endif; ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'popup__body' ) ); ?>"><?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output. ?></div>
	</div>
</div>
