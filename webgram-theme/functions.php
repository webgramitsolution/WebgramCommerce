<?php
/**
 * Webgram Theme bootstrap. Loads inc/ files only; contains no logic itself.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

define( 'WEBGRAM_VERSION', '1.0.0' );
define( 'WEBGRAM_DIR', get_template_directory() );
define( 'WEBGRAM_URI', get_template_directory_uri() );
define( 'WEBGRAM_MIN_CORE_VERSION', '1.0.0' );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	// Safe mode: stub functions plus a loud message, so the site renders instead of throwing a critical error.
	require_once WEBGRAM_DIR . '/inc/compat/requirements.php';
	return;
}

$webgram_includes = [
	'inc/setup.php',
	'inc/core-bridge.php',
	'inc/template-functions.php',
	'inc/template-hooks.php',
	'inc/enqueue.php',
	'inc/settings/defaults.php',
	'inc/settings/class-settings.php',
	'inc/settings/class-settings-sanitizer.php',
	'inc/settings/class-settings-fields.php',
	'inc/settings/class-css-generator.php',
	'inc/settings/class-settings-page.php',
	'inc/settings/class-import-export.php',
	'inc/settings/class-settings-migration.php',
	'inc/customizer/class-customizer.php',
	'inc/builders/class-element.php',
	'inc/builders/presets.php',
	'inc/builders/class-header-builder.php',
	'inc/builders/class-footer-builder.php',
	'inc/builders/class-builder-renderer.php',
	'inc/builders/class-builder-page.php',
	'inc/mega-menu/class-mega-menu-admin.php',
	'inc/mega-menu/class-mega-menu-walker.php',
	'inc/mega-menu/class-mega-menu-frontend.php',
	'inc/mega-menu/class-mobile-nav-walker.php',
	'inc/woocommerce/class-wc-setup.php',
	'inc/woocommerce/class-wc-product-card.php',
	'inc/woocommerce/class-wc-ajax.php',
	'inc/woocommerce/class-wc-shop.php',
	'inc/woocommerce/class-wc-product.php',
	'inc/woocommerce/class-wc-cart.php',
	'inc/woocommerce/class-wc-checkout.php',
	'inc/woocommerce/class-wc-account.php',
	'inc/sections/class-sections.php',
	'inc/elementor/class-elementor-sync.php',
	'inc/admin/class-theme-dashboard.php',
	'inc/admin/class-plugin-installer.php',
	'inc/admin/class-setup-wizard.php',
	'inc/admin/class-demo-importer.php',
	'inc/admin/class-page-metabox.php',
	'inc/admin/class-sidebars.php',
	'inc/class-layouts.php',
];

foreach ( $webgram_includes as $webgram_file ) {
	require_once WEBGRAM_DIR . '/' . $webgram_file;
}
unset( $webgram_includes, $webgram_file );
