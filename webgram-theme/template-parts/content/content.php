<?php
/**
 * Post card in archives.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="wg-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'webgram-card-tall', [ 'loading' => 'lazy' ] ); ?>
		</a>
	<?php endif; ?>
	<div class="wg-post-card__body">
		<div class="wg-post-card__meta">
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>
		<h2 class="wg-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="wg-post-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="wg-link-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'webgram' ); ?></a>
	</div>
</article>
