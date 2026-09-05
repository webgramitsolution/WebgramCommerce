<?php
/**
 * Small helpers used by templates. Anything with business logic belongs in Webgram Core.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a Theme Settings value (option webgram_theme_settings) with its default and a per-key filter.
 */
function webgram_option( string $key, mixed $default = null ): mixed {
	$fallback = $default ?? ( webgram_defaults()[ $key ] ?? null );
	$value    = Webgram_Settings::instance()->get( $key, $fallback );
	return apply_filters( 'webgram/setting/' . $key, $value, $fallback );
}

/** Responsive value helper: returns the value for a device from a dimensions array or a scalar. */
function webgram_option_device( string $key, string $device = 'desktop' ): int|float {
	$value = webgram_option( $key );
	if ( is_array( $value ) ) {
		$value = $value[ $device ] ?? ( $value['desktop'] ?? 0 );
	}
	return is_numeric( $value ) ? $value + 0 : 0;
}

/** Whether the current request is a WooCommerce page in the broad sense (shop, product, cart, checkout, account). */
function webgram_is_woo_page(): bool {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/** Context key for the Page title tab visibility list: page, post, blog, shop, search. */
function webgram_page_title_type(): string {
	if ( is_search() ) {
		return 'search';
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return 'shop';
	}
	if ( is_singular( 'post' ) ) {
		return 'post';
	}
	if ( is_page() ) {
		return 'page';
	}
	return 'blog';
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
		} elseif ( is_single() && ! webgram_option( 'blog_sidebar_single' ) ) {
			$layout = 'container';
		} elseif ( is_home() || is_archive() || is_search() || is_single() ) {
			$layout = (string) webgram_option( 'blog_layout' );
		} elseif ( is_page() ) {
			$layout = (string) webgram_option( 'page_layout' );
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
	$classes[] = 'wg-site-' . sanitize_html_class( (string) webgram_option( 'site_layout' ) );
	$classes[] = 'wg-buttons-' . sanitize_html_class( (string) webgram_option( 'button_style' ) );
	$classes[] = 'wg-forms-' . sanitize_html_class( (string) webgram_option( 'form_style' ) );
	$classes[] = 'wg-cards-' . sanitize_html_class( (string) webgram_option( 'card_style' ) );
	$classes[] = 'wg-badges-' . sanitize_html_class( (string) webgram_option( 'badge_style' ) );
	if ( webgram_option( 'sticky_enabled' ) ) {
		$classes[] = 'wg-header-sticky';
	}
	if ( webgram_option( 'button_shine' ) ) {
		$classes[] = 'wg-btn-shine';
	}
	if ( webgram_option( 'mobile_nav_enabled' ) ) {
		$classes[] = 'wg-has-bottom-nav';
	}
	return $classes;
}

/** Payment method icons available to footer and product payment strips. slug => label. */
function webgram_payment_icon_choices(): array {
	return (array) apply_filters(
		'webgram/payment_icons',
		[
			'visa'       => 'Visa',
			'mastercard' => 'Mastercard',
			'amex'       => 'American Express',
			'rupay'      => 'RuPay',
			'upi'        => 'UPI',
			'gpay'       => 'Google Pay',
			'phonepe'    => 'PhonePe',
			'paytm'      => 'Paytm',
			'amazonpay'  => 'Amazon Pay',
			'netbanking' => __( 'Net banking', 'webgram' ),
			'paypal'     => 'PayPal',
			'cod'        => __( 'Cash on delivery', 'webgram' ),
		]
	);
}

/** Inline SVG for a payment icon from assets/images/payments/{slug}.svg. */
function webgram_payment_icon( string $slug, bool $echo = true ): string {
	$slug = sanitize_file_name( $slug );
	$file = WEBGRAM_DIR . '/assets/images/payments/' . $slug . '.svg';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	$labels = webgram_payment_icon_choices();
	$svg    = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$svg    = preg_replace( '/<svg\b/', '<svg class="wg-payment-icon wg-payment-icon--' . esc_attr( $slug ) . '" role="img" aria-label="' . esc_attr( (string) ( $labels[ $slug ] ?? $slug ) ) . '"', $svg, 1 );
	$kses   = webgram_svg_kses();
	foreach ( $kses as &$attrs ) {
		$attrs += [ 'role' => true, 'aria-label' => true, 'font-family' => true, 'font-size' => true, 'font-weight' => true, 'text-anchor' => true, 'fill-rule' => true, 'clip-rule' => true, 'opacity' => true ];
	}
	unset( $attrs );
	$kses['text']  = $kses['svg'];
	$kses['title'] = [];
	$svg           = wp_kses( $svg, $kses );
	if ( $echo ) {
		echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by wp_kses above.
	}
	return $svg;
}

/** Social networks with brand colors. slug => [label, color, icon]. */
function webgram_social_networks(): array {
	return (array) apply_filters(
		'webgram/social_networks',
		[
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'whatsapp'  => 'WhatsApp',
			'youtube'   => 'YouTube',
			'x'         => 'X',
			'linkedin'  => 'LinkedIn',
			'pinterest' => 'Pinterest',
			'telegram'  => 'Telegram',
			'threads'   => 'Threads',
			'tiktok'    => 'TikTok',
		]
	);
}

/** Brand color for a social network (used by the floating sidebar). */
function webgram_social_color( string $network ): string {
	$colors = [
		'facebook'  => '#1877f2',
		'instagram' => 'linear-gradient(45deg,#f9ce34,#ee2a7b,#6228d7)',
		'whatsapp'  => '#25d366',
		'youtube'   => '#ff0000',
		'x'         => '#000000',
		'linkedin'  => '#0a66c2',
		'pinterest' => '#e60023',
		'telegram'  => '#26a5e4',
		'threads'   => '#000000',
		'tiktok'    => '#000000',
	];
	return (string) apply_filters( 'webgram/social_color', $colors[ $network ] ?? 'var(--wg-color-primary)', $network );
}

/** Text with {year} and {site} placeholders replaced. */
function webgram_replace_placeholders( string $text ): string {
	return str_replace( [ '{year}', '{site}' ], [ gmdate( 'Y' ), get_bloginfo( 'name' ) ], $text );
}

/**
 * Id of a Core Layout assigned to the given template type, or 0. Core answers through the filter; the theme never
 * calls Core classes.
 */
function webgram_layout_id( string $type ): int {
	return (int) apply_filters( 'webgram/layout_for', 0, $type );
}

/** Render a Core Layout or HTML Block by id through the Core-provided filter. */
function webgram_render_block( int $id ): void {
	if ( $id > 0 ) {
		echo apply_filters( 'webgram/html_block', '', $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core returns escaped builder output.
	}
}
add_filter( 'body_class', 'webgram_body_classes' );
