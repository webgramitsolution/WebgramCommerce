<?php
namespace Webgram\Core\Modules\Emails;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Branded Emails. Scheduled for roadmap phase 7. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'emails';
	}

	public function name(): string {
		return __( 'Branded Emails', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Customize WooCommerce email branding: logo, colors, header, footer, preview.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 7;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
