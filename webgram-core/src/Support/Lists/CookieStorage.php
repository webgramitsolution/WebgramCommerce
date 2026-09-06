<?php
namespace Webgram\Core\Support\Lists;

defined( 'ABSPATH' ) || exit;

/**
 * Guest storage in a signed cookie. The value is "base64(json ids).signature"; a tampered or foreign cookie decodes
 * to an empty list. Reads the in-request value after a write so the same request sees its own change.
 */
final class CookieStorage implements StorageInterface {

	private ?array $memory = null;

	/** @param callable(string): string $signer returns an HMAC for the given payload */
	public function __construct( private string $name, private $signer, private int $days = 30 ) {}

	public function get(): array {
		if ( null !== $this->memory ) {
			return $this->memory;
		}
		$raw          = isset( $_COOKIE[ $this->name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $this->name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->memory = self::unpack( $raw, $this->signer );
		return $this->memory;
	}

	public function set( array $ids ): void {
		$ids          = ProductList::normalize( $ids );
		$this->memory = $ids;
		if ( headers_sent() ) {
			return;
		}
		$value  = $ids ? self::pack( $ids, $this->signer ) : '';
		$expire = $ids ? time() + $this->days * DAY_IN_SECONDS : time() - HOUR_IN_SECONDS;
		setcookie( $this->name, $value, [ 'expires' => $expire, 'path' => COOKIEPATH ? COOKIEPATH : '/', 'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ] );
	}

	/** @param callable(string): string $signer */
	public static function pack( array $ids, callable $signer ): string {
		$payload = base64_encode( wp_json_encode( array_values( ProductList::normalize( $ids ) ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return $payload . '.' . substr( $signer( $payload ), 0, 32 );
	}

	/** @param callable(string): string $signer */
	public static function unpack( string $value, callable $signer ): array {
		if ( '' === $value || ! str_contains( $value, '.' ) ) {
			return [];
		}
		[ $payload, $sig ] = explode( '.', $value, 2 );
		if ( ! hash_equals( substr( $signer( $payload ), 0, 32 ), $sig ) ) {
			return [];
		}
		$ids = json_decode( (string) base64_decode( $payload, true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return is_array( $ids ) ? ProductList::normalize( $ids ) : [];
	}
}
