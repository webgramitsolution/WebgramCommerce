<?php
namespace Webgram\Core\Modules\Wishlist;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist. Scheduled for roadmap phase 4. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'wishlist';
	}

	public function name(): string {
		return __( 'Wishlist', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Save products for later, guest support with merge on login.', 'webgram-core' );
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
