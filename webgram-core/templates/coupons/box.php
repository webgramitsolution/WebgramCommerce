<?php
/**
 * Product coupon box. $args: code, headline, copy_label.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-box' ) ); ?>" data-wgc-coupon>
	<svg class="wgc-coupon-box__icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
	<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__text' ) ); ?>">
		<strong><?php echo esc_html( $args['headline'] ); ?></strong>
		<span><?php esc_html_e( 'Use code', 'webgram-core' ); ?> <code class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__code' ) ); ?>" data-wgc-coupon-code><?php echo esc_html( strtoupper( $args['code'] ) ); ?></code></span>
	</div>
	<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'coupon-box__copy', 'wg-btn wg-btn--outline wg-btn--sm' ) ); ?>" data-wgc-copy="<?php echo esc_attr( $args['code'] ); ?>" data-copied="<?php esc_attr_e( 'Code copied', 'webgram-core' ); ?>">
		<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
		<span><?php echo esc_html( $args['copy_label'] ); ?></span>
	</button>
</div>
