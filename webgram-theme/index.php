<?php
/**
 * Fallback template: blog index and archives.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

$webgram_title = '';
if ( is_home() && ! is_front_page() ) {
	$webgram_title = single_post_title( '', false );
} elseif ( is_archive() ) {
	$webgram_title = get_the_archive_title();
} elseif ( is_search() ) {
	/* translators: %s: search query */
	$webgram_title = sprintf( __( 'Results for "%s"', 'webgram' ), get_search_query() );
}
webgram_part( 'misc/page-title', [ 'title' => $webgram_title, 'description' => is_archive() ? get_the_archive_description() : '' ] );
?>
<div class="wg-container">
	<div class="<?php echo esc_attr( webgram_content_classes() ); ?>">
		<main id="primary" class="wg-main">
			<?php if ( have_posts() ) : ?>
				<div class="wg-posts wg-posts--<?php echo esc_attr( (string) webgram_option( 'blog_card_style' ) ); ?> wg-posts--cols-<?php echo esc_attr( (string) ( is_search() ? webgram_option( 'search_page_columns' ) : webgram_option( 'blog_columns' ) ) ); ?>">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content/content', get_post_type() );
					endwhile;
					?>
				</div>
				<?php webgram_part( 'misc/pagination' ); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
			<?php endif; ?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
