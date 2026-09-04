<?php
namespace Webgram\Core\Modules\Integrations;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor and Gutenberg. Scheduled for roadmap phase 5. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'integrations';
	}

	public function name(): string {
		return __( 'Elementor and Gutenberg', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Registers Webgram widgets and blocks. Elementor stays optional.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [  ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 5;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
