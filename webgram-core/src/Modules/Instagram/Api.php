<?php
namespace Webgram\Core\Modules\Instagram;

defined( 'ABSPATH' ) || exit;

/**
 * Instagram Graph API client for Business and Creator accounts (Basic Display API was retired by Meta in
 * December 2024). All HTTP goes through wp_remote_get with a 10 second timeout and response validation.
 */
final class Api {

	public const FIELDS = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp';

	public function __construct( private string $version, private string $user_id, private string $token ) {}

	/** Pure. */
	public static function media_url( string $version, string $user_id, int $limit ): string {
		return sprintf( 'https://graph.facebook.com/%s/%s/media?fields=%s&limit=%d', rawurlencode( $version ), rawurlencode( $user_id ), self::FIELDS, max( 1, min( 50, $limit ) ) );
	}

	/**
	 * Pure: validate and map the API payload to feed items.
	 *
	 * @return array<int, array{id: string, type: string, image: string, url: string, caption: string, time: string}>
	 */
	public static function normalize( array $payload, int $limit ): array {
		$out = [];
		foreach ( (array) ( $payload['data'] ?? [] ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$type  = strtoupper( (string) ( $item['media_type'] ?? '' ) );
			$image = 'VIDEO' === $type ? (string) ( $item['thumbnail_url'] ?? '' ) : (string) ( $item['media_url'] ?? '' );
			$url   = (string) ( $item['permalink'] ?? '' );
			if ( '' === $image || ! str_starts_with( $image, 'https://' ) || ! str_starts_with( $url, 'https://' ) ) {
				continue;
			}
			$out[] = [
				'id'      => (string) ( $item['id'] ?? '' ),
				'type'    => 'VIDEO' === $type ? 'video' : ( 'CAROUSEL_ALBUM' === $type ? 'album' : 'image' ),
				'image'   => $image,
				'url'     => $url,
				'caption' => trim( (string) ( $item['caption'] ?? '' ) ),
				'time'    => (string) ( $item['timestamp'] ?? '' ),
			];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	/** @return array|\WP_Error decoded JSON */
	private function get( string $url ): array|\WP_Error {
		$response = wp_remote_get( add_query_arg( 'access_token', $this->token, $url ), [ 'timeout' => 10, 'headers' => [ 'Accept' => 'application/json' ] ] );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'instagram_invalid', __( 'Instagram returned an unreadable response.', 'webgram-core' ) );
		}
		if ( $code >= 400 || isset( $body['error'] ) ) {
			$message = (string) ( $body['error']['message'] ?? sprintf( 'HTTP %d', $code ) );
			return new \WP_Error( 'instagram_api', $message, [ 'status' => $code ] );
		}
		return $body;
	}

	/** @return array|\WP_Error normalized items */
	public function media( int $limit ): array|\WP_Error {
		$body = $this->get( self::media_url( $this->version, $this->user_id, $limit ) );
		return is_wp_error( $body ) ? $body : self::normalize( $body, $limit );
	}

	/** @return array{username: string, media_count: int}|\WP_Error */
	public function account(): array|\WP_Error {
		$body = $this->get( sprintf( 'https://graph.facebook.com/%s/%s?fields=username,media_count', rawurlencode( $this->version ), rawurlencode( $this->user_id ) ) );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		return [ 'username' => (string) ( $body['username'] ?? '' ), 'media_count' => (int) ( $body['media_count'] ?? 0 ) ];
	}

	/**
	 * Refresh an Instagram Login long-lived token (60 days). Facebook Login tokens cannot be refreshed this way and
	 * must be regenerated; the caller records the error for the admin notice.
	 *
	 * @return array{token: string, expires_in: int}|\WP_Error
	 */
	public function refresh(): array|\WP_Error {
		$body = $this->get( 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token' );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		if ( empty( $body['access_token'] ) ) {
			return new \WP_Error( 'instagram_refresh', __( 'Instagram did not return a refreshed token.', 'webgram-core' ) );
		}
		return [ 'token' => (string) $body['access_token'], 'expires_in' => (int) ( $body['expires_in'] ?? 0 ) ];
	}
}
