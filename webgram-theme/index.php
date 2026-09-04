<?php
/**
 * Fallback template: blog index and archives.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wg-container">
	<div class="<?php echo esc_attr( webgram_content_classes() ); ?>">
		<main id="primary" class="wg-main">
			<?php if ( have_posts() ) : ?>
				<?php if ( is_home() && ! is_front_page() ) : ?>
					<h1 class="wg-page-title"><?php single_post_title(); ?></h1>
				<?php elseif ( is_archive() ) : ?>
					<h1 class="wg-page-title"><?php the_archive_title(); ?></h1>
					<?php the_archive_description( '<div class="wg-archive-description">', '</div>' ); ?>
				<?php endif; ?>

				<div class="wg-posts">
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
