<?php
namespace Webgram\Core\Modules\Notifications\Channels;

use Webgram\Core\Modules\Notifications\Events;
use Webgram\Core\Modules\Notifications\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Email: WooCommerce already sends emails for most events, so the matrix only enables or disables those through
 * woocommerce_email_enabled_{id}. Events WooCommerce has no email for (shipped, out for delivery) are sent with
 * wp_mail through the WooCommerce mailer wrapper, so the Emails module branding applies. No SMTP is built here.
 */
final class EmailChannel implements ChannelInterface {

	public function __construct( private Module $module ) {}

	public function id(): string {
		return 'email';
	}

	public function label(): string {
		return __( 'Email', 'webgram-core' );
	}

	public function configured(): bool {
		return true;
	}

	public function recipient( \WC_Order $order ): string {
		return (string) $order->get_billing_email();
	}

	/** Hook the enabled matrix into WooCommerce's own emails. */
	public function register_matrix(): void {
		foreach ( Events::all() as $event => $def ) {
			foreach ( (array) $def['wc_emails'] as $email_id ) {
				add_filter( 'woocommerce_email_enabled_' . $email_id, fn( bool $enabled ) => $this->module->event_enabled( $event, 'email' ) ? $enabled : false );
			}
		}
	}

	/** Pure: events that WooCommerce covers itself need no send from this channel. */
	public static function handled_by_woocommerce( string $event ): bool {
		return [] !== (array) ( Events::all()[ $event ]['wc_emails'] ?? [] );
	}

	public function send( array $message ): array {
		if ( self::handled_by_woocommerce( (string) $message['event'] ) ) {
			return [ 'ok' => true, 'provider_message_id' => 'woocommerce', 'error_code' => '', 'error_message' => '', 'retryable' => false ];
		}
		$to      = (string) ( $message['recipient'] ?? '' );
		$subject = (string) ( $message['subject'] ?? '' );
		$body    = (string) ( $message['body'] ?? '' );
		if ( ! is_email( $to ) || '' === $subject ) {
			return [ 'ok' => false, 'provider_message_id' => '', 'error_code' => 'invalid_recipient', 'error_message' => 'No valid email address', 'retryable' => false ];
		}
		$mailer = function_exists( 'WC' ) ? WC()->mailer() : null;
		$html   = $mailer ? $mailer->wrap_message( $subject, wpautop( $body ) ) : wpautop( $body );
		$sent   = $mailer ? (bool) $mailer->send( $to, $subject, $html ) : wp_mail( $to, $subject, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );
		return [ 'ok' => $sent, 'provider_message_id' => $sent ? 'wp_mail' : '', 'error_code' => $sent ? '' : 'mail_failed', 'error_message' => $sent ? '' : 'wp_mail returned false', 'retryable' => ! $sent ];
	}
}
