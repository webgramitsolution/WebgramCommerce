<?php
/**
 * Single post content.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-entry' ); ?>>
	<header class="wg-entry__header">
		<h1 class="wg-entry__title"><?php the_title(); ?></h1>
		<div class="wg-entry__meta">
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<span class="wg-entry__author"><?php the_author_posts_link(); ?></span>
		</div>
	</header>
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="wg-entry__media"><?php the_post_thumbnail( 'large', [ 'fetchpriority' => 'high' ] ); ?></figure>
	<?php endif; ?>
	<div class="wg-entry__content wg-prose">
		<?php
		the_content();
		wp_link_pages( [ 'before' => '<nav class="wg-page-links">' . esc_html__( 'Pages:', 'webgram' ), 'after' => '</nav>' ] );
		?>
	</div>
	<footer class="wg-entry__footer">
		<?php the_tags( '<div class="wg-entry__tags">', '', '</div>' ); ?>
	</footer>
</article>
