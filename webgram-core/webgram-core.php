<?php
/**
 * Plugin Name:       Webgram Core
 * Plugin URI:        https://webgramitsolution.com/webgram-core
 * Description:       Functionality layer for the Webgram WooCommerce ecosystem: reviews, reels, wishlist, compare, AI assistant, invoices, WhatsApp notifications, sliders and more. Works with any theme, designed for Webgram Theme.
 * Version:           0.8.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * WC requires at least: 8.5
 * Author:            Webgram IT Solution
 * Author URI:        https://webgramitsolution.com
 * License:           GPL-2.0-or-later
 * Text Domain:       webgram-core
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WEBGRAM_CORE_VERSION', '0.8.0' );
define( 'WEBGRAM_CORE_DB_VERSION', '0.8.0' );
define( 'WEBGRAM_CORE_FILE', __FILE__ );
define( 'WEBGRAM_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEBGRAM_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'WEBGRAM_CORE_MIN_THEME_VERSION', '0.1.0' );

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Webgram Core requires PHP 8.1 or newer. The plugin has not been loaded.', 'webgram-core' ) . '</p></div>';
		}
	);
	return;
}

require_once WEBGRAM_CORE_PATH . 'src/Autoloader.php';
Webgram\Core\Autoloader::register();

/**
 * Global accessor. The only global function the plugin defines.
 */
function webgram_core(): Webgram\Core\Plugin {
	return Webgram\Core\Plugin::instance();
}

register_activation_hook( __FILE__, [ Webgram\Core\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Webgram\Core\Deactivator::class, 'deactivate' ] );

add_action( 'plugins_loaded', static fn() => webgram_core()->boot(), 5 );
