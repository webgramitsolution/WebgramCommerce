<?php
namespace Webgram\Core\Modules\Badges;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Product Badges. Scheduled for roadmap phase 2. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'badges';
	}

	public function name(): string {
		return __( 'Product Badges', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Rule-based badges: new arrivals, sale percentage, best seller, custom text.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 2;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
