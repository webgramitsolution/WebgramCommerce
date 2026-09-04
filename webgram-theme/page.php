<?php
/**
 * Static pages.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! get_post_meta( get_the_ID(), '_webgram_hide_title', true ) ) {
	webgram_part( 'misc/page-title', [ 'title' => get_the_title() ] );
}
?>
<div class="wg-container">
	<div class="<?php echo esc_attr( webgram_content_classes() ); ?>">
		<main id="primary" class="wg-main">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content/content', 'page' );
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>
		</main>
		<?php if ( in_array( webgram_layout(), [ 'sidebar-left', 'sidebar-right' ], true ) ) : ?>
			<?php get_sidebar(); ?>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
