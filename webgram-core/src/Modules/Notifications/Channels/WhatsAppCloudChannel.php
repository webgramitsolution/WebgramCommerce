<?php
namespace Webgram\Core\Modules\Notifications\Channels;

use Webgram\Core\Modules\Notifications\Module;
use Webgram\Core\Modules\Notifications\PhoneNumber;

defined( 'ABSPATH' ) || exit;

/**
 * Meta WhatsApp Cloud API with the store owner's own credentials (Phone Number ID, WABA ID, access token).
 * Webgram never relays messages or bills for them; charges, if any, are billed by Meta to the owner's account.
 */
final class WhatsAppCloudChannel implements ChannelInterface {

	public function __construct( private Module $module ) {}

	public function id(): string {
		return 'whatsapp';
	}

	public function label(): string {
		return __( 'WhatsApp (Meta Cloud API)', 'webgram-core' );
	}

	private function setting( string $key, string $default = '' ): string {
		return trim( (string) $this->module->settings()->get( $key, $default ) );
	}

	private function token(): string {
		$stored = $this->setting( 'wa_access_token' );
		return '' === $stored ? '' : \webgram_core()->crypto()->decrypt( $stored );
	}

	public function version(): string {
		return $this->setting( 'wa_api_version', 'v21.0' ) ?: 'v21.0';
	}

	public function configured(): bool {
		return '' !== $this->setting( 'wa_phone_number_id' ) && '' !== $this->token();
	}

	public function recipient( \WC_Order $order ): string {
		return PhoneNumber::for_order( $order, $this->setting( 'default_country', 'IN' ) );
	}

	/** Pure: redact tokens and long digit strings from provider messages before logging. */
	public static function redact( string $text ): string {
		$text = preg_replace( '/(EAA|Bearer\s+)[A-Za-z0-9_\-]{10,}/', '$1[redacted]', $text ) ?? $text;
		return (string) preg_replace( '/\b\d{10,15}\b/', '[number]', $text );
	}

	/** Pure: template message payload. $params are body parameter strings in declared order. */
	public static function payload( string $to, string $template, string $language, array $params, ?array $document = null ): array {
		$components = [];
		if ( $document && ! empty( $document['url'] ) ) {
			$components[] = [ 'type' => 'header', 'parameters' => [ [ 'type' => 'document', 'document' => [ 'link' => (string) $document['url'], 'filename' => (string) ( $document['filename'] ?? 'invoice.pdf' ) ] ] ] ];
		}
		if ( $params ) {
			$components[] = [ 'type' => 'body', 'parameters' => array_map( static fn( $p ) => [ 'type' => 'text', 'text' => mb_substr( trim( (string) $p ), 0, 1024 ) ?: '-' ], array_values( $params ) ) ];
		}
		return [
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => ltrim( $to, '+' ),
			'type'              => 'template',
			'template'          => [ 'name' => $template, 'language' => [ 'code' => $language ?: 'en' ], 'components' => $components ],
		];
	}

	/** Pure: provider response to the channel result shape. */
	public static function parse_send_response( int $status, array $json ): array {
		if ( $status < 400 && ! empty( $json['messages'][0]['id'] ) ) {
			return [ 'ok' => true, 'provider_message_id' => (string) $json['messages'][0]['id'], 'error_code' => '', 'error_message' => '', 'retryable' => false ];
		}
		$code    = (string) ( $json['error']['code'] ?? $status );
		$message = self::redact( (string) ( $json['error']['message'] ?? ( 'HTTP ' . $status ) ) );
		return [ 'ok' => false, 'provider_message_id' => '', 'error_code' => $code, 'error_message' => $message, 'retryable' => $status >= 500 || 0 === $status || in_array( $code, [ '130429', '131056', '4' ], true ) ];
	}

