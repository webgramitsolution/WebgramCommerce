<?php
namespace Webgram\Core\Abstracts;

use Webgram\Core\Plugin;
use Webgram\Core\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for every Core module.
 *
 * Lifecycle: constructed by ModuleManager on every request, boot() only when enabled and dependencies are satisfied.
 * Modules must not touch other modules' storage; cross-module calls go through webgram_core()->modules()->get( $id ).
 */
abstract class Module {

	protected Plugin $plugin;

	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/** Unique slug, lowercase with underscores: 'reviews', 'ai_assistant'. */
	abstract public function id(): string;

	/** Human readable name shown on the Modules screen. */
	abstract public function name(): string;

	/** One-line description shown on the Modules screen. */
	public function description(): string {
		return '';
	}

	/**
	 * Dependency ids. Accepts other module ids plus the virtual ids 'woocommerce' and 'elementor'.
	 *
	 * @return string[]
	 */
	public function dependencies(): array {
		return [];
	}

	public function default_enabled(): bool {
		return true;
	}

	/** Roadmap phase in which the module ships. Used only for admin display while a module is a stub. */
	public function phase(): int {
		return 0;
	}

	/** True when the module has real functionality. Stubs return false and are shown as "Coming soon". */
	public function is_implemented(): bool {
		return true;
	}

	/** Register hooks. Called only when enabled and dependencies are met. */
	abstract public function boot(): void;

	/** wp_register_* only. Enqueue where the component renders. Called on wp_enqueue_scripts and admin_enqueue_scripts. */
	public function register_assets(): void {}

	/**
	 * Settings schema for the module tab. Each field: id, label, type, default, description, options, sanitize.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function settings_fields(): array {
		return [];
	}

	/** Runs on plugin activation and when the module is first enabled. Must be idempotent. */
	public function activate(): void {}

	/** Runs on plugin uninstall when data removal is opted in. */
	public function uninstall(): void {}

	public function settings(): Settings {
		return $this->plugin->settings( $this->id() );
	}

	public function is_enabled(): bool {
		return $this->plugin->modules()->is_active( $this->id() );
	}

	/** Helper for module templates: templates/{module-dir}/{name}.php */
	protected function view( string $name, array $args = [], bool $echo = true ): string {
		return $this->plugin->view( str_replace( '_', '-', $this->id() ) . '/' . $name, $args, $echo );
	}
}
