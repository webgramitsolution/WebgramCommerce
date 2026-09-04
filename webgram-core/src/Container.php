<?php
namespace Webgram\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Tiny service container. Services are lazily built on first access and cached.
 * No external DI library: the plugin only needs shared singletons and factories.
 */
final class Container {

	/** @var array<string, callable> */
	private array $factories = [];

	/** @var array<string, mixed> */
	private array $instances = [];

	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->instances[ $id ] );
	}

	public function get( string $id ): mixed {
		if ( ! isset( $this->instances[ $id ] ) ) {
			if ( ! isset( $this->factories[ $id ] ) ) {
				throw new \RuntimeException( sprintf( 'Webgram Core: service "%s" is not registered.', $id ) );
			}
			$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );
		}
		return $this->instances[ $id ];
	}
}
