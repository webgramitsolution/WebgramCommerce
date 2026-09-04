<?php
/**
 * 404 page: Core HTML Block when assigned, otherwise the theme empty state from Theme Settings > Other.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();

$webgram_layout = webgram_layout_id( '404' ) ?: (int) webgram_option( 'page_404_block' );
?>
<div class="wg-container">
	<main id="primary" class="wg-main wg-main--404">
		<?php if ( $webgram_layout ) : ?>
			<?php webgram_render_block( $webgram_layout ); ?>
		<?php else : ?>
			<div class="wg-empty wg-empty--404">
				<?php if ( webgram_option( 'page_404_image' ) ) : ?>
					<?php echo wp_get_attachment_image( (int) webgram_option( 'page_404_image' ), 'large', false, [ 'class' => 'wg-empty__image' ] ); ?>
				<?php else : ?>
					<p class="wg-empty__code" aria-hidden="true">404</p>
				<?php endif; ?>
				<h1 class="wg-empty__title"><?php echo esc_html( (string) webgram_option( 'page_404_title' ) ); ?></h1>
				<p class="wg-empty__text"><?php echo esc_html( (string) webgram_option( 'page_404_text' ) ); ?></p>
				<?php if ( webgram_option( 'page_404_button' ) ) : ?>
					<p><a class="wg-btn wg-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( (string) webgram_option( 'page_404_button' ) ); ?></a></p>
				<?php endif; ?>
				<?php if ( webgram_option( 'page_404_search' ) ) : ?>
					<?php get_search_form(); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</main>
</div>
<?php
get_footer();
