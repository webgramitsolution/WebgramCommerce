<?php
/**
 * Not found.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-404">
		<div class="wg-empty">
			<p class="wg-empty__code">404</p>
			<h1 class="wg-empty__title"><?php esc_html_e( 'This page could not be found', 'webgram' ); ?></h1>
			<p class="wg-empty__text"><?php esc_html_e( 'The link may be outdated, or the page may have moved. Try searching, or head back to the homepage.', 'webgram' ); ?></p>
			<?php get_search_form(); ?>
			<a class="wg-btn wg-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to homepage', 'webgram' ); ?></a>
		</div>
	</main>
</div>
<?php
get_footer();
