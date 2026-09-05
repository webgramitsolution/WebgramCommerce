<?php
/**
 * "About Us" split (spec 4.3 item 8): image left 40%, heading with ornament, paragraphs, 3 benefit cards,
 * red CTA with arrow icon. $args: image, title, text (html), items [{icon,title,text}], button_text, button_url,
 * image_position (left|right).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_image = (int) ( $args['image'] ?? 0 );
?>
<section class="wg-about<?php echo 'right' === ( $args['image_position'] ?? 'left' ) ? ' wg-about--image-right' : ''; ?>">
	<?php if ( $webgram_image ) : ?>
		<div class="wg-about__media"><?php echo wp_get_attachment_image( $webgram_image, 'large', false, [ 'loading' => 'lazy' ] ); ?></div>
	<?php endif; ?>
	<div class="wg-about__body">
		<?php webgram_part( 'sections/section-heading', [ 'title' => (string) ( $args['title'] ?? '' ), 'subtitle' => (string) ( $args['subtitle'] ?? '' ), 'align' => 'start' ] ); ?>
		<?php if ( ! empty( $args['text'] ) ) : ?>
			<div class="wg-about__text"><?php echo wp_kses_post( wpautop( (string) $args['text'] ) ); ?></div>
		<?php endif; ?>
		<?php webgram_part( 'sections/benefits', [ 'items' => (array) ( $args['items'] ?? [] ), 'columns' => 3, 'style' => 'cards' ] ); ?>
		<?php if ( ! empty( $args['button_text'] ) ) : ?>
			<a class="wg-btn wg-btn--primary wg-about__cta" href="<?php echo esc_url( (string) ( $args['button_url'] ?? '#' ) ); ?>"><?php echo esc_html( (string) $args['button_text'] ); ?><?php webgram_icon( 'arrow-right' ); ?></a>
		<?php endif; ?>
	</div>
</section>
