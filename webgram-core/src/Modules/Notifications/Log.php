<?php
namespace Webgram\Core\Modules\Notifications;

use Webgram\Core\Abstracts\Repository;

defined( 'ABSPATH' ) || exit;

/** {prefix}wg_notification_log with UNIQUE(order_id, event, channel) as the idempotency key. */
final class Log extends Repository {

	public const STATUSES = [ 'queued', 'sent', 'delivered', 'read', 'failed', 'skipped' ];

	protected function table_name(): string {
		return 'wg_notification_log';
	}

	public function schema(): string {
		return "CREATE TABLE {$this->table()} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			event VARCHAR(32) NOT NULL,
			channel VARCHAR(24) NOT NULL,
			recipient_hash CHAR(40) NULL,
			recipient_masked VARCHAR(64) NULL,
			status VARCHAR(12) NOT NULL DEFAULT 'queued',
			provider_message_id VARCHAR(191) NULL,
			attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
			error_code VARCHAR(64) NULL,
			error_message VARCHAR(255) NULL,
			payload_summary TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY order_event_channel (order_id, event, channel),
			KEY status (status),
			KEY created_at (created_at),
			KEY provider_message_id (provider_message_id)
		) {$this->charset_collate()};";
	}

	/** Pure: mask a phone or email for display. */
	public static function mask( string $recipient ): string {
		if ( str_contains( $recipient, '@' ) ) {
			[ $user, $domain ] = explode( '@', $recipient, 2 );
			return mb_substr( $user, 0, 2 ) . str_repeat( '*', max( 1, mb_strlen( $user ) - 2 ) ) . '@' . $domain;
		}
		$digits = preg_replace( '/\D+/', '', $recipient ) ?? '';
		return strlen( $digits ) > 4 ? str_repeat( '*', strlen( $digits ) - 4 ) . substr( $digits, -4 ) : str_repeat( '*', strlen( $digits ) );
	}

	/** Insert a row unless one exists for the key. Returns [id, created]. */
	public function create( int $order_id, string $event, string $channel, string $recipient, string $status, array $summary = [], string $error_code = '', string $error_message = '' ): array {
		$existing = $this->find_by_key( $order_id, $event, $channel );
		if ( $existing ) {
			return [ (int) $existing['id'], false ];
		}
		$now = current_time( 'mysql', true );
		$ok  = $this->db->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->table(),
			[
				'order_id'         => $order_id,
				'event'            => $event,
				'channel'          => $channel,
				'recipient_hash'   => '' !== $recipient ? sha1( $recipient ) : null,
				'recipient_masked' => '' !== $recipient ? self::mask( $recipient ) : null,
				'status'           => in_array( $status, self::STATUSES, true ) ? $status : 'queued',
				'attempts'         => 0,
				'error_code'       => $error_code ?: null,
				'error_message'    => $error_message ? mb_substr( $error_message, 0, 255 ) : null,
				'payload_summary'  => $summary ? wp_json_encode( $summary ) : null,
				'created_at'       => $now,
				'updated_at'       => $now,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
		if ( ! $ok ) {
			$existing = $this->find_by_key( $order_id, $event, $channel );
			return [ $existing ? (int) $existing['id'] : 0, false ];
		}
		return [ (int) $this->db->insert_id, true ];
	}

	public function find( int $id ): ?array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->db->get_row( $this->db->prepare( 'SELECT * FROM `' . esc_sql( $this->table() ) . '` WHERE id = %d', $id ), ARRAY_A );
		return $row ?: null;
	}

	public function find_by_key( int $order_id, string $event, string $channel ): ?array {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $this->db->get_row( $this->db->prepare( 'SELECT * FROM `' . esc_sql( $this->table() ) . '` WHERE order_id = %d AND event = %s AND channel = %s', $order_id, $event, $channel ), ARRAY_A );
		return $row ?: null;
	}

	public function update( int $id, array $data ): void {
		$data['updated_at'] = current_time( 'mysql', true );
		if ( isset( $data['error_message'] ) ) {
			$data['error_message'] = mb_substr( (string) $data['error_message'], 0, 255 );
		}
		$this->db->update( $this->table(), $data, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function reset_for_resend( int $id ): void {
		$this->update( $id, [ 'status' => 'queued', 'attempts' => 0, 'error_code' => null, 'error_message' => null, 'provider_message_id' => null ] );
	}

	public function delete_key( int $order_id, string $event, string $channel ): void {
		$this->db->delete( $this->table(), [ 'order_id' => $order_id, 'event' => $event, 'channel' => $channel ], [ '%d', '%s', '%s' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	public function set_status_by_provider_id( string $provider_message_id, string $status, string $error_code = '', string $error_message = '' ): bool {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		$data = [ 'status' => $status, 'updated_at' => current_time( 'mysql', true ) ];
		if ( '' !== $error_code ) {
			$data['error_code']    = $error_code;
			$data['error_message'] = mb_substr( $error_message, 0, 255 );
		}
		return (bool) $this->db->update( $this->table(), $data, [ 'provider_message_id' => $provider_message_id ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** @return array{rows: array, total: int} */
	public function list( array $filters, int $page, int $per_page = 25 ): array {
		if ( ! $this->exists() ) {
			return [ 'rows' => [], 'total' => 0 ];
		}
		$where  = [ '1=1' ];
		$values = [];
		foreach ( [ 'status', 'channel', 'event' ] as $key ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$where[]  = "`$key` = %s";
				$values[] = (string) $filters[ $key ];
			}
		}
		if ( ! empty( $filters['order_id'] ) ) {
			$where[]  = 'order_id = %d';
			$values[] = (int) $filters['order_id'];
		}
		$sql   = 'FROM `' . esc_sql( $this->table() ) . '` WHERE ' . implode( ' AND ', $where );
		$total = (int) $this->db->get_var( $values ? $this->db->prepare( 'SELECT COUNT(*) ' . $sql, ...$values ) : 'SELECT COUNT(*) ' . $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$query = 'SELECT * ' . $sql . ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$rows  = (array) $this->db->get_results( $this->db->prepare( $query, ...array_merge( $values, [ $per_page, max( 0, ( $page - 1 ) * $per_page ) ] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return [ 'rows' => $rows, 'total' => $total ];
	}

	public function for_order( int $order_id ): array {
		return $this->exists() ? $this->list( [ 'order_id' => $order_id ], 1, 50 )['rows'] : [];
	}

	/** @return array{sent: int, delivered: int} counts within the window (sent includes delivered and read) */
	public function rate( int $days ): array {
		if ( ! $this->exists() ) {
			return [ 'sent' => 0, 'delivered' => 0 ];
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = (array) $this->db->get_results( $this->db->prepare( 'SELECT status, COUNT(*) AS n FROM `' . esc_sql( $this->table() ) . "` WHERE channel = 'whatsapp' AND created_at >= %s GROUP BY status", $since ), ARRAY_A );
		$sent = 0;
		$deliv = 0;
		foreach ( $rows as $row ) {
			if ( in_array( $row['status'], [ 'sent', 'delivered', 'read' ], true ) ) {
				$sent += (int) $row['n'];
			}
			if ( in_array( $row['status'], [ 'delivered', 'read' ], true ) ) {
				$deliv += (int) $row['n'];
			}
		}
		return [ 'sent' => $sent, 'delivered' => $deliv ];
	}
}
