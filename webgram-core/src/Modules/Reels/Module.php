<?php
namespace Webgram\Core\Modules\Reels;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Shoppable Reels. Scheduled for roadmap phase 6. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'reels';
	}

	public function name(): string {
		return __( 'Shoppable Reels', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Vertical short videos with attached products and add to cart.', 'webgram-core' );
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
