<?php
/**
 * Central place for add_action calls that position theme template parts.
 * WooCommerce hook repositioning lives in inc/woocommerce/ files; this file covers the theme's own hooks.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/**
 * Header: builder output, or a Core Layout of type "header" when one is assigned.
 */
add_action( 'webgram/header', 'webgram_render_header', 10 );
function webgram_render_header(): void {
	$layout_id = webgram_layout_id( 'header' );
	if ( $layout_id ) {
		webgram_render_block( $layout_id );
		return;
	}
	Webgram_Builder_Renderer::render_header();
}

add_action( 'webgram/before_header', 'webgram_render_header_banner_above' );
add_action( 'webgram/after_header', 'webgram_render_header_banner_below' );
function webgram_render_header_banner_above(): void {
	if ( webgram_option( 'header_banner_enabled' ) && 'above' === webgram_option( 'header_banner_position' ) ) {
		webgram_part( 'header/banner' );
	}
}
function webgram_render_header_banner_below(): void {
	if ( webgram_option( 'header_banner_enabled' ) && 'below' === webgram_option( 'header_banner_position' ) ) {
		webgram_part( 'header/banner' );
	}
}

/**
 * Footer: builder output, or a Core Layout of type "footer".
 */
add_action( 'webgram/footer', 'webgram_render_footer', 10 );
function webgram_render_footer(): void {
	$layout_id = webgram_layout_id( 'footer' );
	if ( $layout_id ) {
		webgram_render_block( $layout_id );
		return;
	}
	Webgram_Builder_Renderer::render_footer();
}

/**
 * Off-canvas and floating UI printed once after the page wrapper.
 */
add_action( 'webgram/after_page', 'webgram_render_mobile_drawer', 10 );
add_action( 'webgram/after_page', 'webgram_render_search_overlay', 20 );
add_action( 'webgram/after_page', 'webgram_render_bottom_nav', 30 );
add_action( 'webgram/after_page', 'webgram_render_back_to_top', 40 );
add_action( 'webgram/after_page', 'webgram_render_social_sidebar', 50 );
add_action( 'webgram/after_page', 'webgram_render_overlay', 90 );

function webgram_render_mobile_drawer(): void {
	webgram_part( 'header/mobile-drawer' );
}

function webgram_render_search_overlay(): void {
	$needed = Webgram_Header_Builder::instance()->has_element( 'search_toggle' );
	if ( ! $needed && webgram_option( 'mobile_nav_enabled' ) ) {
		foreach ( (array) webgram_option( 'mobile_nav_items' ) as $item ) {
			if ( 'search' === ( $item['action'] ?? '' ) ) {
				$needed = true;
				break;
			}
		}
	}
	if ( apply_filters( 'webgram/search_overlay', $needed ) ) {
		webgram_part( 'modals/search-overlay' );
	}
}

function webgram_render_bottom_nav(): void {
	if ( webgram_option( 'mobile_nav_enabled' ) ) {
		webgram_part( 'header/bottom-nav' );
	}
}

function webgram_render_back_to_top(): void {
	if ( webgram_option( 'back_to_top' ) ) {
		webgram_part( 'misc/back-to-top' );
	}
}

function webgram_render_social_sidebar(): void {
	if ( webgram_option( 'social_sidebar' ) ) {
		webgram_part( 'misc/social-sidebar' );
	}
}

function webgram_render_overlay(): void {
	echo '<div class="wg-overlay" data-wg-close="drawer" hidden></div>';
}

add_action( 'wp_body_open', 'webgram_render_preloader', 1 );
function webgram_render_preloader(): void {
	if ( webgram_option( 'preloader' ) ) {
		webgram_part( 'misc/preloader' );
	}
}

/**
 * Header height is written to a CSS variable by JS for sticky offsets; give it a server-side starting value.
 */
function webgram_head_inline_vars(): void {
	$layout = Webgram_Header_Builder::instance()->layout();
	$main   = (int) ( $layout['desktop']['main']['settings']['height'] ?? 72 );
	$top    = ! empty( $layout['desktop']['top']['settings']['enabled'] ) ? (int) ( $layout['desktop']['top']['settings']['height'] ?? 36 ) : 0;
	printf( '<style id="wg-runtime-vars">:root{--wg-header-height:%dpx;--wg-topbar-height:%dpx;--wg-sticky-offset:0px}</style>' . "\n", $main, $top );
}
add_action( 'wp_head', 'webgram_head_inline_vars', 2 );

/**
 * Performance toggles.
 */
add_action( 'init', 'webgram_perf_tweaks' );
function webgram_perf_tweaks(): void {
	if ( webgram_option( 'perf_disable_emojis' ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
	if ( webgram_option( 'perf_disable_embeds' ) ) {
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	}
}

/** Optional CDN prefix for theme asset URLs. */
add_filter( 'template_directory_uri', 'webgram_cdn_prefix', 20 );
add_filter( 'stylesheet_directory_uri', 'webgram_cdn_prefix', 20 );
function webgram_cdn_prefix( string $uri ): string {
	if ( is_admin() ) {
		return $uri;
	}
	$cdn = (string) webgram_option( 'perf_cdn_prefix' );
	if ( '' === $cdn ) {
		return $uri;
	}
	return str_replace( untrailingslashit( content_url() ), untrailingslashit( $cdn ) . '/' . basename( WP_CONTENT_DIR ), $uri );
}

/** RTL force switch (preview aid). */
add_filter( 'language_attributes', 'webgram_force_rtl_attribute' );
function webgram_force_rtl_attribute( string $output ): string {
	if ( webgram_option( 'rtl_force' ) && ! str_contains( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}
	return $output;
}

/** Without Core, the Bulk Order template falls back to the Social profiles WhatsApp link. */
add_filter( 'webgram/bulk_order/whatsapp_url', 'webgram_bulk_order_whatsapp_fallback', 5 );
function webgram_bulk_order_whatsapp_fallback( string $url ): string {
	if ( '' !== $url ) {
		return $url;
	}
	foreach ( (array) webgram_option( 'social_links' ) as $link ) {
		if ( 'whatsapp' === ( $link['network'] ?? '' ) && ! empty( $link['url'] ) ) {
			return (string) $link['url'];
		}
	}
	return '';
}
