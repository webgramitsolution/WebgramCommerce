<?php
/**
 * Marquee strip (spec 4.3 item 2): the same marquee component as the top bar with its own colors.
 * $args: items [{icon, text}], bg_color, text_color, speed, separator (dot|line|none).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_items = array_values( array_filter( (array) ( $args['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ! empty( $i['text'] ) ) );
if ( ! $webgram_items ) {
	return;
}
$webgram_style = '--wg-marquee-gap:48px;';
if ( ! empty( $args['bg_color'] ) ) {
	$webgram_style .= '--wg-strip-bg:' . sanitize_hex_color( (string) $args['bg_color'] ) . ';';
}
if ( ! empty( $args['text_color'] ) ) {
	$webgram_style .= '--wg-strip-color:' . sanitize_hex_color( (string) $args['text_color'] ) . ';';
}
$webgram_sep = in_array( $args['separator'] ?? 'dot', [ 'dot', 'line', 'none' ], true ) ? (string) $args['separator'] : 'dot';
?>
<div class="wg-strip wg-marquee wg-marquee--marquee wg-marquee--sep-<?php echo esc_attr( $webgram_sep ); ?> wg-marquee--pause" data-wg-component="marquee" data-mode="marquee" data-speed="<?php echo (int) max( 10, min( 200, (int) ( $args['speed'] ?? 50 ) ) ); ?>" data-direction="left" style="<?php echo esc_attr( $webgram_style ); ?>">
	<div class="wg-marquee__track" aria-live="off">
		<?php foreach ( $webgram_items as $webgram_item ) : ?>
			<span class="wg-marquee__item">
				<?php
				if ( ! empty( $webgram_item['icon'] ) ) {
					webgram_icon( (string) $webgram_item['icon'], 'wg-marquee__icon' );
				}
				?>
				<span><?php echo esc_html( (string) $webgram_item['text'] ); ?></span>
			</span>
		<?php endforeach; ?>
	</div>
</div>
