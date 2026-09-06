<?php
namespace Webgram\Core\Modules\AiAssistant;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/** {prefix}wg_ai_messages */
final class MessageRepository extends Repository {

	protected function table_name(): string {
		return 'wg_ai_messages';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(12) NOT NULL,
			content TEXT NOT NULL,
			payload LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_created (conversation_id, created_at)
		) {$this->charset_collate()};";
	}

	public function add( int $conversation_id, string $role, string $content, array $payload = [] ): int {
		$this->db->insert( $this->table(), [ 'conversation_id' => $conversation_id, 'role' => $role, 'content' => $content, 'payload' => $payload ? wp_json_encode( $payload ) : null, 'created_at' => current_time( 'mysql', true ) ], [ '%d', '%s', '%s', '%s', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (int) $this->db->insert_id;
	}

	/** @return array<int, array{id: int, role: string, content: string, payload: array, created_at: string}> oldest first */
	public function recent( int $conversation_id, int $limit = 20 ): array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT id, role, content, payload, created_at FROM `' . esc_sql( $this->table() ) . '` WHERE conversation_id = %d ORDER BY id DESC LIMIT %d', $conversation_id, max( 1, $limit ) ), ARRAY_A );
		$out  = [];
		foreach ( array_reverse( $rows ) as $row ) {
			$payload = json_decode( (string) ( $row['payload'] ?? '' ), true );
			$out[]   = [ 'id' => (int) $row['id'], 'role' => (string) $row['role'], 'content' => (string) $row['content'], 'payload' => is_array( $payload ) ? $payload : [], 'created_at' => (string) $row['created_at'] ];
		}
		return $out;
	}

	/** User messages sent today (UTC), for the daily budget. */
	public function count_today( string $role = 'user' ): int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->db->get_var( $this->db->prepare( 'SELECT COUNT(*) FROM `' . esc_sql( $this->table() ) . '` WHERE role = %s AND created_at >= %s', $role, gmdate( 'Y-m-d 00:00:00' ) ) );
	}

	public function delete_for_conversations( array $ids ): void {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids ) {
			return;
		}
		$this->db->query( 'DELETE FROM `' . esc_sql( $this->table() ) . '` WHERE conversation_id IN (' . implode( ',', $ids ) . ')' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- integer list.
	}
}
