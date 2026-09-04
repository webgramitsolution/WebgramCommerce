<?php
/**
 * Header banner strip (Theme Settings > Header banner).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_type = (string) webgram_option( 'header_banner_type' );
$webgram_link = (string) webgram_option( 'header_banner_link' );
$webgram_days = (int) webgram_option( 'header_banner_remember' );
$webgram_key  = 'wg_banner_' . substr( md5( (string) wp_json_encode( [ $webgram_type, webgram_option( 'header_banner_text' ), webgram_option( 'header_banner_image' ), webgram_option( 'header_banner_block' ) ] ) ), 0, 8 );
?>
<div class="wg-banner wg-banner--<?php echo esc_attr( $webgram_type ); ?>" data-wg-component="banner" data-key="<?php echo esc_attr( $webgram_key ); ?>" data-days="<?php echo esc_attr( (string) $webgram_days ); ?>" style="--wg-banner-bg:<?php echo esc_attr( (string) webgram_option( 'header_banner_bg' ) ); ?>;--wg-banner-color:<?php echo esc_attr( (string) webgram_option( 'header_banner_color' ) ); ?>" hidden>
	<div class="wg-container wg-banner__inner">
		<?php if ( $webgram_link ) : ?><a class="wg-banner__link" href="<?php echo esc_url( $webgram_link ); ?>"><?php endif; ?>
		<?php if ( 'image' === $webgram_type && webgram_option( 'header_banner_image' ) ) : ?>
			<?php echo wp_get_attachment_image( (int) webgram_option( 'header_banner_image' ), 'full', false, [ 'class' => 'wg-banner__image' ] ); ?>
		<?php elseif ( 'block' === $webgram_type ) : ?>
			<?php webgram_render_block( (int) webgram_option( 'header_banner_block' ) ); ?>
		<?php else : ?>
			<div class="wg-banner__text"><?php echo wp_kses_post( (string) webgram_option( 'header_banner_text' ) ); ?></div>
		<?php endif; ?>
		<?php if ( $webgram_link ) : ?></a><?php endif; ?>
		<?php if ( webgram_option( 'header_banner_close' ) ) : ?>
			<button class="wg-banner__close" type="button" data-wg-banner-close aria-label="<?php esc_attr_e( 'Close banner', 'webgram' ); ?>"><?php webgram_icon( 'close' ); ?></button>
		<?php endif; ?>
	</div>
</div>
