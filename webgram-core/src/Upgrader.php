<?php
namespace Webgram\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs versioned, idempotent migrations when the stored DB version is older than the code version.
 * Migration classes live in src/Migrations/ and are named V{major}_{minor}_{patch}.
 */
final class Upgrader {

	public function __construct( private Plugin $plugin ) {}

	public function maybe_upgrade(): void {
		$installed = (string) get_option( 'webgram_core_db_version', '0' );

		if ( version_compare( $installed, WEBGRAM_CORE_DB_VERSION, '>=' ) ) {
			return;
		}

		foreach ( $this->migrations() as $version => $class ) {
			if ( version_compare( $installed, $version, '<' ) && class_exists( $class ) ) {
				try {
					( new $class( $this->plugin ) )->run();
					update_option( 'webgram_core_db_version', $version );
				} catch ( \Throwable $e ) {
					$this->plugin->logger()->error( 'Migration failed: ' . $version, [ 'error' => $e->getMessage() ] );
					return;
				}
			}
		}

		update_option( 'webgram_core_db_version', WEBGRAM_CORE_DB_VERSION );
		update_option( 'webgram_core_version', WEBGRAM_CORE_VERSION );
	}

	/** @return array<string, class-string> ordered by version */
	private function migrations(): array {
		$list = [];
		foreach ( glob( WEBGRAM_CORE_PATH . 'src/Migrations/V*.php' ) ?: [] as $file ) {
			$name             = basename( $file, '.php' );
			$version          = str_replace( '_', '.', substr( $name, 1 ) );
			$list[ $version ] = 'Webgram\\Core\\Migrations\\' . $name;
		}
		uksort( $list, 'version_compare' );
		return $list;
	}
}
