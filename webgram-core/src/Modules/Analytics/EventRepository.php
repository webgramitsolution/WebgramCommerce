<?php
namespace Webgram\Core\Modules\Analytics;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/** {prefix}wg_events: no IPs, session hashed, meta without personal data. */
final class EventRepository extends Repository {

	protected function table_name(): string {
		return 'wg_events';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event VARCHAR(48) NOT NULL,
			object_type VARCHAR(24) NULL,
			object_id BIGINT UNSIGNED NULL,
			user_id BIGINT UNSIGNED NULL,
			session_hash CHAR(40) NULL,
			meta LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY event_created (event, created_at),
			KEY object (object_type, object_id)
		) {$this->charset_collate()};";
	}

	/** @param array<int, array{event: string, object_type: string, object_id: int, user_id: int, session_hash: string, meta: array}> $rows */
	public function insert_many( array $rows ): int {
		$count = 0;
		foreach ( $rows as $row ) {
			$ok = $this->db->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$this->table(),
				[
					'event'        => $row['event'],
					'object_type'  => $row['object_type'] ?: null,
					'object_id'    => $row['object_id'] ?: null,
					'user_id'      => $row['user_id'] ?: null,
					'session_hash' => $row['session_hash'] ?: null,
					'meta'         => $row['meta'] ? wp_json_encode( $row['meta'] ) : null,
					'created_at'   => current_time( 'mysql', true ),
				],
				[ '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
			);
			$count += $ok ? 1 : 0;
		}
		return $count;
	}

	/** @return array<string, int> event => count within the window */
	public function counts( int $days ): array {
		if ( ! $this->exists() ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT event, COUNT(*) AS n FROM `' . esc_sql( $this->table() ) . '` WHERE created_at >= %s GROUP BY event', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ), ARRAY_A );
		$out  = [];
		foreach ( $rows as $row ) {
			$out[ (string) $row['event'] ] = (int) $row['n'];
		}
		return $out;
	}

	/** @return array<string, int> Y-m-d => count for one event over the window */
	public function daily( string $event, int $days ): array {
		if ( ! $this->exists() ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT DATE(created_at) AS d, COUNT(*) AS n FROM `' . esc_sql( $this->table() ) . '` WHERE event = %s AND created_at >= %s GROUP BY DATE(created_at) ORDER BY d ASC', $event, gmdate( 'Y-m-d 00:00:00', time() - $days * DAY_IN_SECONDS ) ), ARRAY_A );
		$out  = [];
		foreach ( $rows as $row ) {
			$out[ (string) $row['d'] ] = (int) $row['n'];
		}
		return $out;
	}

	/** @return array<int, array{object_id: int, n: int}> top objects for an event */
	public function top( string $event, string $object_type, int $days, int $limit = 10 ): array {
		if ( ! $this->exists() ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT object_id, COUNT(*) AS n FROM `' . esc_sql( $this->table() ) . '` WHERE event = %s AND object_type = %s AND created_at >= %s GROUP BY object_id ORDER BY n DESC LIMIT %d', $event, $object_type, gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ), $limit ), ARRAY_A );
		return array_map( static fn( array $r ) => [ 'object_id' => (int) $r['object_id'], 'n' => (int) $r['n'] ], $rows );
	}

	/** @return array<int, array<int, int>> product id => [days_ago => views] for the trending score */
	public function views_by_day( int $days ): array {
		if ( ! $this->exists() ) {
			return [];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT object_id, DATEDIFF(UTC_TIMESTAMP(), created_at) AS days_ago, COUNT(*) AS n FROM `' . esc_sql( $this->table() ) . '` WHERE event = %s AND object_type = %s AND created_at >= %s GROUP BY object_id, days_ago', 'product_view', 'product', gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ), ARRAY_A );
		$out  = [];
		foreach ( $rows as $row ) {
			$out[ (int) $row['object_id'] ][ (int) $row['days_ago'] ] = (int) $row['n'];
		}
		return $out;
	}

	public function delete_for_user( int $user_id ): int {
		if ( ! $this->exists() ) {
			return 0;
		}
		return (int) $this->db->delete( $this->table(), [ 'user_id' => $user_id ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function count_for_user( int $user_id ): int {
		if ( ! $this->exists() ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->db->get_var( $this->db->prepare( 'SELECT COUNT(*) FROM `' . esc_sql( $this->table() ) . '` WHERE user_id = %d', $user_id ) );
	}
}
