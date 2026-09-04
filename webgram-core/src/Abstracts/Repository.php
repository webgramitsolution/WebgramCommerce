<?php
namespace Webgram\Core\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Base for custom-table access. Repositories are the only classes allowed to use $wpdb directly.
 * Every query goes through $wpdb->prepare() unless it has no user-supplied values.
 */
abstract class Repository {

	protected \wpdb $db;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/** Table name without prefix, e.g. 'wg_events'. */
	abstract protected function table_name(): string;

	/** CREATE TABLE statement body used with dbDelta. Must include the full table name via $this->table(). */
	abstract public function schema(): string;

	public function table(): string {
		return $this->db->prefix . $this->table_name();
	}

	public function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->schema() );
	}

	public function drop(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$this->db->query( 'DROP TABLE IF EXISTS `' . esc_sql( $this->table() ) . '`' );
	}

	public function exists(): bool {
		$table = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $this->db->get_var( $this->db->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	protected function charset_collate(): string {
		return $this->db->get_charset_collate();
	}

	/** Bulk delete rows older than the given number of days. Used by retention crons. */
	public function purge_older_than( int $days, string $column = 'created_at' ): int {
		$days = max( 1, $days );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $this->db->query(
			$this->db->prepare(
				'DELETE FROM `' . esc_sql( $this->table() ) . '` WHERE `' . esc_sql( $column ) . '` < %s',
				gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) )
			)
		);
		return (int) $deleted;
	}
}
