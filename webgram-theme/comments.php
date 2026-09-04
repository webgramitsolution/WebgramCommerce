<?php
/**
 * Comments for posts and pages. WooCommerce product reviews use WooCommerce's own template and, with Core, the
 * Reviews module.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="wg-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="wg-comments__title">
			<?php
			$webgram_count = get_comments_number();
			/* translators: %s: number of comments */
			printf( esc_html( _n( '%s comment', '%s comments', $webgram_count, 'webgram' ) ), esc_html( number_format_i18n( $webgram_count ) ) );
			?>
		</h2>
		<ol class="wg-comments__list">
			<?php wp_list_comments( [ 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ] ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="wg-comments__closed"><?php esc_html_e( 'Comments are closed.', 'webgram' ); ?></p>
	<?php endif; ?>

	<?php comment_form( [ 'class_submit' => 'wg-btn wg-btn--primary' ] ); ?>
</section>
