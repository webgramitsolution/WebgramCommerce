<?php
/**
 * Runs only when the plugin is deleted from the Plugins screen.
 * Data is removed only when the store owner has opted in via Settings > Advanced.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$webgram_core_settings = get_option( 'webgram_core_settings_general', [] );

if ( empty( $webgram_core_settings['remove_data_on_uninstall'] ) ) {
	return;
}

require_once __DIR__ . '/src/Autoloader.php';
Webgram\Core\Autoloader::register();

Webgram\Core\Plugin::instance()->uninstall();
