<?php
namespace Webgram\Core\Modules;

use Webgram\Core\Abstracts\Module;
use Webgram\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers, instantiates, and boots modules. Enable/disable state lives in the webgram_core_modules option.
 *
 * Third parties add modules with:
 *   add_filter( 'webgram_core/modules', fn( $m ) => $m + [ 'my_module' => My\Module::class ] );
 */
final class ModuleManager {

	/** @var array<string, Module> */
	private array $modules = [];

	/** @var array<string, bool> */
	private array $active = [];

	/** @var array<string, string> id => reason */
	private array $blocked = [];

	private bool $booted = false;

	public function __construct( private Container $container ) {}

	/** @return array<string, class-string<Module>> */
	private function definitions(): array {
		$builtin = [
			'woo_enhancements' => WooEnhancements\Module::class,
			'badges'           => Badges\Module::class,
			'quick_view'       => QuickView\Module::class,
			'wishlist'         => Wishlist\Module::class,
			'compare'          => Compare\Module::class,
			'coupons'          => Coupons\Module::class,
			'reviews'          => Reviews\Module::class,
			'slider'           => Slider\Module::class,
			'reels'            => Reels\Module::class,
			'instagram'        => Instagram\Module::class,
			'voice_search'     => VoiceSearch\Module::class,
			'ai_assistant'     => AiAssistant\Module::class,
			'invoice'          => Invoice\Module::class,
			'emails'           => Emails\Module::class,
			'notifications'    => Notifications\Module::class,
			'analytics'        => Analytics\Module::class,
			'integrations'     => Integrations\Module::class,
			'site_tools'       => SiteTools\Module::class,
		];

		return (array) apply_filters( 'webgram_core/modules', $builtin );
	}

	private function load(): void {
		if ( $this->modules ) {
			return;
		}

		$plugin = \webgram_core();
		$states = (array) get_option( 'webgram_core_modules', [] );

		foreach ( $this->definitions() as $id => $class ) {
			if ( ! class_exists( $class ) || ! is_subclass_of( $class, Module::class ) ) {
				continue;
			}
			$module = new $class( $plugin );
			$id     = $module->id();

			$this->modules[ $id ] = $module;
			$this->active[ $id ]  = array_key_exists( $id, $states ) ? (bool) $states[ $id ] : $module->default_enabled();
		}
	}

	public function boot_all(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		$this->load();

		foreach ( $this->modules as $id => $module ) {
			if ( ! $this->active[ $id ] ) {
				continue;
			}
			if ( ! $module->is_implemented() ) {
				$this->active[ $id ] = false; // Stubs are never reported as active, whatever the saved toggle says.
				continue;
			}
			$missing = $this->missing_dependencies( $id );
			if ( $missing ) {
				$this->active[ $id ]  = false;
				$this->blocked[ $id ] = implode( ', ', $missing );
				continue;
			}
			if ( ! apply_filters( 'webgram_core/' . $id . '/enabled', true ) ) {
				$this->active[ $id ] = false;
				continue;
			}

			$module->boot();
			add_action( 'wp_enqueue_scripts', [ $module, 'register_assets' ], 6 );
			add_action( 'admin_enqueue_scripts', [ $module, 'register_assets' ], 6 );
		}

		do_action( 'webgram_core/loaded', \webgram_core() );
	}

	/** @return string[] unmet dependency ids */
	private function missing_dependencies( string $id ): array {
		$missing = [];
		foreach ( $this->modules[ $id ]->dependencies() as $dep ) {
			$ok = match ( $dep ) {
				'woocommerce' => class_exists( 'WooCommerce' ),
				'elementor'   => did_action( 'elementor/loaded' ) > 0 || class_exists( '\Elementor\Plugin' ),
				default       => isset( $this->modules[ $dep ] ) && $this->active[ $dep ] && ! $this->missing_dependencies( $dep ),
			};
			if ( ! $ok ) {
				$missing[] = $dep;
			}
		}
		return $missing;
	}

	/** @return array<string, Module> */
	public function all(): array {
		$this->load();
		return $this->modules;
	}

	public function get( string $id ): ?Module {
		$this->load();
		return $this->modules[ $id ] ?? null;
	}

	public function is_active( string $id ): bool {
		$this->load();
		return ! empty( $this->active[ $id ] );
	}

	public function is_enabled_in_settings( string $id ): bool {
		$this->load();
		$states = (array) get_option( 'webgram_core_modules', [] );
		return array_key_exists( $id, $states ) ? (bool) $states[ $id ] : ( isset( $this->modules[ $id ] ) && $this->modules[ $id ]->default_enabled() );
	}

	public function blocked_reason( string $id ): string {
		return $this->blocked[ $id ] ?? '';
	}

	/** @param array<string, bool> $states */
	public function save_states( array $states ): void {
		$this->load();
		$clean = [];
		foreach ( $this->modules as $id => $module ) {
			$clean[ $id ] = ! empty( $states[ $id ] );
			if ( $clean[ $id ] && ! $this->is_enabled_in_settings( $id ) ) {
				$module->activate();
			}
		}
		update_option( 'webgram_core_modules', $clean );
		do_action( 'webgram_core/modules_saved', $clean );
	}

	public function activate_all(): void {
		$this->load();
		foreach ( $this->modules as $id => $module ) {
			if ( $this->active[ $id ] && $module->is_implemented() ) {
				$module->activate();
			}
		}
	}

	public function uninstall_all(): void {
		$this->load();
		foreach ( $this->modules as $module ) {
			$module->uninstall();
		}
	}
}
