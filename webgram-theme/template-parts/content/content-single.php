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
		<?php webgram_part( 'content/post-meta', [ 'meta' => (array) webgram_option( 'blog_meta' ) ] ); ?>
	</header>
	<?php if ( webgram_option( 'blog_featured_image' ) && has_post_thumbnail() ) : ?>
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
		<?php if ( webgram_option( 'blog_share' ) ) : ?>
			<?php webgram_part( 'misc/share', [ 'url' => get_permalink(), 'title' => get_the_title() ] ); ?>
		<?php endif; ?>
	</footer>
</article>
