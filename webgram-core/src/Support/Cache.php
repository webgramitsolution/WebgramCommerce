<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Cache wrapper with group invalidation.
 * Uses the persistent object cache when one exists, otherwise transients.
 * Group invalidation works by bumping a per-group version that is part of every key.
 */
final class Cache {

	private const PREFIX = 'wgc_';

	public function get( string $key, string $group = 'default' ): mixed {
		$key = $this->key( $key, $group );
		return wp_using_ext_object_cache() ? wp_cache_get( $key, 'webgram_core' ) : get_transient( $key );
	}

	public function set( string $key, mixed $value, int $ttl = HOUR_IN_SECONDS, string $group = 'default' ): void {
		$key = $this->key( $key, $group );
		if ( wp_using_ext_object_cache() ) {
			wp_cache_set( $key, $value, 'webgram_core', $ttl );
		} else {
			set_transient( $key, $value, $ttl );
		}
	}

	public function delete( string $key, string $group = 'default' ): void {
		$key = $this->key( $key, $group );
		if ( wp_using_ext_object_cache() ) {
			wp_cache_delete( $key, 'webgram_core' );
		} else {
			delete_transient( $key );
		}
	}

	/** Invalidates every key in the group by rotating its version. Old transients expire naturally. */
	public function flush_group( string $group ): void {
		update_option( self::PREFIX . 'ver_' . sanitize_key( $group ), (string) time(), false );
	}

	public function remember( string $key, int $ttl, callable $callback, string $group = 'default' ): mixed {
		$value = $this->get( $key, $group );
		if ( false !== $value && null !== $value ) {
			return $value;
		}
		$value = $callback();
		$this->set( $key, $value, $ttl, $group );
		return $value;
	}

	private function key( string $key, string $group ): string {
		$version = (string) get_option( self::PREFIX . 'ver_' . sanitize_key( $group ), '1' );
		return self::PREFIX . sanitize_key( $group ) . '_' . $version . '_' . md5( $key );
	}
}
