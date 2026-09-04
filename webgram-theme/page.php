<?php
/**
 * Static pages.
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
				get_template_part( 'template-parts/content/content', 'page' );
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
			endwhile;
			?>
		</main>
	</div>
</div>
<?php
get_footer();
