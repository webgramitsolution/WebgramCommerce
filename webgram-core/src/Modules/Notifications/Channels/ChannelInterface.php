<?php
namespace Webgram\Core\Modules\Notifications\Channels;

defined( 'ABSPATH' ) || exit;

/**
 * A delivery channel. $message: [ 'event', 'order_id', 'recipient', 'variables' => [...], 'template' => [ 'name',
 * 'language', 'params' => [...] ], 'document' => [ 'url', 'filename' ]|null, 'subject', 'body' ].
 * send() returns [ 'ok' => bool, 'provider_message_id' => string, 'error_code' => string, 'error_message' =>
 * string, 'retryable' => bool ] and never throws.
 */
interface ChannelInterface {

	public function id(): string;

	public function label(): string;

	public function configured(): bool;

	/** Recipient for the order (E.164 phone, email) or '' when none. */
	public function recipient( \WC_Order $order ): string;

	public function send( array $message ): array;
}
