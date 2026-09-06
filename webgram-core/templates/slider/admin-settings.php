<?php
/**
 * Admin: slider settings. $args: settings.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

$wgc_s = $args['settings'];
$wgc_check = static function ( string $key, string $label ) use ( $wgc_s ): void {
	printf( '<p><label><input type="checkbox" name="wg_slider[%1$s]" value="1" %2$s> %3$s</label></p>', esc_attr( $key ), checked( ! empty( $wgc_s[ $key ] ), true, false ), esc_html( $label ) );
};
?>
<?php $wgc_check( 'autoplay', __( 'Autoplay', 'webgram-core' ) ); ?>
<p><label><?php esc_html_e( 'Delay (ms)', 'webgram-core' ); ?> <input type="number" name="wg_slider[delay]" value="<?php echo (int) $wgc_s['delay']; ?>" min="1000" max="30000" step="500" class="small-text"></label></p>
<p><label><?php esc_html_e( 'Transition speed (ms)', 'webgram-core' ); ?> <input type="number" name="wg_slider[speed]" value="<?php echo (int) ( $wgc_s['speed'] ?? 700 ); ?>" min="100" max="3000" step="50" class="small-text"></label></p>
<?php $wgc_check( 'pause_hover', __( 'Pause on hover', 'webgram-core' ) ); ?>
<?php $wgc_check( 'loop', __( 'Loop', 'webgram-core' ) ); ?>
<?php $wgc_check( 'navigation', __( 'Arrows (shown on hover)', 'webgram-core' ) ); ?>
<?php $wgc_check( 'pagination', __( 'Dots', 'webgram-core' ) ); ?>
<?php $wgc_check( 'lazy', __( 'Lazy load slides after the first', 'webgram-core' ) ); ?>
<?php $wgc_check( 'full_width', __( 'Full screen width', 'webgram-core' ) ); ?>
<p><label><?php esc_html_e( 'Effect', 'webgram-core' ); ?>
	<select name="wg_slider[effect]">
		<option value="fade" <?php selected( $wgc_s['effect'], 'fade' ); ?>><?php esc_html_e( 'Fade', 'webgram-core' ); ?></option>
		<option value="slide" <?php selected( $wgc_s['effect'], 'slide' ); ?>><?php esc_html_e( 'Slide', 'webgram-core' ); ?></option>
	</select></label></p>
<p><label><?php esc_html_e( 'Height', 'webgram-core' ); ?>
	<select name="wg_slider[height_mode]">
		<option value="ratio" <?php selected( $wgc_s['height_mode'], 'ratio' ); ?>><?php esc_html_e( 'Aspect ratio', 'webgram-core' ); ?></option>
		<option value="fixed" <?php selected( $wgc_s['height_mode'], 'fixed' ); ?>><?php esc_html_e( 'Fixed pixels', 'webgram-core' ); ?></option>
		<option value="viewport" <?php selected( $wgc_s['height_mode'], 'viewport' ); ?>><?php esc_html_e( 'Full viewport', 'webgram-core' ); ?></option>
	</select></label></p>
<p><label><?php esc_html_e( 'Ratio desktop (W:H)', 'webgram-core' ); ?> <input type="text" name="wg_slider[ratio]" value="<?php echo esc_attr( $wgc_s['ratio'] ); ?>" class="small-text" placeholder="16:6"></label></p>
<p><label><?php esc_html_e( 'Ratio mobile (W:H)', 'webgram-core' ); ?> <input type="text" name="wg_slider[ratio_mobile]" value="<?php echo esc_attr( $wgc_s['ratio_mobile'] ); ?>" class="small-text" placeholder="4:5"></label></p>
<p><label><?php esc_html_e( 'Fixed height (px)', 'webgram-core' ); ?> <input type="number" name="wg_slider[height]" value="<?php echo (int) $wgc_s['height']; ?>" min="200" max="1200" class="small-text"></label></p>
