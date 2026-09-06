<?php
/**
 * Announcement marquee. $args: settings (messages, mode, speed, direction, pause, gap, separator, interval), device.
 * CSS animates a duplicated track for a seamless loop; JS only duplicates content to fill the width.
 * prefers-reduced-motion switches to the static mode through CSS.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_s   = (array) ( $args['settings'] ?? [] );
$webgram_msg = array_values( array_filter( (array) ( $webgram_s['messages'] ?? [] ), static fn( $m ) => ! empty( $m['text'] ) ) );
if ( ! $webgram_msg ) {
	return;
}
$webgram_mode = (string) ( $webgram_s['mode'] ?? 'marquee' );
$webgram_sep  = (string) ( $webgram_s['separator'] ?? 'dot' );
?>
<div class="wg-marquee wg-marquee--<?php echo esc_attr( $webgram_mode ); ?> wg-marquee--sep-<?php echo esc_attr( $webgram_sep ); ?><?php echo ! empty( $webgram_s['pause'] ) ? ' wg-marquee--pause' : ''; ?>"
	data-wg-component="marquee"
	data-mode="<?php echo esc_attr( $webgram_mode ); ?>"
	data-speed="<?php echo (int) ( $webgram_s['speed'] ?? 50 ); ?>"
	data-direction="<?php echo esc_attr( (string) ( $webgram_s['direction'] ?? 'left' ) ); ?>"
	data-interval="<?php echo (int) ( $webgram_s['interval'] ?? 4000 ); ?>"
	style="--wg-marquee-gap:<?php echo (int) ( $webgram_s['gap'] ?? 48 ); ?>px">
	<div class="wg-marquee__track" aria-live="off">
		<?php foreach ( $webgram_msg as $webgram_i => $webgram_m ) : ?>
			<?php $webgram_tag = ! empty( $webgram_m['link'] ) ? 'a' : 'span'; ?>
			<<?php echo esc_attr( $webgram_tag ); ?> class="wg-marquee__item<?php echo 0 === $webgram_i ? ' is-active' : ''; ?>"<?php echo 'a' === $webgram_tag ? ' href="' . esc_url( (string) $webgram_m['link'] ) . '"' : ''; ?>>
				<?php
				if ( ! empty( $webgram_m['icon'] ) ) {
					webgram_icon( (string) $webgram_m['icon'], 'wg-marquee__icon' );
				}
				?>
				<span><?php echo esc_html( (string) $webgram_m['text'] ); ?></span>
			</<?php echo esc_attr( $webgram_tag ); ?>>
		<?php endforeach; ?>
	</div>
</div>
