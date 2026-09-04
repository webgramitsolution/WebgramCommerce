<?php
/**
 * Page content.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'wg-entry wg-entry--page' ); ?>>
	<?php if ( ! get_post_meta( get_the_ID(), '_webgram_hide_title', true ) ) : ?>
		<header class="wg-entry__header"><h1 class="wg-entry__title"><?php the_title(); ?></h1></header>
	<?php endif; ?>
	<div class="wg-entry__content wg-prose">
		<?php
		the_content();
		wp_link_pages( [ 'before' => '<nav class="wg-page-links">' . esc_html__( 'Pages:', 'webgram' ), 'after' => '</nav>' ] );
		?>
	</div>
</article>
