<?php
namespace Webgram\Core\Modules\AiAssistant\Providers;

use Webgram\Core\Modules\AiAssistant\CompletionResult;

defined( 'ABSPATH' ) || exit;

/**
 * Anthropic Messages API (POST /v1/messages). Adaptive thinking stays on (default on Claude Opus 5); effort is
 * kept low for chat; tool choice is always auto; refusal stop reasons are surfaced; server-side fallbacks are
 * enabled on Opus 5 and Fable models so a policy decline is rerun on the default fallback chain.
 */
final class AnthropicProvider extends AbstractHttpProvider {

	public const DEFAULT_MODEL = 'claude-opus-5';
	public const VERSION       = '2023-06-01';

	public function name(): string {
		return 'anthropic';
	}

	public function build_request( array $messages, array $tools, array $options ): array {
		$system   = [];
		$out      = [];
		foreach ( $messages as $m ) {
			switch ( $m['role'] ) {
				case 'system':
					$system[] = (string) $m['content'];
					break;
				case 'user':
					$out[] = [ 'role' => 'user', 'content' => (string) $m['content'] ];
					break;
				case 'assistant':
					$content = [];
					if ( '' !== trim( (string) $m['content'] ) ) {
						$content[] = [ 'type' => 'text', 'text' => (string) $m['content'] ];
					}
					foreach ( (array) ( $m['tool_calls'] ?? [] ) as $call ) {
						$content[] = [ 'type' => 'tool_use', 'id' => (string) $call['id'], 'name' => (string) $call['name'], 'input' => (object) ( $call['arguments'] ?? [] ) ];
					}
					if ( $content ) {
						$out[] = [ 'role' => 'assistant', 'content' => $content ];
					}
					break;
				case 'tool':
					$block = [ 'type' => 'tool_result', 'tool_use_id' => (string) $m['tool_call_id'], 'content' => (string) $m['content'] ];
					// All tool results for one assistant turn go into a single user message.
					$last = count( $out ) - 1;
					if ( $last >= 0 && 'user' === $out[ $last ]['role'] && is_array( $out[ $last ]['content'] ) && 'tool_result' === ( $out[ $last ]['content'][0]['type'] ?? '' ) ) {
						$out[ $last ]['content'][] = $block;
					} else {
						$out[] = [ 'role' => 'user', 'content' => [ $block ] ];
					}
					break;
			}
		}
		$model = (string) ( $options['model'] ?? self::DEFAULT_MODEL ) ?: self::DEFAULT_MODEL;
		$body  = [
			'model'         => $model,
			'max_tokens'    => (int) ( $options['max_tokens'] ?? 1024 ),
			'system'        => implode( "\n\n", $system ),
			'messages'      => $out,
			'output_config' => [ 'effort' => (string) ( $options['effort'] ?? 'low' ) ],
		];
		if ( $tools ) {
			$body['tools']       = array_map( static fn( array $t ) => [ 'name' => $t['name'], 'description' => (string) ( $t['description'] ?? '' ), 'input_schema' => self::parameters( $t ) ], array_values( $tools ) );
			$body['tool_choice'] = [ 'type' => 'auto' ];
		}
		$headers = [ 'x-api-key' => $this->api_key, 'anthropic-version' => self::VERSION ];
		if ( str_starts_with( $model, 'claude-opus-5' ) || str_starts_with( $model, 'claude-fable' ) ) {
			$headers['anthropic-beta'] = 'server-side-fallback-2026-07-01';
			$body['fallbacks']         = 'default';
		}
		return [ 'url' => 'https://api.anthropic.com/v1/messages', 'headers' => $headers, 'body' => $body ];
	}

	public function parse_response( array $json ): CompletionResult {
		$text  = '';
		$calls = [];
		foreach ( (array) ( $json['content'] ?? [] ) as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$text .= (string) ( $block['text'] ?? '' );
			} elseif ( 'tool_use' === ( $block['type'] ?? '' ) ) {
				$calls[] = [ 'id' => (string) ( $block['id'] ?? '' ), 'name' => (string) ( $block['name'] ?? '' ), 'arguments' => self::arguments( $block['input'] ?? [] ) ];
			}
		}
		$usage   = [ 'input' => (int) ( $json['usage']['input_tokens'] ?? 0 ), 'output' => (int) ( $json['usage']['output_tokens'] ?? 0 ) ];
		$refused = 'refusal' === ( $json['stop_reason'] ?? '' );
		return new CompletionResult( trim( $text ), $calls, $usage, $refused ? 'refusal' : '', $refused, [ 'stop_reason' => (string) ( $json['stop_reason'] ?? '' ), 'model' => (string) ( $json['model'] ?? '' ) ] );
	}
}
