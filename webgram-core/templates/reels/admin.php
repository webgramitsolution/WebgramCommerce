<?php
/**
 * Reel metabox. $args: d (data), products (id => name), sources.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

$wgc_d = $args['d'];
$wgc_video_url  = $wgc_d['video_id'] ? wp_get_attachment_url( $wgc_d['video_id'] ) : '';
$wgc_poster_src = $wgc_d['poster'] ? wp_get_attachment_image_url( $wgc_d['poster'], 'medium' ) : '';
?>
<div class="wgc-reel-admin">
	<p>
		<label><input type="radio" name="wg_video_source" value="upload" <?php checked( $wgc_d['source'], 'upload' ); ?>> <?php esc_html_e( 'Uploaded video (MP4, 9:16)', 'webgram-core' ); ?></label>
		&nbsp;&nbsp;<label><input type="radio" name="wg_video_source" value="external" <?php checked( $wgc_d['source'], 'external' ); ?>> <?php esc_html_e( 'External URL', 'webgram-core' ); ?></label>
	</p>
	<div class="wgc-image-field wgc-video-field">
		<input type="hidden" name="wg_video_id" value="<?php echo (int) $wgc_d['video_id']; ?>">
		<code class="wgc-video-field__name" <?php echo $wgc_video_url ? '' : 'hidden'; ?>><?php echo esc_html( basename( (string) $wgc_video_url ) ); ?></code>
		<button type="button" class="button wgc-video-select"><?php esc_html_e( 'Choose video', 'webgram-core' ); ?></button>
		<button type="button" class="button-link wgc-video-remove" <?php echo $wgc_video_url ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'webgram-core' ); ?></button>
	</div>
	<p>
		<label><?php esc_html_e( 'External video URL', 'webgram-core' ); ?><br>
		<input type="url" name="wg_video_url" value="<?php echo esc_attr( $wgc_d['url'] ); ?>" class="large-text" placeholder="https://youtube.com/shorts/... or https://.../video.mp4"></label>
		<span class="description"><?php echo esc_html( sprintf( /* translators: %s: list */ __( 'Supported: %s', 'webgram-core' ), implode( ', ', array_column( $args['sources'], 'label' ) ) ) ); ?></span>
	</p>
	<p><strong><?php esc_html_e( 'Poster image (required, 9:16)', 'webgram-core' ); ?></strong></p>
	<div class="wgc-image-field">
		<input type="hidden" name="wg_poster_id" value="<?php echo (int) $wgc_d['poster']; ?>">
		<img src="<?php echo esc_url( (string) $wgc_poster_src ); ?>" alt="" <?php echo $wgc_poster_src ? '' : 'hidden'; ?> style="max-height:160px">
		<button type="button" class="button wgc-image-select"><?php esc_html_e( 'Choose poster', 'webgram-core' ); ?></button>
		<button type="button" class="button-link wgc-image-remove" <?php echo $wgc_poster_src ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'webgram-core' ); ?></button>
	</div>
	<p><strong><?php esc_html_e( 'Products (up to 5)', 'webgram-core' ); ?></strong></p>
	<select name="wg_products[]" multiple size="6" class="widefat">
		<?php foreach ( $args['products'] as $wgc_id => $wgc_name ) : ?>
			<option value="<?php echo (int) $wgc_id; ?>" <?php selected( in_array( (int) $wgc_id, $wgc_d['products'], true ) ); ?>><?php echo esc_html( $wgc_name ); ?> (#<?php echo (int) $wgc_id; ?>)</option>
		<?php endforeach; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Hold Ctrl or Cmd to select several. The first product shows on the card; all of them appear in the viewer sheet.', 'webgram-core' ); ?></p>
	<p><label><?php esc_html_e( 'CTA text', 'webgram-core' ); ?><br><input type="text" name="wg_cta_text" value="<?php echo esc_attr( (string) $wgc_d['cta']['text'] ); ?>" class="regular-text"></label></p>
	<p><label><?php esc_html_e( 'CTA link', 'webgram-core' ); ?><br><input type="url" name="wg_cta_url" value="<?php echo esc_attr( (string) $wgc_d['cta']['url'] ); ?>" class="regular-text"></label></p>
</div>
