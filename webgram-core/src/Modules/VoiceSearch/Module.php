<?php
namespace Webgram\Core\Modules\VoiceSearch;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Voice Search. Scheduled for roadmap phase 6. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'voice_search';
	}

	public function name(): string {
		return __( 'Voice Search', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Microphone input for product search and the assistant.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 6;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
