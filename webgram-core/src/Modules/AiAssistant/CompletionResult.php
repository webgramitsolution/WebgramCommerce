<?php
namespace Webgram\Core\Modules\AiAssistant;

defined( 'ABSPATH' ) || exit;

/** Normalized provider reply: text and/or tool calls [{id, name, arguments}], usage, error (never shown to visitors). */
final class CompletionResult {

	public function __construct(
		public string $text = '',
		public array $tool_calls = [],
		public array $usage = [],
		public string $error = '',
		public bool $refused = false,
		public array $raw = []
	) {}

	public function has_tool_calls(): bool {
		return [] !== $this->tool_calls;
	}

	public static function error( string $message ): self {
		return new self( '', [], [], $message );
	}
}
