<?php
namespace Webgram\Core\Modules\Notifications\Channels;

defined( 'ABSPATH' ) || exit;

/** Business Solution Provider adapter: interface and config shape only in v1 (no provider implemented). */
final class WhatsAppBspChannel implements ChannelInterface {

	public function id(): string {
		return 'whatsapp_bsp';
	}

	public function label(): string {
		return __( 'WhatsApp via BSP (coming later)', 'webgram-core' );
	}

	public function configured(): bool {
		return (bool) apply_filters( 'webgram_core/notifications/bsp_configured', false );
	}

	public function recipient( \WC_Order $order ): string {
		return '';
	}

	public function send( array $message ): array {
		$result = apply_filters( 'webgram_core/notifications/bsp_send', null, $message );
		return is_array( $result ) ? $result : [ 'ok' => false, 'provider_message_id' => '', 'error_code' => 'not_implemented', 'error_message' => 'No BSP provider installed', 'retryable' => false ];
	}
}
