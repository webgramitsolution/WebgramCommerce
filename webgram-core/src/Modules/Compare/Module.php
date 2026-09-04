<?php
namespace Webgram\Core\Modules\Compare;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Compare. Scheduled for roadmap phase 4. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'compare';
	}

	public function name(): string {
		return __( 'Compare', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Side-by-side product comparison.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 4;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