	private function request( string $method, string $path, array $body = [] ): array {
		$response = wp_remote_request(
			'https://graph.facebook.com/' . rawurlencode( $this->version() ) . '/' . ltrim( $path, '/' ),
			[ 'method' => $method, 'timeout' => 15, 'headers' => [ 'Authorization' => 'Bearer ' . $this->token(), 'Content-Type' => 'application/json' ], 'body' => $body ? wp_json_encode( $body ) : null ]
		);
		if ( is_wp_error( $response ) ) {
			return [ 'status' => 0, 'json' => [ 'error' => [ 'message' => $response->get_error_message(), 'code' => 'network' ] ] ];
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return [ 'status' => (int) wp_remote_retrieve_response_code( $response ), 'json' => is_array( $json ) ? $json : [] ];
	}

	public function send( array $message ): array {
		if ( ! $this->configured() ) {
			return [ 'ok' => false, 'provider_message_id' => '', 'error_code' => 'not_configured', 'error_message' => 'WhatsApp credentials missing', 'retryable' => false ];
		}
		$template = (array) ( $message['template'] ?? [] );
		if ( empty( $template['name'] ) ) {
			return [ 'ok' => false, 'provider_message_id' => '', 'error_code' => 'no_template', 'error_message' => 'No Meta template mapped for this event', 'retryable' => false ];
		}
		$payload = self::payload( (string) $message['recipient'], (string) $template['name'], (string) ( $template['language'] ?? 'en' ), (array) ( $template['params'] ?? [] ), $message['document'] ?? null );
		$payload = (array) apply_filters( 'webgram_core/notifications/whatsapp_payload', $payload, $message );
		$r       = $this->request( 'POST', $this->setting( 'wa_phone_number_id' ) . '/messages', $payload );
		return self::parse_send_response( $r['status'], $r['json'] );
	}

	/** @return array{ok: bool, display_phone_number?: string, quality_rating?: string, verified_name?: string, error?: string} */
	public function test(): array {
		if ( ! $this->configured() ) {
			return [ 'ok' => false, 'error' => __( 'Phone Number ID or access token missing.', 'webgram-core' ) ];
		}
		$r = $this->request( 'GET', $this->setting( 'wa_phone_number_id' ) . '?fields=display_phone_number,quality_rating,verified_name' );
		if ( $r['status'] >= 400 || 0 === $r['status'] || isset( $r['json']['error'] ) ) {
			return [ 'ok' => false, 'error' => self::redact( (string) ( $r['json']['error']['message'] ?? ( 'HTTP ' . $r['status'] ) ) ) ];
		}
		return [ 'ok' => true, 'display_phone_number' => (string) ( $r['json']['display_phone_number'] ?? '' ), 'quality_rating' => (string) ( $r['json']['quality_rating'] ?? '' ), 'verified_name' => (string) ( $r['json']['verified_name'] ?? '' ) ];
	}

	/** @return array<int, array{name: string, language: string, status: string, category: string}>|\WP_Error */
	public function templates(): array|\WP_Error {
		$waba = $this->setting( 'wa_waba_id' );
		if ( '' === $waba || ! $this->configured() ) {
			return new \WP_Error( 'not_configured', __( 'WABA ID or credentials missing.', 'webgram-core' ) );
		}
		$r = $this->request( 'GET', $waba . '/message_templates?fields=name,status,language,category&limit=200' );
		if ( $r['status'] >= 400 || 0 === $r['status'] || isset( $r['json']['error'] ) ) {
			return new \WP_Error( 'meta', self::redact( (string) ( $r['json']['error']['message'] ?? ( 'HTTP ' . $r['status'] ) ) ) );
		}
		return self::parse_templates( $r['json'] );
	}

	/** Pure. */
	public static function parse_templates( array $json ): array {
		$out = [];
		foreach ( (array) ( $json['data'] ?? [] ) as $t ) {
			if ( is_array( $t ) && ! empty( $t['name'] ) ) {
				$out[] = [ 'name' => sanitize_key( (string) $t['name'] ), 'language' => (string) ( $t['language'] ?? 'en' ), 'status' => strtoupper( (string) ( $t['status'] ?? '' ) ), 'category' => strtoupper( (string) ( $t['category'] ?? '' ) ) ];
			}
		}
		return $out;
	}
}
