<?php
/**
 * Theme supports, image sizes, nav menus, editor palette.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

function webgram_setup(): void {
	load_theme_textdomain( 'webgram', WEBGRAM_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-logo', [ 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ] );

	// Declares to Webgram Core that this theme styles Core components and provides template overrides.
	add_theme_support(
		'webgram-core',
		[
			'styles'         => true,
			'templates'      => true,
			'header_icons'   => [ 'wishlist', 'compare', 'cart' ],
			'admin_menu'     => 'webgram',
			'settings_panel' => true,
		]
	);

	register_nav_menus(
		[
			'primary'   => __( 'Primary menu', 'webgram' ),
			'secondary' => __( 'Secondary bar (below header)', 'webgram' ),
			'mobile'    => __( 'Mobile menu', 'webgram' ),
			'footer'    => __( 'Footer menu', 'webgram' ),
		]
	);

	add_image_size( 'webgram-card', 600, 600, true );
	add_image_size( 'webgram-card-tall', 600, 800, true );
	add_image_size( 'webgram-thumb', 120, 120, true );

	// Editor palette mirrors the design tokens so Gutenberg content stays on-brand.
	$palette = [];
	foreach ( webgram_token_colors() as $slug => $label ) {
		$palette[] = [
			'name'  => $label,
			'slug'  => 'wg-' . $slug,
			'color' => (string) webgram_option( 'color_' . $slug ),
		];
	}
	add_theme_support( 'editor-color-palette', $palette );
	add_theme_support( 'disable-custom-gradients' );

	$GLOBALS['content_width'] = 1320;
}
add_action( 'after_setup_theme', 'webgram_setup' );

/** Token colors exposed to the editor palette. slug => label. */
function webgram_token_colors(): array {
	return [
		'primary'   => __( 'Primary', 'webgram' ),
		'secondary' => __( 'Secondary', 'webgram' ),
		'accent'    => __( 'Accent', 'webgram' ),
		'text'      => __( 'Text', 'webgram' ),
		'heading'   => __( 'Heading', 'webgram' ),
		'bg'        => __( 'Background', 'webgram' ),
		'bg_alt'    => __( 'Alternate background', 'webgram' ),
	];
}

function webgram_widgets_init(): void {
	register_sidebar(
		[
			'name'          => __( 'Blog sidebar', 'webgram' ),
			'id'            => 'sidebar-blog',
			'before_widget' => '<div id="%1$s" class="wg-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="wg-widget__title">',
			'after_title'   => '</h3>',
		]
	);
	register_sidebar(
		[
			'name'          => __( 'Shop sidebar', 'webgram' ),
			'id'            => 'sidebar-shop',
			'before_widget' => '<div id="%1$s" class="wg-widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="wg-widget__title">',
			'after_title'   => '</h3>',
		]
	);
	$columns = max( 1, min( 6, (int) webgram_option( 'footer_columns' ) ) );
	for ( $i = 1; $i <= $columns; $i++ ) {
		register_sidebar(
			[
				/* translators: %d: column number */
				'name'          => sprintf( __( 'Footer column %d', 'webgram' ), $i ),
				'id'            => 'footer-' . $i,
				'before_widget' => '<div id="%1$s" class="wg-widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="wg-widget__title">',
				'after_title'   => '</h3>',
			]
		);
	}
}
add_action( 'widgets_init', 'webgram_widgets_init' );
