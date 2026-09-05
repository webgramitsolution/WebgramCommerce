<?php
namespace Webgram\Core\Modules\AiAssistant\Providers;

use Webgram\Core\Modules\AiAssistant\CompletionResult;
use Webgram\Core\Modules\AiAssistant\ProviderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Shared HTTP plumbing: wp_remote_post with a 20 second timeout, JSON decoding, response shape validation and
 * error logging without the key. Subclasses implement the pure request builder and response parser so both can
 * be unit tested without network access.
 */
abstract class AbstractHttpProvider implements ProviderInterface {

	public function __construct( protected string $api_key ) {}

	/** Pure: [ 'url' => string, 'headers' => array, 'body' => array ] */
	abstract public function build_request( array $messages, array $tools, array $options ): array;

	/** Pure. */
	abstract public function parse_response( array $json ): CompletionResult;

	public function supports_tools(): bool {
		return true;
	}

	public function complete( array $messages, array $tools, array $options ): CompletionResult {
		if ( '' === $this->api_key ) {
			return CompletionResult::error( 'missing_api_key' );
		}
		$request  = $this->build_request( $messages, $tools, $options );
		$request  = (array) apply_filters( 'webgram_core/ai/request', $request, $this->name(), $options );
		$response = wp_remote_post(
			$request['url'],
			[
				'timeout' => (int) apply_filters( 'webgram_core/ai/timeout', 20 ),
				'headers' => array_merge( [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ], (array) $request['headers'] ),
				'body'    => wp_json_encode( $request['body'] ),
			]
		);
		if ( is_wp_error( $response ) ) {
			\webgram_core()->logger()->error( 'AI provider request failed', [ 'provider' => $this->name(), 'error' => $response->get_error_message() ] );
			return CompletionResult::error( 'network' );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $json ) ) {
			\webgram_core()->logger()->error( 'AI provider returned invalid JSON', [ 'provider' => $this->name(), 'status' => $code ] );
			return CompletionResult::error( 'invalid_response' );
		}
		if ( $code >= 400 ) {
			$message = (string) ( $json['error']['message'] ?? $json['message'] ?? ( 'HTTP ' . $code ) );
			\webgram_core()->logger()->error( 'AI provider error', [ 'provider' => $this->name(), 'status' => $code, 'message' => $message ] );
			return CompletionResult::error( 429 === $code ? 'rate_limited' : 'provider_error' );
		}
		return $this->parse_response( $json );
	}

	/** Canonical tool schema to JSON schema parameters (shared by providers). */
	protected static function parameters( array $tool ): array {
		$params = (array) ( $tool['parameters'] ?? [] );
		if ( empty( $params['type'] ) ) {
			$params = [ 'type' => 'object', 'properties' => new \stdClass(), 'required' => [] ];
		}
		if ( isset( $params['properties'] ) && [] === $params['properties'] ) {
			$params['properties'] = new \stdClass();
		}
		return $params;
	}

	/** Decode tool arguments from a JSON string or array. */
	protected static function arguments( mixed $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}
		$decoded = json_decode( (string) $value, true );
		return is_array( $decoded ) ? $decoded : [];
	}
}
