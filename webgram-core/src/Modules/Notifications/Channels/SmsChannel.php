<?php
namespace Webgram\Core\Modules\Notifications\Channels;

defined( 'ABSPATH' ) || exit;

/** SMS: interface and stub only in v1; a provider can implement it through webgram_core/notifications/sms_send. */
final class SmsChannel implements ChannelInterface {

	public function id(): string {
		return 'sms';
	}

	public function label(): string {
		return __( 'SMS (coming later)', 'webgram-core' );
	}

	public function configured(): bool {
		return (bool) apply_filters( 'webgram_core/notifications/sms_configured', false );
	}

	public function recipient( \WC_Order $order ): string {
		return '';
	}

	public function send( array $message ): array {
		$result = apply_filters( 'webgram_core/notifications/sms_send', null, $message );
		return is_array( $result ) ? $result : [ 'ok' => false, 'provider_message_id' => '', 'error_code' => 'not_implemented', 'error_message' => 'No SMS provider installed', 'retryable' => false ];
	}
}
