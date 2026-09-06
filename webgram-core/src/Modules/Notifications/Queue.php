<?php
namespace Webgram\Core\Modules\Notifications;

use Webgram\Core\Modules\Notifications\Channels\EmailChannel;

defined( 'ABSPATH' ) || exit;

/**
 * Every send is a background job (Action Scheduler when present, WP cron otherwise). The log row is the
 * idempotency key, so a duplicate event fire is a no-op. Retries: 3 with 1, 5 and 30 minute backoff on network
 * and 5xx errors; none on template or recipient errors. Order hooks never wait on a send or throw.
 */
final class Queue {

	public const HOOK        = 'webgram_core_notification_send';
	public const MAX_ATTEMPTS = 3;

	public function __construct( private Module $module, private Log $log, private Templates $templates ) {}

	public function register(): void {
		add_action( self::HOOK, [ $this, 'process' ] );
		add_filter( 'webgram_core/cron_hooks', static fn( array $hooks ) => array_merge( $hooks, [ self::HOOK ] ) );
	}

	/** Pure: seconds before the next attempt. */
	public static function backoff( int $attempt ): int {
		return [ 1 => 60, 2 => 300, 3 => 1800 ][ $attempt ] ?? 1800;
	}

	/** Pure. */
	public static function should_retry( array $result, int $attempts ): bool {
		return ! ( $result['ok'] ?? false ) && ! empty( $result['retryable'] ) && $attempts < self::MAX_ATTEMPTS;
	}

	private function schedule( int $log_id, int $delay = 0 ): void {
		if ( function_exists( 'as_enqueue_async_action' ) && 0 === $delay ) {
			as_enqueue_async_action( self::HOOK, [ $log_id ], 'webgram-core' );
		} elseif ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $delay, self::HOOK, [ $log_id ], 'webgram-core' );
		} else {
			wp_schedule_single_event( time() + $delay, self::HOOK, [ $log_id ] );
		}
	}

	/** Called from WooCommerce hooks: never throws. */
	public function enqueue( \WC_Order $order, string $event ): void {
		try {
			if ( ! $this->log->exists() ) {
				return;
			}
			foreach ( $this->module->channels() as $channel ) {
				if ( ! $this->module->event_enabled( $event, $channel->id() ) ) {
					continue;
				}
				if ( 'email' === $channel->id() && EmailChannel::handled_by_woocommerce( $event ) ) {
					continue; // WooCommerce sends this one itself; the matrix already gates it.
				}
				if ( ! $channel->configured() ) {
					continue;
				}
				$recipient = $channel->recipient( $order );
				$skip      = '';
				if ( 'whatsapp' === $channel->id() && ! $this->module->optin()->has_consent( $order ) ) {
					$skip = 'skipped_no_consent';
				} elseif ( '' === $recipient ) {
					$skip = 'whatsapp' === $channel->id() ? 'skipped_invalid_phone' : 'skipped_no_recipient';
				} elseif ( 'whatsapp' === $channel->id() && '' === $this->templates->mapping( $event )['name'] ) {
					$skip = 'skipped_no_template';
				}
				[ $id, $created ] = $this->log->create( $order->get_id(), $event, $channel->id(), $recipient, $skip ? 'skipped' : 'queued', [ 'event' => $event ], $skip );
				if ( $created && ! $skip ) {
					$this->schedule( $id );
				}
			}
		} catch ( \Throwable $e ) {
			\webgram_core()->logger()->error( 'Notification enqueue failed', [ 'event' => $event, 'error' => $e->getMessage() ] );
		}
	}

	/** Build the channel message for a log row. */
	public function message( array $row, \WC_Order $order ): array {
		$variables = $this->templates->variables( $order, (string) $row['event'] );
		$message   = [ 'event' => (string) $row['event'], 'order_id' => (int) $row['order_id'], 'recipient' => '', 'variables' => $variables, 'template' => [], 'document' => null, 'subject' => '', 'body' => '' ];
		$channel   = $this->module->channel( (string) $row['channel'] );
		if ( $channel ) {
			$message['recipient'] = $channel->recipient( $order );
		}
		if ( 'whatsapp' === $row['channel'] ) {
			$map                 = $this->templates->mapping( (string) $row['event'] );
			$message['template'] = [ 'name' => $map['name'], 'language' => $map['language'], 'params' => Templates::params( $map['params'], $variables ) ];
			if ( $map['document'] && '' !== $variables['invoice_url'] ) {
				$message['document'] = [ 'url' => $variables['invoice_url'], 'filename' => 'invoice-' . sanitize_file_name( $variables['invoice_number'] ) . '.pdf' ];
			}
		} elseif ( 'email' === $row['channel'] ) {
			$message += $this->templates->email_content( (string) $row['event'], $variables );
		}
		return (array) apply_filters( 'webgram_core/notifications/message', $message, $row, $order );
	}

	public function process( $log_id ): void {
		$row = $this->log->find( (int) $log_id );
		if ( ! $row || ! in_array( $row['status'], [ 'queued', 'failed' ], true ) ) {
			return;
		}
		$order   = wc_get_order( (int) $row['order_id'] );
		$channel = $this->module->channel( (string) $row['channel'] );
		if ( ! $order instanceof \WC_Order || ! $channel ) {
			$this->log->update( (int) $log_id, [ 'status' => 'failed', 'error_code' => 'missing', 'error_message' => 'Order or channel not available' ] );
			return;
		}
		$attempts = (int) $row['attempts'] + 1;
		$result   = $channel->send( $this->message( $row, $order ) );
		$result   = (array) apply_filters( 'webgram_core/notifications/send_result', $result, $row, $order );
		if ( ! empty( $result['ok'] ) ) {
			$this->log->update( (int) $log_id, [ 'status' => 'sent', 'attempts' => $attempts, 'provider_message_id' => (string) ( $result['provider_message_id'] ?? '' ), 'error_code' => null, 'error_message' => null ] );
			do_action( 'webgram_core/notifications/sent', $row, $order, $result );
			return;
		}
		$this->log->update( (int) $log_id, [ 'status' => self::should_retry( $result, $attempts ) ? 'queued' : 'failed', 'attempts' => $attempts, 'error_code' => (string) ( $result['error_code'] ?? '' ), 'error_message' => (string) ( $result['error_message'] ?? '' ) ] );
		if ( self::should_retry( $result, $attempts ) ) {
			$this->schedule( (int) $log_id, self::backoff( $attempts ) );
		} else {
			do_action( 'webgram_core/notifications/failed', $row, $order, $result );
		}
	}

	/** Admin retry (same row) or resend (new attempt cycle). */
	public function resend( int $log_id ): void {
		$row = $this->log->find( $log_id );
		if ( $row ) {
			$this->log->reset_for_resend( $log_id );
			$this->schedule( $log_id );
		}
	}

	/** Admin "Resend event" for an order: recreate the row when it was skipped or missing. */
	public function resend_event( \WC_Order $order, string $event, string $channel_id ): void {
		$this->log->delete_key( $order->get_id(), $event, $channel_id );
		$channel = $this->module->channel( $channel_id );
		if ( ! $channel ) {
			return;
		}
		[ $id ] = $this->log->create( $order->get_id(), $event, $channel_id, $channel->recipient( $order ), 'queued', [ 'event' => $event, 'manual' => true ] );
		if ( $id ) {
			$this->schedule( $id );
		}
	}
}
