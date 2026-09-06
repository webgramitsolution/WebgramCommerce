<?php
/**
 * Template Name: Bulk Order
 * Description: Bulk inquiry form with a benefits column and a WhatsApp button.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

get_header();
webgram_part( 'misc/page-title', [ 'title' => get_the_title() ] );

$webgram_benefits = array_filter( array_map( 'trim', explode( "\n", (string) apply_filters( 'webgram/bulk_order/benefits', '' ) ) ) );
$webgram_whatsapp = (string) apply_filters( 'webgram/bulk_order/whatsapp_url', '' );
?>
<div class="wg-container">
	<main id="primary" class="wg-main">
		<div class="wg-bulk">
			<div class="wg-bulk__form wg-page-card">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( '' !== trim( get_the_content() ) ) {
						echo '<div class="wg-prose wg-page-card__intro">';
						the_content();
						echo '</div>';
					}
				endwhile;
				if ( webgram_has_core( 'woo_enhancements' ) ) {
					echo do_shortcode( '[webgram_bulk_inquiry]' );
				} else {
					echo '<p class="wg-notice wg-notice--info">' . esc_html__( 'The bulk inquiry form is provided by the Webgram Core plugin.', 'webgram' ) . '</p>';
				}
				?>
			</div>
			<aside class="wg-bulk__side">
				<?php if ( $webgram_benefits ) : ?>
					<h2 class="wg-bulk__title"><?php esc_html_e( 'Why order in bulk?', 'webgram' ); ?></h2>
					<ul class="wg-bulk__benefits">
						<?php foreach ( $webgram_benefits as $webgram_b ) : ?>
							<li><?php webgram_icon( 'check-circle' ); ?><span><?php echo esc_html( $webgram_b ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $webgram_whatsapp ) : ?>
					<a class="wg-btn wg-btn--block wg-bulk__whatsapp" href="<?php echo esc_url( $webgram_whatsapp ); ?>" target="_blank" rel="noopener"><?php webgram_icon( 'social-whatsapp' ); ?><?php esc_html_e( 'Chat on WhatsApp', 'webgram' ); ?></a>
				<?php endif; ?>
				<?php do_action( 'webgram/bulk_order/sidebar' ); ?>
			</aside>
		</div>
	</main>
</div>
<?php
get_footer();
