<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/** {prefix}wg_pincodes: offline pincode table imported from CSV. */
final class PincodeRepository extends Repository {

	protected function table_name(): string {
		return 'wg_pincodes';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			pincode VARCHAR(12) NOT NULL,
			city VARCHAR(80) NULL,
			state VARCHAR(80) NULL,
			deliverable TINYINT(1) NOT NULL DEFAULT 1,
			cod TINYINT(1) NOT NULL DEFAULT 1,
			eta_days TINYINT UNSIGNED NULL,
			PRIMARY KEY  (pincode)
		) {$this->charset_collate()};";
	}

	/** @return array{pincode: string, city: string, state: string, deliverable: bool, cod: bool, eta_days: int|null}|null */
	public function find( string $pincode ): ?array {
		if ( ! $this->exists() ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->db->get_row( $this->db->prepare( 'SELECT pincode, city, state, deliverable, cod, eta_days FROM `' . esc_sql( $this->table() ) . '` WHERE pincode = %s', $pincode ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}
		return [
			'pincode'     => (string) $row['pincode'],
			'city'        => (string) $row['city'],
			'state'       => (string) $row['state'],
			'deliverable' => (bool) $row['deliverable'],
			'cod'         => (bool) $row['cod'],
			'eta_days'    => null === $row['eta_days'] ? null : (int) $row['eta_days'],
		];
	}

	public function count(): int {
		if ( ! $this->exists() ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $this->db->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $this->table() ) . '`' );
	}

	/**
	 * Insert or replace rows in batches.
	 *
	 * @param array<int, array{pincode: string, city: string, state: string, deliverable: bool, cod: bool, eta_days: int|null}> $rows
	 */
	public function upsert_many( array $rows ): int {
		if ( ! $rows ) {
			return 0;
		}
		$this->install();
		$total = 0;
		foreach ( array_chunk( $rows, 500 ) as $chunk ) {
			$values       = [];
			$placeholders = [];
			foreach ( $chunk as $row ) {
				$placeholders[] = '(%s, %s, %s, %d, %d, %s)';
				array_push( $values, $row['pincode'], $row['city'], $row['state'], $row['deliverable'] ? 1 : 0, $row['cod'] ? 1 : 0, null === $row['eta_days'] ? null : (string) $row['eta_days'] );
			}
			$sql = 'INSERT INTO `' . esc_sql( $this->table() ) . '` (pincode, city, state, deliverable, cod, eta_days) VALUES ' . implode( ', ', $placeholders )
				. ' ON DUPLICATE KEY UPDATE city = VALUES(city), state = VALUES(state), deliverable = VALUES(deliverable), cod = VALUES(cod), eta_days = VALUES(eta_days)';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			$result = $this->db->query( $this->db->prepare( $sql, $values ) );
			$total += false === $result ? 0 : count( $chunk );
		}
		return $total;
	}

	public function truncate(): void {
		if ( $this->exists() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->db->query( 'TRUNCATE TABLE `' . esc_sql( $this->table() ) . '`' );
		}
	}
}
