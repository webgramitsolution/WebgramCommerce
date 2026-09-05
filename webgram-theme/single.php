<?php
/**
 * Single posts.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

webgram_part( 'misc/page-title', [ 'title' => get_the_title(), 'type' => 'post' ] );
?>
<div class="wg-container">
	<div class="<?php echo esc_attr( webgram_content_classes() ); ?>">
		<main id="primary" class="wg-main">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content/content', 'single' );
				the_post_navigation(
					[
						'prev_text' => '<span class="wg-nav-label">' . esc_html__( 'Previous', 'webgram' ) . '</span> <span class="wg-nav-title">%title</span>',
						'next_text' => '<span class="wg-nav-label">' . esc_html__( 'Next', 'webgram' ) . '</span> <span class="wg-nav-title">%title</span>',
					]
				);
				if ( webgram_option( 'blog_related' ) ) {
					webgram_part( 'content/related-posts', [ 'count' => (int) webgram_option( 'blog_related_count' ) ] );
				}
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
