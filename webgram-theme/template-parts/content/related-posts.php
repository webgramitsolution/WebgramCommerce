<?php
/**
 * Related posts under a single post: same categories first, then tags. $args: count.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_count = max( 1, min( 6, (int) ( $args['count'] ?? 3 ) ) );
$webgram_cats  = wp_get_post_categories( get_the_ID(), [ 'fields' => 'ids' ] );
$webgram_args  = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $webgram_count,
	'post__not_in'        => [ get_the_ID() ],
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
];
if ( $webgram_cats ) {
	$webgram_args['category__in'] = array_map( 'intval', $webgram_cats );
}
$webgram_query = new WP_Query( (array) apply_filters( 'webgram/blog/related_args', $webgram_args ) );
if ( ! $webgram_query->have_posts() ) {
	return;
}
?>
<section class="wg-related-posts" aria-label="<?php esc_attr_e( 'Related posts', 'webgram' ); ?>">
	<h2 class="wg-section-title"><span><?php esc_html_e( 'You may also like', 'webgram' ); ?></span></h2>
	<div class="wg-posts wg-posts--grid wg-posts--cols-<?php echo (int) $webgram_count; ?>">
		<?php
		while ( $webgram_query->have_posts() ) :
			$webgram_query->the_post();
			get_template_part( 'template-parts/content/content' );
		endwhile;
		wp_reset_postdata();
		?>
	</div>
</section>
