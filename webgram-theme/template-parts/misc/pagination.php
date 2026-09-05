<?php
/**
 * Blog pagination: page numbers, or a "Load more" button (webgram/blog/pagination_type) handled by main.js.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_type = (string) apply_filters( 'webgram/blog/pagination_type', 'numbers' );
$webgram_next = 'load_more' === $webgram_type ? (string) get_next_posts_page_link() : '';

if ( 'load_more' === $webgram_type ) {
	if ( '' !== $webgram_next && (int) $GLOBALS['wp_query']->max_num_pages > max( 1, (int) get_query_var( 'paged' ) ) ) :
		?>
		<div class="wg-load-more-wrap" data-wg-component="blog-load-more">
			<a class="wg-btn wg-btn--outline wg-load-more" href="<?php echo esc_url( $webgram_next ); ?>" data-wg-blog-load-more data-target=".wg-posts" data-loading="<?php esc_attr_e( 'Loading', 'webgram' ); ?>"><?php esc_html_e( 'Load more', 'webgram' ); ?></a>
		</div>
		<?php
	endif;
	return;
}

the_posts_pagination(
	[
		'class'     => 'wg-pagination',
		'mid_size'  => 1,
		'prev_text' => '<span class="wg-sr-only">' . esc_html__( 'Previous page', 'webgram' ) . '</span>' . webgram_icon( 'chevron-left', '', false ),
		'next_text' => '<span class="wg-sr-only">' . esc_html__( 'Next page', 'webgram' ) . '</span>' . webgram_icon( 'chevron-right', '', false ),
	]
);
