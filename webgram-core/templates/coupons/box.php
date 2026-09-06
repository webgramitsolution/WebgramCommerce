<?php
/**
 * Product coupon box. $args: code, headline, copy_label, style, color, show_icon, show_apply, apply_label.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_style = in_array( $args['style'] ?? 'soft', [ 'soft', 'outline', 'solid', 'ticket' ], true ) ? (string) $args['style'] : 'soft';
$wgc_color = (string) ( $args['color'] ?? '#15803d' );
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-box', 'wgc-coupon-box--' . $wgc_style ) ); ?>" data-wgc-coupon data-code="<?php echo esc_attr( $args['code'] ); ?>" style="--wgc-coupon-color:<?php echo esc_attr( $wgc_color ); ?>">
	<?php if ( ! empty( $args['show_icon'] ) ) : ?>
		<svg class="wgc-coupon-box__icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
	<?php endif; ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__text' ) ); ?>">
		<strong><?php echo esc_html( $args['headline'] ); ?></strong>
		<span><?php esc_html_e( 'Use code', 'webgram-core' ); ?> <code class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__code' ) ); ?>" data-wgc-coupon-code><?php echo esc_html( strtoupper( $args['code'] ) ); ?></code></span>
		<span class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__message' ) ); ?>" data-wgc-coupon-message aria-live="polite" role="status"></span>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__actions' ) ); ?>">
		<?php if ( ! empty( $args['show_apply'] ) ) : ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__apply', 'wg-btn wg-btn--primary wg-btn--sm' ) ); ?>" data-wgc-apply="<?php echo esc_attr( $args['code'] ); ?>" data-applied="<?php esc_attr_e( 'Applied', 'webgram-core' ); ?>"><?php echo esc_html( $args['apply_label'] ?? __( 'Apply', 'webgram-core' ) ); ?></button>
		<?php endif; ?>
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__copy', 'wg-btn wg-btn--outline wg-btn--sm' ) ); ?>" data-wgc-copy="<?php echo esc_attr( $args['code'] ); ?>" data-copied="<?php esc_attr_e( 'Code copied', 'webgram-core' ); ?>">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
			<span><?php echo esc_html( $args['copy_label'] ); ?></span>
		</button>
	</div>
</div>
