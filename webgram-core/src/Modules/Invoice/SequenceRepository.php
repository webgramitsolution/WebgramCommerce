<?php
namespace Webgram\Core\Modules\Invoice;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * {prefix}wg_invoice_sequence: AUTO_INCREMENT id is the gap-free sequence; UNIQUE(order_id) makes assignment
 * idempotent; UNIQUE(invoice_no) guards yearly-reset collisions (retried).
 */
final class SequenceRepository extends Repository {

	protected function table_name(): string {
		return 'wg_invoice_sequence';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			invoice_no VARCHAR(40) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_id (order_id),
			UNIQUE KEY invoice_no (invoice_no)
		) {$this->charset_collate()};";
	}

	/** @return array{id: int, order_id: int, invoice_no: string, created_at: string}|null */
	public function find_by_order( int $order_id ): ?array {
		if ( ! $this->exists() ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->db->get_row( $this->db->prepare( 'SELECT id, order_id, invoice_no, created_at FROM `' . esc_sql( $this->table() ) . '` WHERE order_id = %d', $order_id ), ARRAY_A );
		return $row ? [ 'id' => (int) $row['id'], 'order_id' => (int) $row['order_id'], 'invoice_no' => (string) $row['invoice_no'], 'created_at' => (string) $row['created_at'] ] : null;
	}

	/** Rows created in the same calendar or financial year before this id (for yearly reset numbering). */
	public function count_before_in_period( int $id, string $period_start ): int {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->db->get_var( $this->db->prepare( 'SELECT COUNT(*) FROM `' . esc_sql( $this->table() ) . '` WHERE id < %d AND created_at >= %s', $id, $period_start ) );
	}

	/**
	 * Assign a number to an order inside a transaction. $formatter receives (sequence id, created_at, attempt)
	 * and returns the invoice number; on a UNIQUE(invoice_no) collision it is called again (max 3 times).
	 *
	 * @return array{id: int, order_id: int, invoice_no: string, created_at: string}|null
	 */
	public function assign( int $order_id, callable $formatter ): ?array {
		if ( ! $this->exists() ) {
			$this->install();
		}
		$existing = $this->find_by_order( $order_id );
		if ( $existing && '' !== $existing['invoice_no'] ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$this->db->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! $existing ) {
			$ok = $this->db->insert( $this->table(), [ 'order_id' => $order_id, 'invoice_no' => 'pending-' . $order_id, 'created_at' => $now ], [ '%d', '%s', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( ! $ok ) {
				$this->db->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return $this->find_by_order( $order_id ); // Another request won the race.
			}
			$id = (int) $this->db->insert_id;
		} else {
			$id  = $existing['id'];
			$now = $existing['created_at'];
		}
		$number = '';
		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			$number = (string) $formatter( $id, $now, $attempt );
			$ok     = $this->db->update( $this->table(), [ 'invoice_no' => $number ], [ 'id' => $id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( false !== $ok ) {
				break;
			}
			$number = '';
		}
		if ( '' === $number ) {
			$this->db->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return null;
		}
		$this->db->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return [ 'id' => $id, 'order_id' => $order_id, 'invoice_no' => $number, 'created_at' => $now ];
	}

	public function count(): int {
		if ( ! $this->exists() ) {
			return 0;
		}
		return (int) $this->db->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $this->table() ) . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}
}
