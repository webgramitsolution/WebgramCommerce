<?php
/**
 * Variation chips row shared by the standard and list cards. $args: chips (array|null), permalink.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$chips = $args['chips'] ?? null;
if ( ! $chips || empty( $chips['chips'] ) ) {
	return;
}
?>
<div class="wg-card__chips wg-chips wg-chips--<?php echo esc_attr( $chips['style'] ); ?>" data-wg-chips data-attribute="<?php echo esc_attr( $chips['key'] ); ?>" role="group" aria-label="<?php echo esc_attr( $chips['label'] ); ?>">
	<?php foreach ( $chips['chips'] as $webgram_i => $webgram_chip ) : ?>
		<button type="button" class="wg-chip<?php echo 0 === $webgram_i ? ' is-selected' : ''; ?>" data-value="<?php echo esc_attr( $webgram_chip['value'] ); ?>" aria-pressed="<?php echo 0 === $webgram_i ? 'true' : 'false'; ?>" title="<?php echo esc_attr( $webgram_chip['label'] ); ?>"<?php echo $webgram_chip['color'] ? ' style="--wg-chip-color:' . esc_attr( $webgram_chip['color'] ) . '"' : ''; ?>>
			<?php if ( $webgram_chip['image'] ) : ?>
				<img src="<?php echo esc_url( $webgram_chip['image'] ); ?>" alt="" loading="lazy">
			<?php elseif ( ! $webgram_chip['color'] ) : ?>
				<?php echo esc_html( $webgram_chip['label'] ); ?>
			<?php else : ?>
				<span class="wg-sr-only"><?php echo esc_html( $webgram_chip['label'] ); ?></span>
			<?php endif; ?>
		</button>
	<?php endforeach; ?>
	<?php if ( $chips['more'] > 0 ) : ?>
		<a class="wg-chip wg-chip--more" href="<?php echo esc_url( $args['permalink'] ); ?>">+<?php echo (int) $chips['more']; ?></a>
	<?php endif; ?>
</div>
<script type="application/json" data-wg-variations><?php echo wp_json_encode( $chips['variations'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON inside a data script tag. ?></script>
