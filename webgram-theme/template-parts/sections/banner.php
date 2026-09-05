<?php
/**
 * Promotional banner. $args: image (id), image_mobile (id), heading, text, button_text, button_url, align,
 * height (px), overlay (0-100), text_color, radius (bool).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_image  = (int) ( $args['image'] ?? 0 );
$webgram_mobile = (int) ( $args['image_mobile'] ?? 0 );
$webgram_align  = in_array( $args['align'] ?? 'left', [ 'left', 'center', 'right' ], true ) ? (string) $args['align'] : 'left';
$webgram_url    = (string) ( $args['button_url'] ?? '' );
$webgram_style  = '--wg-banner-height:' . max( 120, (int) ( $args['height'] ?? 320 ) ) . 'px;--wg-banner-overlay:' . ( max( 0, min( 100, (int) ( $args['overlay'] ?? 20 ) ) ) / 100 ) . ( ! empty( $args['text_color'] ) ? ';color:' . sanitize_hex_color( (string) $args['text_color'] ) : '' );
$webgram_tag    = $webgram_url && empty( $args['button_text'] ) ? 'a' : 'div';
?>
<<?php echo esc_attr( $webgram_tag ); ?> class="wg-banner wg-banner--<?php echo esc_attr( $webgram_align ); ?><?php echo empty( $args['radius'] ) ? '' : ' wg-banner--rounded'; ?>" style="<?php echo esc_attr( $webgram_style ); ?>"<?php echo 'a' === $webgram_tag ? ' href="' . esc_url( $webgram_url ) . '"' : ''; ?> data-wg-component="banner-parallax">
	<?php if ( $webgram_image ) : ?>
		<picture class="wg-banner__media">
			<?php if ( $webgram_mobile ) : ?>
				<source media="(max-width: 767.98px)" srcset="<?php echo esc_url( (string) wp_get_attachment_image_url( $webgram_mobile, 'large' ) ); ?>">
			<?php endif; ?>
			<?php echo wp_get_attachment_image( $webgram_image, 'full', false, [ 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
		</picture>
	<?php endif; ?>
	<span class="wg-banner__overlay" aria-hidden="true"></span>
	<?php if ( ! empty( $args['heading'] ) || ! empty( $args['text'] ) || ! empty( $args['button_text'] ) ) : ?>
		<div class="wg-banner__content">
			<?php if ( ! empty( $args['heading'] ) ) : ?>
				<h2 class="wg-banner__heading"><?php echo esc_html( (string) $args['heading'] ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $args['text'] ) ) : ?>
				<p class="wg-banner__text"><?php echo esc_html( (string) $args['text'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $args['button_text'] ) ) : ?>
				<a class="wg-btn wg-btn--primary" href="<?php echo esc_url( $webgram_url ?: '#' ); ?>"><?php echo esc_html( (string) $args['button_text'] ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</<?php echo esc_attr( $webgram_tag ); ?>>
