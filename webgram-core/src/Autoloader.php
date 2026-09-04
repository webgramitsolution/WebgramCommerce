<?php
namespace Webgram\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader for the Webgram\Core namespace.
 * If a Composer autoloader ships in vendor/ (for PDF libraries), it is loaded too.
 */
final class Autoloader {

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );

		$composer = WEBGRAM_CORE_PATH . 'vendor/autoload.php';
		if ( is_readable( $composer ) ) {
			require_once $composer;
		}
	}

	public static function load( string $class ): void {
		$prefix = 'Webgram\\Core\\';

		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = WEBGRAM_CORE_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
