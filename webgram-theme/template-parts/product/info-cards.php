<?php
/**
 * Info cards (Returns, Shipping). $args: cards (icon, title, text).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_cards = array_filter( (array) $args['cards'], static fn( $c ) => ! empty( $c['title'] ) );
if ( ! $webgram_cards ) {
	return;
}
?>
<div class="wg-info-cards">
	<?php foreach ( $webgram_cards as $webgram_card ) : ?>
		<div class="wg-info-card">
			<span class="wg-info-card__icon"><?php webgram_icon( (string) ( $webgram_card['icon'] ?? 'info' ) ); ?></span>
			<span class="wg-info-card__text"><strong><?php echo esc_html( (string) $webgram_card['title'] ); ?></strong><span><?php echo esc_html( (string) ( $webgram_card['text'] ?? '' ) ); ?></span></span>
		</div>
	<?php endforeach; ?>
</div>
