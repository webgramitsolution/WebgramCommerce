<?php
namespace Webgram\Core;

defined( 'ABSPATH' ) || exit;

final class Activator {

	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( plugin_basename( WEBGRAM_CORE_FILE ) );
			wp_die( esc_html__( 'Webgram Core requires PHP 8.1 or newer.', 'webgram-core' ) );
		}

		add_option( 'webgram_core_version', WEBGRAM_CORE_VERSION );
		add_option( 'webgram_core_db_version', '0' );
		add_option( 'webgram_core_activated_at', time() );

		// Module tables and defaults are created by each module's activate() via the manager.
		Plugin::instance()->modules()->activate_all();

		update_option( 'webgram_core_db_version', WEBGRAM_CORE_DB_VERSION );
		set_transient( 'webgram_core_just_activated', 1, 60 );
		flush_rewrite_rules();
	}
}
