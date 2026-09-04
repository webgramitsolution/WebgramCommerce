<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts API credentials at rest using libsodium secretbox.
 * Key is derived from the site's AUTH_KEY + SECURE_AUTH_KEY, so moving the database to another install
 * (with different salts) makes stored tokens unreadable, which is the intended behavior.
 *
 * Optionally define WEBGRAM_CORE_ENCRYPTION_KEY in wp-config.php to pin the key independently of the salts.
 */
final class Crypto {

	private const PREFIX = 'wgc1:';

	public function is_available(): bool {
		return function_exists( 'sodium_crypto_secretbox' );
	}

	public function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		if ( ! $this->is_available() ) {
			// Sodium ships with PHP 7.2+. If a host has disabled it, refuse to store a plaintext secret.
			throw new \RuntimeException( 'Sodium extension is not available; cannot store credentials securely.' );
		}
		$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = sodium_crypto_secretbox( $plain, $nonce, $this->key() );
		$out    = self::PREFIX . base64_encode( $nonce . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		sodium_memzero( $plain );
		return $out;
	}

	public function decrypt( string $stored ): string {
		if ( '' === $stored || ! str_starts_with( $stored, self::PREFIX ) || ! $this->is_available() ) {
			return '';
		}
		$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return '';
		}
		$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, $this->key() );
		return false === $plain ? '' : $plain;
	}

	public function is_encrypted( string $value ): bool {
		return str_starts_with( $value, self::PREFIX );
	}

	/** Masks a secret for display: first 4 and last 4 characters visible. */
	public static function mask( string $secret ): string {
		$len = strlen( $secret );
		if ( $len <= 8 ) {
			return str_repeat( '*', $len );
		}
		return substr( $secret, 0, 4 ) . str_repeat( '*', min( 12, $len - 8 ) ) . substr( $secret, -4 );
	}

	private function key(): string {
		$material = defined( 'WEBGRAM_CORE_ENCRYPTION_KEY' ) && WEBGRAM_CORE_ENCRYPTION_KEY
			? (string) WEBGRAM_CORE_ENCRYPTION_KEY
			: ( defined( 'AUTH_KEY' ) ? AUTH_KEY : '' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '' );

		if ( '' === $material ) {
			throw new \RuntimeException( 'No key material for credential encryption.' );
		}
		return hash( 'sha256', 'webgram-core|' . $material, true );
	}
}
