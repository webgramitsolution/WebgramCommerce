<?php
namespace Webgram\Core\Modules\AiAssistant;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * AI Shopping Assistant. Scheduled for roadmap phase 6. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'ai_assistant';
	}

	public function name(): string {
		return __( 'AI Shopping Assistant', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Ecommerce-aware chat assistant with product cards. Connect OpenAI, Gemini, Anthropic or run in rule-based mode.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return false;
	}

	public function phase(): int {
		return 6;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
