<?php
/**
 * Subcategory chips above the products. $args: terms (WP_Term[]), shape (circle|square|rounded).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wg-subcats wg-subcats--<?php echo esc_attr( $args['shape'] ); ?>" data-wg-component="scroller">
	<?php foreach ( $args['terms'] as $webgram_term ) : ?>
		<?php $webgram_thumb = (int) get_term_meta( $webgram_term->term_id, 'thumbnail_id', true ); ?>
		<a class="wg-subcats__item" href="<?php echo esc_url( (string) get_term_link( $webgram_term ) ); ?>">
			<span class="wg-subcats__media">
				<?php if ( $webgram_thumb ) : ?>
					<?php echo wp_get_attachment_image( $webgram_thumb, 'webgram-thumb', false, [ 'loading' => 'lazy' ] ); ?>
				<?php else : ?>
					<?php webgram_icon( 'grid' ); ?>
				<?php endif; ?>
			</span>
			<span class="wg-subcats__label"><?php echo esc_html( $webgram_term->name ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
