<?php
namespace Webgram\Core\Support\Lists;

defined( 'ABSPATH' ) || exit;

/**
 * A capped, ordered list of product ids with pluggable storage (user meta when logged in, signed cookie for guests).
 * Newest additions come first. Used by the Wishlist and Compare modules.
 */
final class ProductList {

	public const MAX = 50;

	public function __construct( private StorageInterface $storage, private int $max = self::MAX ) {}

	/** @return int[] */
	public static function normalize( array $ids, int $max = self::MAX ): array {
		$out = [];
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
			if ( count( $out ) >= $max ) {
				break;
			}
		}
		return $out;
	}

	/** @return int[] */
	public function ids(): array {
		return self::normalize( $this->storage->get(), $this->max );
	}

	public function has( int $id ): bool {
		return in_array( $id, $this->ids(), true );
	}

	public function count(): int {
		return count( $this->ids() );
	}

	public function is_full(): bool {
		return $this->count() >= $this->max;
	}

	/** Returns false when the list is full. */
	public function add( int $id ): bool {
		$ids = $this->ids();
		if ( in_array( $id, $ids, true ) ) {
			return true;
		}
		if ( count( $ids ) >= $this->max ) {
			return false;
		}
		array_unshift( $ids, $id );
		$this->storage->set( $ids );
		return true;
	}

	public function remove( int $id ): void {
		$ids = array_values( array_filter( $this->ids(), static fn( int $i ) => $i !== $id ) );
		$this->storage->set( $ids );
	}

	/** Returns 'added', 'removed' or 'full'. */
	public function toggle( int $id ): string {
		if ( $this->has( $id ) ) {
			$this->remove( $id );
			return 'removed';
		}
		return $this->add( $id ) ? 'added' : 'full';
	}

	public function clear(): void {
		$this->storage->set( [] );
	}

	/** Merge another list's ids (guest cookie) into this one, keeping existing entries first. */
	public function merge( array $ids ): void {
		$merged = self::normalize( array_merge( $this->ids(), $ids ), $this->max );
		if ( $merged !== $this->ids() ) {
			$this->storage->set( $merged );
		}
	}
}
