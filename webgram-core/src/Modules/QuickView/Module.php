<?php
namespace Webgram\Core\Modules\QuickView;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Quick View. Scheduled for roadmap phase 2. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'quick_view';
	}

	public function name(): string {
		return __( 'Quick View', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Product summary in a modal from any product card.', 'webgram-core' );
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
