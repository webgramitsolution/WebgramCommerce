<?php
namespace Webgram\Core\Modules\AiAssistant\Providers;

use Webgram\Core\Modules\AiAssistant\CompletionResult;

defined( 'ABSPATH' ) || exit;

/** OpenAI Chat Completions API with function calling. */
final class OpenAiProvider extends AbstractHttpProvider {

	public const DEFAULT_MODEL = 'gpt-4o-mini';

	public function name(): string {
		return 'openai';
	}

	public function build_request( array $messages, array $tools, array $options ): array {
		$out = [];
		foreach ( $messages as $m ) {
			$row = [ 'role' => $m['role'], 'content' => (string) $m['content'] ];
			if ( 'assistant' === $m['role'] && ! empty( $m['tool_calls'] ) ) {
				$row['tool_calls'] = array_map( static fn( array $c ) => [ 'id' => (string) $c['id'], 'type' => 'function', 'function' => [ 'name' => (string) $c['name'], 'arguments' => wp_json_encode( (object) ( $c['arguments'] ?? [] ) ) ] ], array_values( (array) $m['tool_calls'] ) );
				if ( '' === $row['content'] ) {
					$row['content'] = null;
				}
			}
			if ( 'tool' === $m['role'] ) {
				$row['tool_call_id'] = (string) $m['tool_call_id'];
			}
			$out[] = $row;
		}
		$body = [
			'model'       => (string) ( $options['model'] ?? self::DEFAULT_MODEL ) ?: self::DEFAULT_MODEL,
			'messages'    => $out,
			'max_tokens'  => (int) ( $options['max_tokens'] ?? 1024 ),
			'temperature' => (float) ( $options['temperature'] ?? 0.3 ),
		];
		if ( $tools ) {
			$body['tools']       = array_map( static fn( array $t ) => [ 'type' => 'function', 'function' => [ 'name' => $t['name'], 'description' => (string) ( $t['description'] ?? '' ), 'parameters' => self::parameters( $t ) ] ], array_values( $tools ) );
			$body['tool_choice'] = 'auto';
		}
		return [ 'url' => 'https://api.openai.com/v1/chat/completions', 'headers' => [ 'Authorization' => 'Bearer ' . $this->api_key ], 'body' => $body ];
	}

	public function parse_response( array $json ): CompletionResult {
		$message = (array) ( $json['choices'][0]['message'] ?? [] );
		$calls   = [];
		foreach ( (array) ( $message['tool_calls'] ?? [] ) as $call ) {
			if ( is_array( $call ) && 'function' === ( $call['type'] ?? 'function' ) ) {
				$calls[] = [ 'id' => (string) ( $call['id'] ?? '' ), 'name' => (string) ( $call['function']['name'] ?? '' ), 'arguments' => self::arguments( $call['function']['arguments'] ?? '' ) ];
			}
		}
		$refused = 'content_filter' === ( $json['choices'][0]['finish_reason'] ?? '' );
		return new CompletionResult( trim( (string) ( $message['content'] ?? '' ) ), $calls, [ 'input' => (int) ( $json['usage']['prompt_tokens'] ?? 0 ), 'output' => (int) ( $json['usage']['completion_tokens'] ?? 0 ) ], $refused ? 'refusal' : '', $refused, [ 'finish_reason' => (string) ( $json['choices'][0]['finish_reason'] ?? '' ) ] );
	}
}
