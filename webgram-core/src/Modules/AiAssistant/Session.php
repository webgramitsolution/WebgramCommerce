<?php
namespace Webgram\Core\Modules\AiAssistant;

defined( 'ABSPATH' ) || exit;

/** Random 64 hex session key in an HttpOnly, SameSite=Lax cookie. Never the WooCommerce session id. */
final class Session {

	public const COOKIE = 'wg_ai_session';

	/** Pure. */
	public static function valid( string $key ): bool {
		return (bool) preg_match( '/^[a-f0-9]{64}$/', $key );
	}

	public static function generate(): string {
		return bin2hex( random_bytes( 32 ) );
	}

	public static function current(): string {
		$key = isset( $_COOKIE[ self::COOKIE ] ) ? (string) $_COOKIE[ self::COOKIE ] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return self::valid( $key ) ? $key : '';
	}

	/** Returns the existing key or starts a new one (sets the cookie when headers are still open). */
	public static function start( int $days = 30 ): string {
		$key = self::current();
		if ( '' !== $key ) {
			return $key;
		}
		$key = self::generate();
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, $key, [ 'expires' => time() + $days * DAY_IN_SECONDS, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );
		}
		$_COOKIE[ self::COOKIE ] = $key;
		return $key;
	}

	/** Non-reversible hash for rate limiting and analytics. */
	public static function hash( string $key ): string {
		return substr( hash( 'sha256', 'wg-ai|' . $key ), 0, 40 );
	}
}
