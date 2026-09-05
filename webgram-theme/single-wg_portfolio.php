<?php
/**
 * Single portfolio item: full width image, category, content and links to related items.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

webgram_part( 'misc/page-title', [ 'title' => get_the_title(), 'type' => 'page' ] );
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-main--portfolio">
		<?php
		while ( have_posts() ) :
			the_post();
			$webgram_terms = get_the_terms( get_the_ID(), 'wg_portfolio_cat' );
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-entry wg-entry--portfolio' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="wg-entry__media"><?php the_post_thumbnail( 'large', [ 'fetchpriority' => 'high' ] ); ?></figure>
				<?php endif; ?>
				<?php if ( is_array( $webgram_terms ) && $webgram_terms ) : ?>
					<p class="wg-entry__meta"><?php echo wp_kses_post( get_the_term_list( get_the_ID(), 'wg_portfolio_cat', '', ', ' ) ); ?></p>
				<?php endif; ?>
				<div class="wg-entry__content wg-prose"><?php the_content(); ?></div>
			</article>
			<?php
			the_post_navigation(
				[
					'prev_text' => '<span class="wg-nav-label">' . esc_html__( 'Previous project', 'webgram' ) . '</span> <span class="wg-nav-title">%title</span>',
					'next_text' => '<span class="wg-nav-label">' . esc_html__( 'Next project', 'webgram' ) . '</span> <span class="wg-nav-title">%title</span>',
				]
			);
		endwhile;
		?>
		<p class="wg-portfolio-back"><a class="wg-btn wg-btn--outline" href="<?php echo esc_url( (string) get_post_type_archive_link( 'wg_portfolio' ) ); ?>"><?php esc_html_e( 'All projects', 'webgram' ); ?></a></p>
	</main>
</div>
<?php
get_footer();
