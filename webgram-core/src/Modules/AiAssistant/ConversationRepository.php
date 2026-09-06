<?php
namespace Webgram\Core\Modules\AiAssistant;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/** {prefix}wg_ai_conversations */
final class ConversationRepository extends Repository {

	protected function table_name(): string {
		return 'wg_ai_conversations';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_key CHAR(64) NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			provider VARCHAR(32) NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'open',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY session_key (session_key),
			KEY user_id (user_id),
			KEY updated_at (updated_at)
		) {$this->charset_collate()};";
	}

	public function find_open( string $session_key ): ?int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$id = $this->db->get_var( $this->db->prepare( 'SELECT id FROM `' . esc_sql( $this->table() ) . '` WHERE session_key = %s AND status = %s ORDER BY id DESC LIMIT 1', $session_key, 'open' ) );
		return $id ? (int) $id : null;
	}

	public function create( string $session_key, int $user_id, string $provider ): int {
		$now = current_time( 'mysql', true );
		$this->db->insert( $this->table(), [ 'session_key' => $session_key, 'user_id' => $user_id ?: null, 'provider' => $provider, 'status' => 'open', 'created_at' => $now, 'updated_at' => $now ], [ '%s', '%d', '%s', '%s', '%s', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $this->db->insert_id;
	}

	public function touch( int $id, int $user_id = 0 ): void {
		$data = [ 'updated_at' => current_time( 'mysql', true ) ];
		if ( $user_id > 0 ) {
			$data['user_id'] = $user_id;
		}
		$this->db->update( $this->table(), $data, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @return int[] */
	public function ids_for_user( int $user_id ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map( 'intval', (array) $this->db->get_col( $this->db->prepare( 'SELECT id FROM `' . esc_sql( $this->table() ) . '` WHERE user_id = %d ORDER BY id ASC', $user_id ) ) );
	}

	/** @return int[] */
	public function ids_older_than( int $days ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_map( 'intval', (array) $this->db->get_col( $this->db->prepare( 'SELECT id FROM `' . esc_sql( $this->table() ) . '` WHERE updated_at < %s', gmdate( 'Y-m-d H:i:s', time() - max( 1, $days ) * DAY_IN_SECONDS ) ) ) );
	}

	public function delete_ids( array $ids ): void {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids ) {
			return;
		}
		$in = implode( ',', $ids );
		$this->db->query( 'DELETE FROM `' . esc_sql( $this->table() ) . '` WHERE id IN (' . $in . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- integer list.
	}
}
