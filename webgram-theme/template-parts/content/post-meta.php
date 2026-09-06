<?php
/**
 * Post meta row. $args: meta (array of date, author, category, comments).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_meta = array_values( array_intersect( [ 'date', 'author', 'category', 'comments' ], (array) ( $args['meta'] ?? [] ) ) );
if ( ! $webgram_meta ) {
	return;
}
?>
<div class="wg-post-meta">
	<?php foreach ( $webgram_meta as $webgram_item ) : ?>
		<?php if ( 'date' === $webgram_item ) : ?>
			<time class="wg-post-meta__item" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		<?php elseif ( 'author' === $webgram_item ) : ?>
			<span class="wg-post-meta__item wg-post-meta__author"><?php the_author_posts_link(); ?></span>
		<?php elseif ( 'category' === $webgram_item && get_the_category_list( ', ' ) ) : ?>
			<span class="wg-post-meta__item wg-post-meta__categories"><?php echo wp_kses_post( get_the_category_list( ', ' ) ); ?></span>
		<?php elseif ( 'comments' === $webgram_item && ( comments_open() || get_comments_number() ) ) : ?>
			<a class="wg-post-meta__item" href="<?php comments_link(); ?>"><?php echo esc_html( sprintf( /* translators: %s: number of comments. */ _n( '%s comment', '%s comments', (int) get_comments_number(), 'webgram' ), number_format_i18n( (int) get_comments_number() ) ) ); ?></a>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
