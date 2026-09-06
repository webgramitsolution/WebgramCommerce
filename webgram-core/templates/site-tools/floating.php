<?php
/**
 * Floating blocks stack. $args: items, position, offset, devices, scroll, labels.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_classes = [ 'wgc-floating--' . $args['position'] ];
foreach ( (array) $args['devices'] as $wgc_device ) {
	$wgc_classes[] = 'wgc-floating--' . $wgc_device;
}
if ( ! empty( $args['labels'] ) ) {
	$wgc_classes[] = 'wgc-floating--labels';
}
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'floating', implode( ' ', $wgc_classes ) ) ); ?>" data-wgc-floating data-scroll="<?php echo (int) $args['scroll']; ?>" style="--wgc-floating-offset:<?php echo (int) $args['offset']; ?>px" <?php echo (int) $args['scroll'] > 0 ? 'hidden' : ''; ?>>
	<?php foreach ( (array) $args['items'] as $wgc_item ) : ?>
		<?php if ( 'block' === $wgc_item['type'] ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'floating__block' ) ); ?>"><?php echo \Webgram\Core\Modules\SiteTools\Blocks::render( (int) $wgc_item['block'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block output. ?></div>
		<?php else : ?>
			<a class="<?php echo esc_attr( Helpers::css_class( 'floating__btn', 'wgc-floating__btn--' . $wgc_item['type'] ) ); ?>" href="<?php echo esc_url( $wgc_item['url'] ); ?>" <?php echo 'whatsapp' === $wgc_item['type'] ? 'target="_blank" rel="noopener"' : ''; ?> aria-label="<?php echo esc_attr( $wgc_item['label'] ); ?>" data-wg-cta="floating-<?php echo esc_attr( $wgc_item['type'] ); ?>"<?php echo $wgc_item['color'] ? ' style="--wgc-floating-bg:' . esc_attr( $wgc_item['color'] ) . '"' : ''; ?>>
				<?php if ( function_exists( 'webgram_icon' ) ) : ?>
					<?php echo webgram_icon( $wgc_item['icon'], '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG. ?>
				<?php elseif ( 'whatsapp' === $wgc_item['type'] ) : ?>
					<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.1.6a2.7 2.7 0 0 0 1.8-1.2 2.2 2.2 0 0 0 .1-1.2c0-.1-.2-.2-.4-.3z"/></svg>
				<?php else : ?>
					<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l1.9-4.6A8.4 8.4 0 1 1 21 11.5z"/></svg>
				<?php endif; ?>
				<span class="<?php echo esc_attr( Helpers::css_class( 'floating__label' ) ); ?>"><?php echo esc_html( $wgc_item['label'] ); ?></span>
			</a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
