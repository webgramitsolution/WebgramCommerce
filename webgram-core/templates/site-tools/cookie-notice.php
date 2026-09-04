<?php
/**
 * Cookie notice. $args: text, accept, reject, policy_url, policy_label, position, days.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'cookie', 'wgc-cookie--' . sanitize_html_class( $args['position'] ) . ' wg-cookie--' . sanitize_html_class( $args['position'] ) ) ); ?>" data-wgc-cookie data-days="<?php echo (int) $args['days']; ?>" role="region" aria-label="<?php esc_attr_e( 'Cookie notice', 'webgram-core' ); ?>" hidden>
	<div class="<?php echo esc_attr( Helpers::css_class( 'cookie__text' ) ); ?>">
		<?php echo wp_kses_post( $args['text'] ); ?>
		<?php if ( $args['policy_url'] ) : ?>
			<a href="<?php echo esc_url( $args['policy_url'] ); ?>"><?php echo esc_html( $args['policy_label'] ); ?></a>
		<?php endif; ?>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'cookie__actions' ) ); ?>">
		<?php if ( $args['reject'] ) : ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--ghost wg-btn--ghost' ) ); ?>" data-wgc-cookie-choice="rejected"><?php echo esc_html( $args['reject'] ); ?></button>
		<?php endif; ?>
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn--primary' ) ); ?>" data-wgc-cookie-choice="accepted"><?php echo esc_html( $args['accept'] ); ?></button>
	</div>
</div>
