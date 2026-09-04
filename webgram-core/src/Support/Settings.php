<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Per-module settings stored as one option each: webgram_core_settings_{module}.
 * Only 'general' is autoloaded; module options load on first read so unused modules cost nothing on the front end.
 */
final class Settings {

	private string $module = 'general';

	/** @var array<string, array<string, mixed>> */
	private static array $cache = [];

	public function for( string $module ): Settings {
		$clone         = clone $this;
		$clone->module = sanitize_key( $module );
		return $clone;
	}

	private function option_name(): string {
		return 'webgram_core_settings_' . $this->module;
	}

	/** @return array<string, mixed> */
	public function all(): array {
		if ( ! isset( self::$cache[ $this->module ] ) ) {
			$stored = get_option( $this->option_name(), [] );
			self::$cache[ $this->module ] = is_array( $stored ) ? $stored : [];
		}
		return self::$cache[ $this->module ];
	}

	public function get( string $key, mixed $default = null ): mixed {
		$all   = $this->all();
		$value = array_key_exists( $key, $all ) ? $all[ $key ] : $default;
		return apply_filters( 'webgram_core/setting/' . $this->module . '/' . $key, $value, $default );
	}

	public function set( string $key, mixed $value ): void {
		$all         = $this->all();
		$all[ $key ] = $value;
		$this->save( $all );
	}

	/** @param array<string, mixed> $values full replacement */
	public function save( array $values ): void {
		self::$cache[ $this->module ] = $values;
		update_option( $this->option_name(), $values, 'general' === $this->module );
	}

	public function delete(): void {
		unset( self::$cache[ $this->module ] );
		delete_option( $this->option_name() );
	}
}
