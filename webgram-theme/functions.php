<?php
/**
 * Webgram Theme bootstrap. Loads inc/ files only; contains no logic itself.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

define( 'WEBGRAM_VERSION', '0.1.0' );
define( 'WEBGRAM_DIR', get_template_directory() );
define( 'WEBGRAM_URI', get_template_directory_uri() );
define( 'WEBGRAM_MIN_CORE_VERSION', '0.1.0' );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Webgram requires PHP 8.1 or newer. Please ask your host to upgrade PHP.', 'webgram' ) . '</p></div>';
		}
	);
	return;
}

$webgram_includes = [
	'inc/setup.php',
	'inc/core-bridge.php',
	'inc/template-functions.php',
	'inc/template-hooks.php',
	'inc/enqueue.php',
	'inc/customizer/defaults.php',
	'inc/customizer/class-customizer.php',
	'inc/customizer/output/class-css-generator.php',
	'inc/woocommerce/class-wc-setup.php',
	'inc/woocommerce/class-wc-product-card.php',
	'inc/admin/class-theme-dashboard.php',
];

foreach ( $webgram_includes as $webgram_file ) {
	require_once WEBGRAM_DIR . '/' . $webgram_file;
}
unset( $webgram_includes, $webgram_file );
