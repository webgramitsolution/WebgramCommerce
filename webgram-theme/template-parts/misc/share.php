<?php
/**
 * Share row. $args: url, title.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_networks = (array) webgram_option( 'social_share_networks' );
if ( ! $webgram_networks ) {
	return;
}
$webgram_url   = rawurlencode( (string) $args['url'] );
$webgram_title = rawurlencode( (string) $args['title'] );
$webgram_links = [
	'facebook'  => [ 'https://www.facebook.com/sharer/sharer.php?u=' . $webgram_url, 'Facebook', 'social-facebook' ],
	'x'         => [ 'https://twitter.com/intent/tweet?url=' . $webgram_url . '&text=' . $webgram_title, 'X', 'social-x' ],
	'whatsapp'  => [ 'https://api.whatsapp.com/send?text=' . $webgram_title . '%20' . $webgram_url, 'WhatsApp', 'social-whatsapp' ],
	'pinterest' => [ 'https://pinterest.com/pin/create/button/?url=' . $webgram_url . '&description=' . $webgram_title, 'Pinterest', 'social-pinterest' ],
	'telegram'  => [ 'https://t.me/share/url?url=' . $webgram_url . '&text=' . $webgram_title, 'Telegram', 'social-telegram' ],
	'linkedin'  => [ 'https://www.linkedin.com/sharing/share-offsite/?url=' . $webgram_url, 'LinkedIn', 'social-linkedin' ],
	'email'     => [ 'mailto:?subject=' . $webgram_title . '&body=' . $webgram_url, __( 'Email', 'webgram' ), 'mail' ],
];
?>
<div class="wg-share" data-wg-component="share">
	<span class="wg-share__label"><?php esc_html_e( 'Share', 'webgram' ); ?></span>
	<?php foreach ( $webgram_networks as $webgram_net ) : ?>
		<?php if ( 'copy' === $webgram_net ) : ?>
			<button type="button" class="wg-share__link wg-share__link--copy" data-wg-copy="<?php echo esc_attr( (string) $args['url'] ); ?>" aria-label="<?php esc_attr_e( 'Copy link', 'webgram' ); ?>"><?php webgram_icon( 'copy' ); ?></button>
		<?php elseif ( isset( $webgram_links[ $webgram_net ] ) ) : ?>
			<a class="wg-share__link wg-share__link--<?php echo esc_attr( $webgram_net ); ?>" href="<?php echo esc_url( $webgram_links[ $webgram_net ][0] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $webgram_links[ $webgram_net ][1] ); ?>"><?php webgram_icon( $webgram_links[ $webgram_net ][2] ); ?></a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
