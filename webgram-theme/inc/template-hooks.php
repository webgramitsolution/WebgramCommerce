<?php
/**
 * Central place for add_action calls that position theme template parts.
 * WooCommerce hook repositioning lives in inc/woocommerce/ files; this file covers the theme's own hooks.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header
 */
add_action( 'webgram/header', 'webgram_render_header_topbar', 10 );
add_action( 'webgram/header', 'webgram_render_header_main', 20 );
add_action( 'webgram/header', 'webgram_render_header_secondary', 30 );

function webgram_render_header_topbar(): void {
	if ( webgram_option( 'topbar_enabled' ) ) {
		webgram_part( 'header/topbar' );
	}
}

function webgram_render_header_main(): void {
	webgram_part( 'header/main' );
}

function webgram_render_header_secondary(): void {
	if ( has_nav_menu( 'secondary' ) && webgram_option( 'secondary_bar_enabled' ) ) {
		webgram_part( 'header/secondary' );
	}
}

/**
 * Footer
 */
add_action( 'webgram/footer', 'webgram_render_footer_widgets', 10 );
add_action( 'webgram/footer', 'webgram_render_footer_bottom', 20 );

function webgram_render_footer_widgets(): void {
	webgram_part( 'footer/widgets' );
}

function webgram_render_footer_bottom(): void {
	webgram_part( 'footer/bottom' );
}

/**
 * Header height is written to a CSS variable by JS for sticky offsets; give it a server-side starting value.
 */
function webgram_head_inline_vars(): void {
	echo '<style id="wg-runtime-vars">:root{--wg-header-height:72px;--wg-topbar-height:0px}</style>' . "\n";
}
add_action( 'wp_head', 'webgram_head_inline_vars', 2 );
