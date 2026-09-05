<?php
/**
 * Page title band with optional breadcrumb.
 * $args: title (string), description (html), image (attachment id), type (page|post|blog|shop|search).
 * Reads the Page title tab plus the per-post overrides written by the Webgram options box.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_type  = (string) ( $args['type'] ?? webgram_page_title_type() );
$webgram_types = (array) webgram_option( 'page_title_types' );
$webgram_show  = (bool) webgram_option( 'page_title_show' ) && in_array( $webgram_type, $webgram_types, true );
$webgram_meta_image = 0;

if ( is_singular() ) {
	$webgram_override = (string) get_post_meta( get_the_ID(), '_webgram_page_title', true );
	if ( 'show' === $webgram_override ) {
		$webgram_show = (bool) webgram_option( 'page_title_show' );
	} elseif ( 'hide' === $webgram_override || get_post_meta( get_the_ID(), '_webgram_hide_title', true ) ) {
		$webgram_show = false;
	}
	$webgram_meta_image = (int) get_post_meta( get_the_ID(), '_webgram_page_title_image', true );
}

$webgram_breadcrumb = (bool) webgram_option( 'breadcrumb_show' );
if ( ! $webgram_show && ! $webgram_breadcrumb ) {
	return;
}

$webgram_title   = (string) ( $args['title'] ?? '' );
$webgram_image   = $webgram_meta_image ?: (int) ( $args['image'] ?? webgram_option( 'page_title_bg_image' ) );
$webgram_style   = $webgram_show ? (string) webgram_option( 'page_title_style' ) : 'minimal';
$webgram_heading = in_array( webgram_option( 'page_title_heading_size' ), [ 'h1', 'h2', 'h3' ], true ) ? (string) webgram_option( 'page_title_heading_size' ) : 'h1';
$webgram_overlay = Webgram_Settings_Sanitizer::color( (string) webgram_option( 'page_title_overlay' ) ) ?: '#111827';
$webgram_vars    = [
	'--wg-title-bg'       => Webgram_Settings_Sanitizer::color( (string) webgram_option( 'page_title_bg' ) ),
	'--wg-title-color'    => Webgram_Settings_Sanitizer::color( (string) webgram_option( 'page_title_color' ) ),
	'--wg-title-overlay'  => $webgram_overlay,
	'--wg-title-overlay-opacity' => (string) ( max( 0, min( 100, (int) webgram_option( 'page_title_overlay_opacity' ) ) ) / 100 ),
	'--wg-title-bg-size'  => (string) webgram_option( 'page_title_bg_size' ),
	'--wg-title-bg-position' => (string) webgram_option( 'page_title_bg_position' ),
];
if ( $webgram_image && 'minimal' !== $webgram_style ) {
	$webgram_vars['--wg-title-image'] = 'url(' . esc_url( (string) wp_get_attachment_image_url( $webgram_image, 'full' ) ) . ')';
}
$webgram_css = '';
foreach ( $webgram_vars as $webgram_var => $webgram_value ) {
	if ( '' !== (string) $webgram_value ) {
		$webgram_css .= $webgram_var . ':' . $webgram_value . ';';
	}
}
$webgram_classes = [
	'wg-page-title',
	'wg-page-title--' . $webgram_style,
	'wg-page-title--' . (string) webgram_option( 'page_title_size' ),
	'wg-page-title--mobile-' . (string) webgram_option( 'page_title_size_mobile' ),
	'wg-page-title--' . (string) webgram_option( 'page_title_align' ),
	'wg-page-title--type-' . $webgram_type,
];
if ( $webgram_image && 'minimal' !== $webgram_style ) {
	$webgram_classes[] = 'has-image';
}
if ( webgram_option( 'page_title_bg_parallax' ) ) {
	$webgram_classes[] = 'is-fixed';
}
if ( ! webgram_option( 'breadcrumb_mobile' ) ) {
	$webgram_classes[] = 'wg-page-title--crumb-desktop';
}
$webgram_crumb_below = 'below' === webgram_option( 'breadcrumb_position' );
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $webgram_classes ) ) ); ?>" style="<?php echo esc_attr( $webgram_css ); ?>">
	<div class="wg-container wg-page-title__inner">
		<?php if ( $webgram_breadcrumb && ! $webgram_crumb_below ) : ?>
			<?php webgram_part( 'misc/breadcrumb' ); ?>
		<?php endif; ?>
		<?php if ( $webgram_show && '' !== $webgram_title ) : ?>
			<<?php echo $webgram_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- whitelisted tag. ?> class="wg-page-title__heading"><?php echo wp_kses_post( $webgram_title ); ?></<?php echo $webgram_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php endif; ?>
		<?php if ( $webgram_show && ! empty( $args['description'] ) ) : ?>
			<div class="wg-page-title__description"><?php echo wp_kses_post( (string) $args['description'] ); ?></div>
		<?php endif; ?>
		<?php if ( $webgram_breadcrumb && $webgram_crumb_below ) : ?>
			<?php webgram_part( 'misc/breadcrumb' ); ?>
		<?php endif; ?>
	</div>
</div>
