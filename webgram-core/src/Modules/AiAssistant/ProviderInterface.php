<?php
namespace Webgram\Core\Modules\AiAssistant;

defined( 'ABSPATH' ) || exit;

/**
 * An LLM provider. Messages use the canonical shape:
 *   [ 'role' => 'system'|'user'|'assistant'|'tool', 'content' => string,
 *     'tool_calls' => [ [ 'id', 'name', 'arguments' => array ] ] (assistant only),
 *     'tool_call_id' => string, 'name' => string (tool only) ]
 * Tools use the canonical schema: [ 'name', 'description', 'parameters' => JSON schema ].
 */
interface ProviderInterface {

	public function name(): string;

	public function supports_tools(): bool;

	/** @param array<int, array> $messages @param array<int, array> $tools @param array<string, mixed> $options model, max_tokens, temperature */
	public function complete( array $messages, array $tools, array $options ): CompletionResult;
}
