<?php
/**
 * Asset registration and conditional loading. A page loads only the bundles it renders.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

function webgram_asset_version( string $relative ): string {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$file = WEBGRAM_DIR . '/assets/' . $relative;
		return is_readable( $file ) ? (string) filemtime( $file ) : WEBGRAM_VERSION;
	}
	return WEBGRAM_VERSION;
}

function webgram_enqueue_assets(): void {
	$rtl = is_rtl() ? '-rtl' : '';

	// Fonts: self-hosted by default; Google Fonts only when the store owner opts in.
	if ( 'google' === webgram_option( 'font_source' ) ) {
		$families = array_filter( array_unique( [ webgram_option( 'font_body' ), webgram_option( 'font_heading' ) ] ) );
		if ( $families ) {
			$query = implode( '&', array_map( static fn( $f ) => 'family=' . rawurlencode( (string) $f ) . ':wght@400;500;600;700', $families ) );
			wp_enqueue_style( 'webgram-fonts', 'https://fonts.googleapis.com/css2?' . $query . '&display=swap', [], null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}
	} else {
		wp_enqueue_style( 'webgram-fonts-local', WEBGRAM_URI . '/assets/fonts/fonts.css', [], webgram_asset_version( 'fonts/fonts.css' ) );
	}

	wp_enqueue_style( 'webgram-main', WEBGRAM_URI . '/assets/css/main' . $rtl . '.css', [], webgram_asset_version( 'css/main.css' ) );

	if ( webgram_is_woo_page() ) {
		wp_enqueue_style( 'webgram-woocommerce', WEBGRAM_URI . '/assets/css/woocommerce' . $rtl . '.css', [ 'webgram-main' ], webgram_asset_version( 'css/woocommerce.css' ) );
	}

	wp_enqueue_script( 'webgram-main', WEBGRAM_URI . '/assets/js/main.js', [], webgram_asset_version( 'js/main.js' ), [ 'in_footer' => true, 'strategy' => 'defer' ] );

	wp_localize_script(
		'webgram-main',
		'webgramData',
		(array) apply_filters(
			'webgram/frontend_data',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'wcAjax'     => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
				'nonce'      => wp_create_nonce( 'webgram_nonce' ),
				'sticky'     => [
					'enabled'      => (bool) webgram_option( 'sticky_enabled' ),
					'shrink'       => (bool) webgram_option( 'sticky_shrink' ),
					'shadow'       => (bool) webgram_option( 'sticky_shadow' ),
					'hideOnScroll' => (bool) webgram_option( 'sticky_hide_on_scroll' ),
					'mobile'       => (bool) webgram_option( 'sticky_mobile' ),
				],
				'search'     => [
					'live'     => (bool) webgram_option( 'search_live' ),
					'minChars' => (int) webgram_option( 'search_min_chars' ),
				],
				'i18n'       => [
					'menu'       => __( 'Menu', 'webgram' ),
					'close'      => __( 'Close', 'webgram' ),
					'noResults'  => __( 'No results found', 'webgram' ),
					'viewAll'    => __( 'View all results', 'webgram' ),
					'products'   => __( 'Products', 'webgram' ),
					'categories' => __( 'Categories', 'webgram' ),
					'posts'      => __( 'Articles', 'webgram' ),
					'searching'  => __( 'Searching', 'webgram' ),
				],
			]
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Print generated token CSS after the main stylesheet so it overrides compiled fallbacks.
	wp_add_inline_style( 'webgram-main', Webgram_CSS_Generator::instance()->get_css() );
}

/** Critical header CSS inline (optional): the compiled header rules from assets/css/critical.css. */
function webgram_critical_css(): void {
	if ( ! webgram_option( 'perf_critical_header' ) ) {
		return;
	}
	$file = WEBGRAM_DIR . '/assets/css/critical.css';
	if ( is_readable( $file ) ) {
		echo '<style id="wg-critical">' . wp_strip_all_tags( (string) file_get_contents( $file ) ) . '</style>' . "\n"; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'webgram_critical_css', 3 );
add_action( 'wp_enqueue_scripts', 'webgram_enqueue_assets' );

/** Remove WooCommerce's default stylesheets; the theme provides complete WooCommerce styling. */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/** Editor styles so block content previews with the site's tokens. */
function webgram_editor_assets(): void {
	add_editor_style( 'assets/css/main.css' );
}
add_action( 'admin_init', 'webgram_editor_assets' );

/** Preload the two primary self-hosted font files. */
function webgram_preload_fonts(): void {
	if ( 'google' === webgram_option( 'font_source' ) || ! webgram_option( 'perf_font_preload' ) ) {
		return;
	}
	foreach ( [ 'inter-latin-400.woff2', 'manrope-latin-700.woff2' ] as $file ) {
		if ( is_readable( WEBGRAM_DIR . '/assets/fonts/' . $file ) ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( WEBGRAM_URI . '/assets/fonts/' . $file ) );
		}
	}
}
add_action( 'wp_head', 'webgram_preload_fonts', 1 );
