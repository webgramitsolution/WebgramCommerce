<?php
namespace Webgram\Core;

defined( 'ABSPATH' ) || exit;

final class Deactivator {

	public static function deactivate(): void {
		// Clear scheduled events owned by Core. Data is never removed on deactivation.
		$hooks = (array) apply_filters( 'webgram_core/cron_hooks', [ 'webgram_core_daily_maintenance' ] );
		foreach ( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
		flush_rewrite_rules();
	}
}
