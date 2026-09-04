<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Site Tools. Scheduled for roadmap phase 1: Layouts, HTML Blocks, promo popup, age verification, cookie notice,
 * maintenance mode, white label, custom JS. Registered now so the Modules screen and settings tabs are stable.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'site_tools';
	}

	public function name(): string {
		return __( 'Site Tools', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Layouts, HTML Blocks, promo popup, age verification, cookie notice, maintenance mode, white label and custom JS.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 1;
	}

	public function is_implemented(): bool {
		return false;
	}

	public function boot(): void {}
}
