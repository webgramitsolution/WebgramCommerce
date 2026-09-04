<?php
/**
 * Header "Deliver to" pill. $args: label, style, value, has_value.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'location', 'wgc-location--' . sanitize_html_class( $args['style'] ) . ' wg-location--' . sanitize_html_class( $args['style'] ) . ( $args['has_value'] ? ' has-value' : '' ) ) ); ?>" data-wgc-location-open aria-haspopup="dialog" aria-controls="wgc-location-modal">
	<svg class="wgc-location__icon" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
	<span class="<?php echo esc_attr( Helpers::css_class( 'location__text' ) ); ?>">
		<small><?php echo esc_html( $args['label'] ); ?></small>
		<strong data-wgc-location-value><?php echo esc_html( $args['value'] ); ?></strong>
	</span>
</button>
