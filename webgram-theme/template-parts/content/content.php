<?php
/**
 * Post card in archives. Reads blog_meta, blog_featured_image and blog_card_style.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_meta = (array) webgram_option( 'blog_meta' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-post-card wg-post-card--' . sanitize_html_class( (string) webgram_option( 'blog_card_style' ) ) ); ?>>
	<?php if ( webgram_option( 'blog_featured_image' ) && has_post_thumbnail() ) : ?>
		<a class="wg-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'webgram-card-tall', [ 'loading' => webgram_option( 'perf_lazy_load' ) ? 'lazy' : 'eager' ] ); ?>
		</a>
	<?php endif; ?>
	<div class="wg-post-card__body">
		<?php webgram_part( 'content/post-meta', [ 'meta' => $webgram_meta ] ); ?>
		<h2 class="wg-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="wg-post-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="wg-link-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'webgram' ); ?></a>
	</div>
</article>
