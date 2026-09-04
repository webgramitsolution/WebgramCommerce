<?php
/**
 * Page title band with optional breadcrumb. $args: title (string), description (html), image (attachment id).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

if ( ! webgram_option( 'page_title_show' ) && ! webgram_option( 'breadcrumb_show' ) ) {
	return;
}
$webgram_title = (string) ( $args['title'] ?? '' );
$webgram_image = (int) ( $args['image'] ?? webgram_option( 'page_title_bg_image' ) );
$webgram_style = '--wg-title-bg:' . esc_attr( (string) webgram_option( 'page_title_bg' ) ) . ';--wg-title-color:' . esc_attr( (string) webgram_option( 'page_title_color' ) );
if ( $webgram_image ) {
	$webgram_style .= ';--wg-title-image:url(' . esc_url( (string) wp_get_attachment_image_url( $webgram_image, 'full' ) ) . ')';
}
?>
<div class="wg-page-title wg-page-title--<?php echo esc_attr( (string) webgram_option( 'page_title_size' ) ); ?> wg-page-title--<?php echo esc_attr( (string) webgram_option( 'page_title_align' ) ); ?><?php echo $webgram_image ? ' has-image' : ''; ?>" style="<?php echo $webgram_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>">
	<div class="wg-container wg-page-title__inner">
		<?php if ( webgram_option( 'breadcrumb_show' ) ) : ?>
			<?php webgram_part( 'misc/breadcrumb' ); ?>
		<?php endif; ?>
		<?php if ( webgram_option( 'page_title_show' ) && '' !== $webgram_title ) : ?>
			<h1 class="wg-page-title__heading"><?php echo wp_kses_post( $webgram_title ); ?></h1>
		<?php endif; ?>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<div class="wg-page-title__description"><?php echo wp_kses_post( (string) $args['description'] ); ?></div>
		<?php endif; ?>
	</div>
</div>
