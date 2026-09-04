<?php
/**
 * Cart offer progress with milestone nodes. $args: milestones, percent, next, achieved, message.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'progress' ) ); ?>" data-wgc-progress>
	<p class="<?php echo esc_attr( Helpers::css_class( 'progress__text' ) ); ?>"><?php echo esc_html( $args['message'] ); ?></p>
	<div class="<?php echo esc_attr( Helpers::css_class( 'progress__bar' ) ); ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $args['percent']; ?>">
		<span class="<?php echo esc_attr( Helpers::css_class( 'progress__fill' ) ); ?>" style="width:<?php echo (int) $args['percent']; ?>%"></span>
		<?php $wgc_n = count( $args['milestones'] ); ?>
		<?php foreach ( $args['milestones'] as $wgc_i => $wgc_m ) : ?>
			<span class="<?php echo esc_attr( Helpers::css_class( 'progress__node', $wgc_m['done'] ? 'is-done' : '' ) ); ?>" style="inset-inline-start:<?php echo esc_attr( (string) ( ( $wgc_i + 1 ) / $wgc_n * 100 ) ); ?>%" title="<?php echo esc_attr( $wgc_m['label'] ); ?>">
				<?php if ( $wgc_m['done'] ) : ?><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg><?php endif; ?>
			</span>
		<?php endforeach; ?>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'progress__labels' ) ); ?>">
		<?php foreach ( $args['milestones'] as $wgc_m ) : ?>
			<span class="<?php echo $wgc_m['done'] ? 'is-done' : ''; ?>"><?php echo esc_html( $wgc_m['label'] ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
