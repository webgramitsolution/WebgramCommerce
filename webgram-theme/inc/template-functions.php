<?php
/**
 * Small helpers used by templates. Anything with business logic belongs in Webgram Core.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a Customizer option with its default and a per-key filter.
 */
function webgram_option( string $key, mixed $default = null ): mixed {
	$defaults = webgram_defaults();
	$fallback = $default ?? ( $defaults[ $key ] ?? null );
	$value    = get_theme_mod( $key, $fallback );
	return apply_filters( 'webgram/setting/' . $key, $value, $fallback );
}

/** Whether the current request is a WooCommerce page in the broad sense (shop, product, cart, checkout, account). */
function webgram_is_woo_page(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/** Page layout id for the current view: container, full-width, sidebar-left, sidebar-right. */
function webgram_layout(): string {
	$layout = '';

	if ( is_singular() ) {
		$layout = (string) get_post_meta( get_the_ID(), '_webgram_layout', true );
	}

	if ( '' === $layout || 'default' === $layout ) {
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			$layout = (string) webgram_option( 'shop_layout' );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$layout = 'container';
		} elseif ( is_home() || is_archive() || is_search() || is_single() ) {
			$layout = (string) webgram_option( 'blog_layout' );
		} else {
			$layout = 'container';
		}
	}

	return (string) apply_filters( 'webgram/layout', $layout );
}

/** Classes for the main content wrapper based on layout. */
function webgram_content_classes(): string {
	$layout  = webgram_layout();
	$classes = [ 'wg-content' ];
	if ( 'full-width' === $layout ) {
		$classes[] = 'wg-content--full';
	} elseif ( in_array( $layout, [ 'sidebar-left', 'sidebar-right' ], true ) ) {
		$classes[] = 'wg-content--with-sidebar';
		$classes[] = 'wg-content--' . $layout;
	}
	return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/** Inline SVG icon from assets/images/icons/{name}.svg, cached per request. Output is escaped through wp_kses. */
function webgram_icon( string $name, string $class = '', bool $echo = true ): string {
	static $cache = [];
	$name = sanitize_file_name( $name );

	if ( ! isset( $cache[ $name ] ) ) {
		$file           = WEBGRAM_DIR . '/assets/images/icons/' . $name . '.svg';
		$cache[ $name ] = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	$svg = $cache[ $name ];
	if ( '' === $svg ) {
		return '';
	}

	$svg = preg_replace( '/<svg\b/', '<svg class="wg-icon wg-icon--' . esc_attr( $name ) . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-hidden="true" focusable="false"', $svg, 1 );
	$svg = wp_kses( $svg, webgram_svg_kses() );

	if ( $echo ) {
		echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by wp_kses above.
	}
	return $svg;
}

function webgram_svg_kses(): array {
	$attrs = [ 'class' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'focusable' => true, 'xmlns' => true, 'd' => true, 'cx' => true, 'cy' => true, 'r' => true, 'x' => true, 'y' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'points' => true, 'rx' => true, 'ry' => true, 'transform' => true ];
	return [ 'svg' => $attrs, 'path' => $attrs, 'circle' => $attrs, 'rect' => $attrs, 'line' => $attrs, 'polyline' => $attrs, 'polygon' => $attrs, 'g' => $attrs ];
}

/** Render a template part with an args array. Thin wrapper so call sites read consistently. */
function webgram_part( string $slug, array $args = [] ): void {
	get_template_part( 'template-parts/' . $slug, null, $args );
}

/** Body classes for layout hooks. */
function webgram_body_classes( array $classes ): array {
	$classes[] = 'wg-layout-' . sanitize_html_class( webgram_layout() );
	$classes[] = webgram_has_core() ? 'wg-has-core' : 'wg-no-core';
	if ( webgram_option( 'header_sticky' ) ) {
		$classes[] = 'wg-header-sticky';
	}
	return $classes;
}
add_filter( 'body_class', 'webgram_body_classes' );
