<?php
namespace Webgram\Core\Support\Lists;

defined( 'ABSPATH' ) || exit;

final class UserMetaStorage implements StorageInterface {

	public function __construct( private int $user_id, private string $meta_key ) {}

	public function get(): array {
		$ids = get_user_meta( $this->user_id, $this->meta_key, true );
		return ProductList::normalize( is_array( $ids ) ? $ids : [] );
	}

	public function set( array $ids ): void {
		if ( $ids ) {
			update_user_meta( $this->user_id, $this->meta_key, ProductList::normalize( $ids ) );
		} else {
			delete_user_meta( $this->user_id, $this->meta_key );
		}
	}
}
