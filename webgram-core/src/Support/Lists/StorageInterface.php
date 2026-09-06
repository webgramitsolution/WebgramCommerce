<?php
namespace Webgram\Core\Support\Lists;

defined( 'ABSPATH' ) || exit;

/** Storage for a per-visitor product id list (wishlist, compare). */
interface StorageInterface {

	/** @return int[] */
	public function get(): array;

	/** @param int[] $ids */
	public function set( array $ids ): void;
}
