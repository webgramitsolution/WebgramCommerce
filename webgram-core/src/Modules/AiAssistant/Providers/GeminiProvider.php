<?php
namespace Webgram\Core\Modules\AiAssistant\Providers;

use Webgram\Core\Modules\AiAssistant\CompletionResult;

defined( 'ABSPATH' ) || exit;

/** Google Gemini generateContent API with function declarations. */
final class GeminiProvider extends AbstractHttpProvider {

	public const DEFAULT_MODEL = 'gemini-2.0-flash';

	public function name(): string {
		return 'gemini';
	}

	public function build_request( array $messages, array $tools, array $options ): array {
		$system   = [];
		$contents = [];
		foreach ( $messages as $m ) {
			switch ( $m['role'] ) {
				case 'system':
					$system[] = (string) $m['content'];
					break;
				case 'user':
					$contents[] = [ 'role' => 'user', 'parts' => [ [ 'text' => (string) $m['content'] ] ] ];
					break;
				case 'assistant':
					$parts = [];
					if ( '' !== trim( (string) $m['content'] ) ) {
						$parts[] = [ 'text' => (string) $m['content'] ];
					}
					foreach ( (array) ( $m['tool_calls'] ?? [] ) as $call ) {
						$parts[] = [ 'functionCall' => [ 'name' => (string) $call['name'], 'args' => (object) ( $call['arguments'] ?? [] ) ] ];
					}
					if ( $parts ) {
						$contents[] = [ 'role' => 'model', 'parts' => $parts ];
					}
					break;
				case 'tool':
					$decoded    = json_decode( (string) $m['content'], true );
					$part       = [ 'functionResponse' => [ 'name' => (string) ( $m['name'] ?? '' ), 'response' => [ 'result' => is_array( $decoded ) ? $decoded : (string) $m['content'] ] ] ];
					$last       = count( $contents ) - 1;
					if ( $last >= 0 && 'user' === $contents[ $last ]['role'] && isset( $contents[ $last ]['parts'][0]['functionResponse'] ) ) {
						$contents[ $last ]['parts'][] = $part;
					} else {
						$contents[] = [ 'role' => 'user', 'parts' => [ $part ] ];
					}
					break;
			}
		}
		$model = (string) ( $options['model'] ?? self::DEFAULT_MODEL ) ?: self::DEFAULT_MODEL;
		$body  = [
			'contents'         => $contents,
			'generationConfig' => [ 'maxOutputTokens' => (int) ( $options['max_tokens'] ?? 1024 ), 'temperature' => (float) ( $options['temperature'] ?? 0.3 ) ],
		];
		if ( $system ) {
			$body['system_instruction'] = [ 'parts' => [ [ 'text' => implode( "\n\n", $system ) ] ] ];
		}
		if ( $tools ) {
			$body['tools'] = [ [ 'function_declarations' => array_map( static fn( array $t ) => [ 'name' => $t['name'], 'description' => (string) ( $t['description'] ?? '' ), 'parameters' => self::parameters( $t ) ], array_values( $tools ) ) ] ];
		}
		return [ 'url' => 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent', 'headers' => [ 'x-goog-api-key' => $this->api_key ], 'body' => $body ];
	}

	public function parse_response( array $json ): CompletionResult {
		$text  = '';
		$calls = [];
		$i     = 0;
		foreach ( (array) ( $json['candidates'][0]['content']['parts'] ?? [] ) as $part ) {
			if ( ! is_array( $part ) ) {
				continue;
			}
			if ( isset( $part['text'] ) ) {
				$text .= (string) $part['text'];
			} elseif ( isset( $part['functionCall'] ) ) {
				$calls[] = [ 'id' => 'call_' . ( ++$i ), 'name' => (string) ( $part['functionCall']['name'] ?? '' ), 'arguments' => self::arguments( $part['functionCall']['args'] ?? [] ) ];
			}
		}
		$reason  = (string) ( $json['candidates'][0]['finishReason'] ?? '' );
		$refused = in_array( $reason, [ 'SAFETY', 'BLOCKLIST', 'PROHIBITED_CONTENT' ], true ) || isset( $json['promptFeedback']['blockReason'] );
		return new CompletionResult( trim( $text ), $calls, [ 'input' => (int) ( $json['usageMetadata']['promptTokenCount'] ?? 0 ), 'output' => (int) ( $json['usageMetadata']['candidatesTokenCount'] ?? 0 ) ], $refused ? 'refusal' : '', $refused, [ 'finish_reason' => $reason ] );
	}
}
