<?php
namespace Webgram\Core\Support\Lists;

defined( 'ABSPATH' ) || exit;

/**
 * Signed, expiring token that carries a product id list (wishlist share links). Works for guests as well because
 * the ids travel inside the token instead of pointing at an account.
 */
final class ShareToken {

	/** @param callable(string): string $signer */
	public static function create( array $ids, int $expires_at, callable $signer ): string {
		$payload = rtrim( strtr( base64_encode( wp_json_encode( [ 'ids' => array_values( ProductList::normalize( $ids ) ), 'exp' => $expires_at ] ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return $payload . '.' . substr( $signer( $payload ), 0, 32 );
	}

	/**
	 * @param callable(string): string $signer
	 * @return int[]|null null when invalid or expired
	 */
	public static function parse( string $token, int $now, callable $signer ): ?array {
		if ( '' === $token || ! str_contains( $token, '.' ) ) {
			return null;
		}
		[ $payload, $sig ] = explode( '.', $token, 2 );
		if ( ! hash_equals( substr( $signer( $payload ), 0, 32 ), $sig ) ) {
			return null;
		}
		$data = json_decode( (string) base64_decode( strtr( $payload, '-_', '+/' ), true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! is_array( $data ) || empty( $data['ids'] ) || (int) ( $data['exp'] ?? 0 ) < $now ) {
			return null;
		}
		return ProductList::normalize( (array) $data['ids'] );
	}
}
