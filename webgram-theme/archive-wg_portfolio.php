<?php
/**
 * Portfolio archive (Webgram Core portfolio post type): category filter chips and a grid of cards.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

$webgram_title = is_tax() ? single_term_title( '', false ) : (string) post_type_archive_title( '', false );
webgram_part( 'misc/page-title', [ 'title' => $webgram_title, 'description' => is_tax() ? term_description() : '', 'type' => 'blog' ] );
$webgram_cats = webgram_option( 'portfolio_filter' ) ? get_terms( [ 'taxonomy' => 'wg_portfolio_cat', 'hide_empty' => true ] ) : [];
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-main--portfolio">
		<?php if ( is_array( $webgram_cats ) && $webgram_cats ) : ?>
			<nav class="wg-chips wg-portfolio-filter" aria-label="<?php esc_attr_e( 'Portfolio categories', 'webgram' ); ?>">
				<a class="wg-chip<?php echo is_tax() ? '' : ' is-selected'; ?>" href="<?php echo esc_url( (string) get_post_type_archive_link( 'wg_portfolio' ) ); ?>"><?php esc_html_e( 'All', 'webgram' ); ?></a>
				<?php foreach ( $webgram_cats as $webgram_cat ) : ?>
					<a class="wg-chip<?php echo is_tax( 'wg_portfolio_cat', $webgram_cat->term_id ) ? ' is-selected' : ''; ?>" href="<?php echo esc_url( (string) get_term_link( $webgram_cat ) ); ?>"><?php echo esc_html( $webgram_cat->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<div class="wg-portfolio wg-portfolio--cols-<?php echo (int) webgram_option( 'portfolio_columns' ); ?>">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/content', 'wg_portfolio' );
				endwhile;
				?>
			</div>
			<?php webgram_part( 'misc/pagination' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/content', 'none' ); ?>
		<?php endif; ?>
	</main>
</div>
<?php
get_footer();
