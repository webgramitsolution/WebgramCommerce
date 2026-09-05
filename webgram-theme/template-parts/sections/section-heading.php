<?php
/**
 * Section heading with the dot-line ornament on both sides (spec 4.3 item 4). $args: title, subtitle, align
 * (center|start), link_url, link_text, tag.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_title = (string) ( $args['title'] ?? '' );
$webgram_tag   = in_array( $args['tag'] ?? 'h2', [ 'h1', 'h2', 'h3', 'h4' ], true ) ? (string) $args['tag'] : 'h2';
if ( '' === $webgram_title && empty( $args['link_url'] ) ) {
	return;
}
?>
<div class="wg-section-heading<?php echo 'start' === ( $args['align'] ?? 'center' ) ? ' wg-section-heading--start' : ''; ?>">
	<?php if ( '' !== $webgram_title ) : ?>
		<span class="wg-section-heading__ornament" aria-hidden="true"></span>
		<div class="wg-section-heading__text">
			<<?php echo esc_attr( $webgram_tag ); ?> class="wg-section-heading__title"><?php echo esc_html( $webgram_title ); ?></<?php echo esc_attr( $webgram_tag ); ?>>
			<?php if ( ! empty( $args['subtitle'] ) ) : ?>
				<p class="wg-section-heading__subtitle"><?php echo esc_html( (string) $args['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
		<span class="wg-section-heading__ornament wg-section-heading__ornament--end" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( ! empty( $args['link_url'] ) && ! empty( $args['link_text'] ) ) : ?>
		<a class="wg-section-heading__link" href="<?php echo esc_url( (string) $args['link_url'] ); ?>"><?php echo esc_html( (string) $args['link_text'] ); ?><?php webgram_icon( 'arrow-right' ); ?></a>
	<?php endif; ?>
</div>
