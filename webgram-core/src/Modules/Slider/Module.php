<?php
namespace Webgram\Core\Modules\Slider;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Slider. Scheduled for roadmap phase 5. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'slider';
	}

	public function name(): string {
		return __( 'Slider', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Responsive hero slider with per-device images, overlays, CTAs and animations.', 'webgram-core' );
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
