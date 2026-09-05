<?php
/**
 * Floating social sidebar (right edge, vertically centered, brand colors).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_links = array_filter( (array) webgram_option( 'social_links' ), static fn( $l ) => ! empty( $l['url'] ) && ! empty( $l['network'] ) );
if ( ! $webgram_links ) {
	return;
}
$webgram_devices = array_map( 'sanitize_html_class', (array) webgram_option( 'social_sidebar_devices' ) );
$webgram_names   = webgram_social_networks();
?>
<div class="wg-social-sidebar wg-social-sidebar--<?php echo esc_attr( (string) webgram_option( 'social_sidebar_position' ) ); ?> <?php echo esc_attr( implode( ' ', array_map( static fn( $d ) => 'wg-show-' . $d, $webgram_devices ) ) ); ?>">
	<?php foreach ( $webgram_links as $webgram_link ) : ?>
		<?php $webgram_net = sanitize_key( (string) $webgram_link['network'] ); ?>
		<a class="wg-social-sidebar__link wg-social-sidebar__link--<?php echo esc_attr( $webgram_net ); ?>" href="<?php echo esc_url( (string) $webgram_link['url'] ); ?>" target="_blank" rel="noopener" style="--wg-brand:<?php echo esc_attr( webgram_social_color( $webgram_net ) ); ?>" aria-label="<?php echo esc_attr( (string) ( $webgram_names[ $webgram_net ] ?? $webgram_net ) ); ?>">
			<?php webgram_icon( 'social-' . $webgram_net ); ?>
		</a>
	<?php endforeach; ?>
</div>
