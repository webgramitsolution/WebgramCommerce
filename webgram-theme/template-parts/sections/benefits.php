<?php
/**
 * Benefits row (spec 4.3 item 12): 5 columns, red outline icon 28px, bold title, small grey text.
 * $args: items [{icon, title, text}], columns, style (row|cards).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_items = array_values( array_filter( (array) ( $args['items'] ?? [] ), static fn( $i ) => is_array( $i ) && ( ! empty( $i['title'] ) || ! empty( $i['text'] ) ) ) );
if ( ! $webgram_items ) {
	return;
}
$webgram_columns = max( 1, min( 6, (int) ( $args['columns'] ?? count( $webgram_items ) ) ) );
?>
<div class="wg-benefits wg-benefits--<?php echo esc_attr( 'cards' === ( $args['style'] ?? 'row' ) ? 'cards' : 'row' ); ?>" style="--wg-benefits-cols:<?php echo (int) $webgram_columns; ?>">
	<?php foreach ( $webgram_items as $webgram_item ) : ?>
		<div class="wg-benefit">
			<span class="wg-benefit__icon"><?php webgram_icon( (string) ( $webgram_item['icon'] ?? 'check-circle' ) ); ?></span>
			<?php if ( ! empty( $webgram_item['title'] ) ) : ?>
				<strong class="wg-benefit__title"><?php echo esc_html( (string) $webgram_item['title'] ); ?></strong>
			<?php endif; ?>
			<?php if ( ! empty( $webgram_item['text'] ) ) : ?>
				<span class="wg-benefit__text"><?php echo esc_html( (string) $webgram_item['text'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
