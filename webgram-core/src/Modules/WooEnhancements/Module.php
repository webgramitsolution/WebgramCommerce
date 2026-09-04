<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Enhancements. Scheduled for roadmap phase 2. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'woo_enhancements';
	}

	public function name(): string {
		return __( 'WooCommerce Enhancements', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Buy Now, recently viewed, pincode delivery check, specifications table, bulk inquiry, contact seller and track order.', 'webgram-core' );
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
