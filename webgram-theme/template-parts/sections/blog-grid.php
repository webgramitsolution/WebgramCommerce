<?php
/**
 * "From Our Blog" grid (spec 4.3 item 9): post cards with 16:10 image, title, excerpt, Read more.
 * $args: title, subtitle, count, columns, category (slug), show_excerpt, link_url, link_text.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_query = new WP_Query(
	[
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 12, (int) ( $args['count'] ?? 4 ) ) ),
		'ignore_sticky_posts' => true,
		'category_name'       => sanitize_title( (string) ( $args['category'] ?? '' ) ),
		'no_found_rows'       => true,
	]
);
if ( ! $webgram_query->have_posts() ) {
	return;
}
?>
<section class="wg-blog-grid" style="--wg-blog-cols:<?php echo (int) max( 1, min( 4, (int) ( $args['columns'] ?? 4 ) ) ); ?>">
	<?php webgram_part( 'sections/section-heading', [ 'title' => (string) ( $args['title'] ?? '' ), 'subtitle' => (string) ( $args['subtitle'] ?? '' ), 'link_url' => (string) ( $args['link_url'] ?? '' ), 'link_text' => (string) ( $args['link_text'] ?? '' ) ] ); ?>
	<div class="wg-blog-grid__row" data-wg-component="carousel" data-wg-carousel="mobile">
		<?php while ( $webgram_query->have_posts() ) : ?>
			<?php $webgram_query->the_post(); ?>
			<article class="wg-post-card wg-post-card--grid">
				<a class="wg-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'webgram-card-wide', [ 'loading' => 'lazy' ] ); ?>
					<?php endif; ?>
				</a>
				<div class="wg-post-card__body">
					<div class="wg-post-card__meta"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></div>
					<h3 class="wg-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php if ( ! isset( $args['show_excerpt'] ) || $args['show_excerpt'] ) : ?>
						<div class="wg-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></div>
					<?php endif; ?>
					<a class="wg-link-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'webgram' ); ?><?php webgram_icon( 'arrow-right' ); ?></a>
				</div>
			</article>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</section>
