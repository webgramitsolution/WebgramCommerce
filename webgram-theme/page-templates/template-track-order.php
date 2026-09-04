<?php
/**
 * Template Name: Track Order
 * Description: Centered card with the order tracking form (Webgram Core) and the page content above it.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
webgram_part( 'misc/page-title', [ 'title' => get_the_title() ] );
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-main--narrow">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<div class="wg-page-card">
				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<div class="wg-prose wg-page-card__intro"><?php the_content(); ?></div>
				<?php endif; ?>
				<?php if ( webgram_has_core( 'woo_enhancements' ) ) : ?>
					<?php echo do_shortcode( '[webgram_track_order]' ); ?>
				<?php else : ?>
					<p class="wg-notice wg-notice--info"><?php esc_html_e( 'Order tracking is provided by the Webgram Core plugin.', 'webgram' ); ?></p>
					<?php if ( function_exists( 'wc_get_account_endpoint_url' ) ) : ?>
						<p><a class="wg-btn wg-btn--primary" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php esc_html_e( 'View my orders', 'webgram' ); ?></a></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
		endwhile;
		?>
	</main>
</div>
<?php
get_footer();
