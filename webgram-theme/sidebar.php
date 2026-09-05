<?php
/**
 * Sidebar for blog, pages and products. Rendered only for sidebar layouts; the area comes from
 * Webgram > Sidebars assignments or the per post override. On mobile it stacks or opens off-canvas.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

if ( ! in_array( webgram_layout(), [ 'sidebar-left', 'sidebar-right' ], true ) ) {
	return;
}
$webgram_context = 'blog';
if ( function_exists( 'is_product' ) && is_product() ) {
	$webgram_context = 'product';
} elseif ( is_page() ) {
	$webgram_context = 'page';
}
$webgram_sidebar = Webgram_Sidebars::for_context( $webgram_context );
if ( ! is_active_sidebar( $webgram_sidebar ) ) {
	return;
}
$webgram_offcanvas = 'offcanvas' === webgram_option( 'sidebar_mobile' );
?>
<?php if ( $webgram_offcanvas ) : ?>
	<button type="button" class="wg-btn wg-btn--outline wg-sidebar-toggle" data-wg-toggle="sidebar" aria-controls="secondary" aria-expanded="false"><?php webgram_icon( 'sliders' ); ?><?php esc_html_e( 'Show sidebar', 'webgram' ); ?></button>
<?php endif; ?>
<aside id="secondary" class="wg-sidebar wg-sidebar--<?php echo esc_attr( $webgram_context ); ?><?php echo $webgram_offcanvas ? ' wg-sidebar--offcanvas wg-drawer wg-drawer--left' : ''; ?>"<?php echo $webgram_offcanvas ? ' data-wg-component="drawer" data-wg-drawer="sidebar" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Sidebar', 'webgram' ) . '"' : ''; ?><?php echo webgram_option( 'sidebar_sticky' ) ? ' data-sticky="1"' : ''; ?>>
	<?php if ( $webgram_offcanvas ) : ?>
		<div class="wg-drawer__head wg-sidebar__head"><span class="wg-drawer__title"><?php esc_html_e( 'Sidebar', 'webgram' ); ?></span><button class="wg-icon-btn wg-icon-btn--no-label" type="button" data-wg-close="drawer"><?php webgram_icon( 'close' ); ?><span class="wg-sr-only"><?php esc_html_e( 'Close', 'webgram' ); ?></span></button></div>
	<?php endif; ?>
	<div class="wg-sidebar__body">
		<?php dynamic_sidebar( $webgram_sidebar ); ?>
	</div>
</aside>
