<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Logs through WooCommerce's logger when available (WooCommerce > Status > Logs, source "webgram-core"),
 * otherwise through error_log when WP_DEBUG_LOG is on. Secrets are redacted before writing.
 */
final class Logger {

	private const SOURCE = 'webgram-core';

	public function error( string $message, array $context = [] ): void {
		$this->log( 'error', $message, $context );
	}

	public function warning( string $message, array $context = [] ): void {
		$this->log( 'warning', $message, $context );
	}

	public function info( string $message, array $context = [] ): void {
		$this->log( 'info', $message, $context );
	}

	public function debug( string $message, array $context = [] ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->log( 'debug', $message, $context );
		}
	}

	private function log( string $level, string $message, array $context ): void {
		$context = $this->redact( $context );
		$line    = $message . ( $context ? ' ' . wp_json_encode( $context ) : '' );

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, $line, [ 'source' => self::SOURCE ] );
			return;
		}

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[%s] %s: %s', self::SOURCE, strtoupper( $level ), $line ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	private function redact( array $context ): array {
		$sensitive = [ 'token', 'access_token', 'api_key', 'secret', 'password', 'authorization', 'key' ];
		foreach ( $context as $k => $v ) {
			if ( is_array( $v ) ) {
				$context[ $k ] = $this->redact( $v );
			} elseif ( is_string( $k ) && in_array( strtolower( $k ), $sensitive, true ) ) {
				$context[ $k ] = '[redacted]';
			}
		}
		return $context;
	}
}
