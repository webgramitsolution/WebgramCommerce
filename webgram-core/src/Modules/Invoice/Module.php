<?php
namespace Webgram\Core\Modules\Invoice;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Invoices. Scheduled for roadmap phase 7. This file registers the module with the manager
 * so the Modules screen, dependency graph and settings tabs are stable from the first release.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'invoice';
	}

	public function name(): string {
		return __( 'Invoices', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Professional PDF invoices with configurable numbering, admin and customer downloads.', 'webgram-core' );
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
