<?php
/**
 * Page rendered by a Webgram Core Layout (Webgram_Layouts). The layout content replaces the page body;
 * the header, footer, page title band and WooCommerce wrapper hooks stay in place so plugins still attach.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_current = Webgram_Layouts::current();
get_header( 'shop' === $webgram_current['type'] ? 'shop' : null );

$webgram_title = '';
if ( is_singular() ) {
	$webgram_title = get_the_title();
} elseif ( is_search() ) {
	$webgram_title = get_search_query();
} elseif ( is_archive() ) {
	$webgram_title = get_the_archive_title();
} elseif ( is_home() ) {
	$webgram_title = single_post_title( '', false );
}
webgram_part( 'misc/page-title', [ 'title' => $webgram_title, 'description' => is_archive() ? get_the_archive_description() : '' ] );

$webgram_woo = in_array( $webgram_current['type'], [ 'shop', 'cart', 'checkout', 'thankyou', 'myaccount' ], true );
if ( $webgram_woo ) {
	do_action( 'woocommerce_before_main_content' );
}
if ( is_singular() ) {
	the_post();
}
?>
<div class="wg-layout wg-layout--<?php echo esc_attr( $webgram_current['type'] ); ?>">
	<?php do_action( 'webgram/layout/before', $webgram_current['type'], $webgram_current['id'] ); ?>
	<?php webgram_render_block( $webgram_current['id'] ); ?>
	<?php do_action( 'webgram/layout/after', $webgram_current['type'], $webgram_current['id'] ); ?>
</div>
<?php
if ( $webgram_woo ) {
	do_action( 'woocommerce_after_main_content' );
}
get_footer();
