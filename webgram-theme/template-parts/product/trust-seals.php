<?php
/**
 * Trust seals row: circular seals with icon and text. $args: seals (icon, label).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_seals = array_slice( array_filter( (array) $args['seals'], static fn( $s ) => ! empty( $s['label'] ) ), 0, 6 );
if ( ! $webgram_seals ) {
	return;
}
?>
<div class="wg-seals wg-seals--<?php echo count( $webgram_seals ); ?>">
	<?php foreach ( $webgram_seals as $webgram_seal ) : ?>
		<div class="wg-seal">
			<span class="wg-seal__circle"><?php webgram_icon( (string) ( $webgram_seal['icon'] ?? 'shield' ) ); ?></span>
			<span class="wg-seal__label"><?php echo esc_html( (string) $webgram_seal['label'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
