<?php
namespace Webgram\Core\Modules\Reels;

defined( 'ABSPATH' ) || exit;

/**
 * Video source adapters: MP4 (upload or URL), YouTube Shorts, Vimeo, Cloudflare Stream and Bunny Stream.
 * Third parties add adapters through webgram_core/reels/sources. Detection is pure so it is unit tested.
 */
final class Sources {

	/** @return array<string, array{label: string, match: callable, embed: callable}> */
	public static function all(): array {
		$sources = [
			'mp4'        => [
				'label' => 'MP4',
				'match' => static fn( string $url ): bool => (bool) preg_match( '/\.(mp4|webm|m4v)(\?|#|$)/i', $url ),
				'embed' => static fn( string $url ): array => [ 'type' => 'video', 'src' => $url ],
			],
			'youtube'    => [
				'label' => 'YouTube',
				'match' => static fn( string $url ): bool => (bool) preg_match( '#^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/#i', $url ),
				'embed' => static function ( string $url ): array {
					$id = self::youtube_id( $url );
					return $id ? [ 'type' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&mute=1&loop=1&playlist=' . $id . '&controls=0&playsinline=1&rel=0' ] : [];
				},
			],
			'vimeo'      => [
				'label' => 'Vimeo',
				'match' => static fn( string $url ): bool => (bool) preg_match( '#^https?://(www\.|player\.)?vimeo\.com/#i', $url ),
				'embed' => static function ( string $url ): array {
					return preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ? [ 'type' => 'iframe', 'src' => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&muted=1&loop=1&background=1&playsinline=1' ] : [];
				},
			],
			'cloudflare' => [
				'label' => 'Cloudflare Stream',
				'match' => static fn( string $url ): bool => (bool) preg_match( '#cloudflarestream\.com/#i', $url ),
				'embed' => static function ( string $url ): array {
					if ( preg_match( '#https?://([a-z0-9\-]+\.cloudflarestream\.com)/([a-f0-9]{32})#i', $url, $m ) ) {
						return [ 'type' => 'iframe', 'src' => 'https://' . $m[1] . '/' . $m[2] . '/iframe?autoplay=true&muted=true&loop=true&controls=false' ];
					}
					return [ 'type' => 'iframe', 'src' => $url ];
				},
			],
			'bunny'      => [
				'label' => 'Bunny Stream',
				'match' => static fn( string $url ): bool => (bool) preg_match( '#(mediadelivery\.net|b-cdn\.net)/#i', $url ),
				'embed' => static function ( string $url ): array {
					if ( preg_match( '#/play/(\d+)/([a-f0-9\-]+)#i', $url, $m ) ) {
						return [ 'type' => 'iframe', 'src' => 'https://iframe.mediadelivery.net/embed/' . $m[1] . '/' . $m[2] . '?autoplay=true&muted=true&loop=true&preload=false' ];
					}
					return (bool) preg_match( '/\.m3u8(\?|$)/i', $url ) ? [ 'type' => 'hls', 'src' => $url ] : [ 'type' => 'iframe', 'src' => $url ];
				},
			],
		];
		return (array) apply_filters( 'webgram_core/reels/sources', $sources );
	}

	/** Pure. */
	public static function youtube_id( string $url ): string {
		if ( preg_match( '#(?:shorts/|embed/|v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/** Pure: detect the adapter key for a URL, '' when none matches. */
	public static function detect( string $url, ?array $sources = null ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}
		foreach ( $sources ?? self::all() as $key => $source ) {
			if ( ( $source['match'] )( $url ) ) {
				return (string) $key;
			}
		}
		return '';
	}

	/** @return array{type: string, src: string} empty when unsupported */
	public static function embed( string $url, ?array $sources = null ): array {
		$key = self::detect( $url, $sources );
		if ( '' === $key ) {
			return [];
		}
		$embed = ( ( $sources ?? self::all() )[ $key ]['embed'] )( $url );
		return isset( $embed['src'] ) && '' !== $embed['src'] ? $embed + [ 'source' => $key ] : [];
	}
}
