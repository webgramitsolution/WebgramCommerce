<?php
/**
 * Single posts.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
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
