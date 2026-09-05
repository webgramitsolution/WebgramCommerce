<?php
namespace Webgram\Core;

use Webgram\Core\Modules\ModuleManager;
use Webgram\Core\Support\Assets;
use Webgram\Core\Support\Cache;
use Webgram\Core\Support\Crypto;
use Webgram\Core\Support\Logger;
use Webgram\Core\Support\Settings;
use Webgram\Core\Support\Template;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;
	private Container $container;
	private bool $booted = false;

	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	private function __construct() {
		$this->container = new Container();
		$this->register_services();
	}

	private function register_services(): void {
		$c = $this->container;

		$c->set( 'settings', static fn() => new Settings() );
		$c->set( 'cache', static fn() => new Cache() );
		$c->set( 'logger', static fn() => new Logger() );
		$c->set( 'crypto', static fn() => new Crypto() );
		$c->set( 'assets', static fn() => new Assets() );
		$c->set( 'template', static fn() => new Template() );
		$c->set( 'modules', static fn( Container $c ) => new ModuleManager( $c ) );
		$c->set( 'rest', static fn( Container $c ) => new Rest\Router( $c ) );
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'webgram-core', false, dirname( plugin_basename( WEBGRAM_CORE_FILE ) ) . '/languages' );

		( new Upgrader( $this ) )->maybe_upgrade();

		Compat\Hpos::declare();

		if ( is_admin() ) {
			( new Admin\ModulesPage( $this ) )->register();
			( new Admin\SettingsPage( $this ) )->register();
			( new Admin\Notices( $this ) )->register();
			( new Admin\PageSetup( $this ) )->register();
		}

		$this->assets()->register_hooks();

		// Module boot runs after all plugins are loaded so dependency checks see WooCommerce, Elementor, etc.
		add_action( 'plugins_loaded', fn() => $this->modules()->boot_all(), 20 );
		add_action( 'rest_api_init', fn() => $this->container->get( 'rest' )->register_routes() );
	}

	public function uninstall(): void {
		$this->modules()->uninstall_all();
		delete_option( 'webgram_core_modules' );
		delete_option( 'webgram_core_version' );
		delete_option( 'webgram_core_db_version' );
		delete_option( 'webgram_core_activated_at' );
		foreach ( wp_load_alloptions() as $name => $unused ) {
			if ( str_starts_with( $name, 'webgram_core_settings_' ) ) {
				delete_option( $name );
			}
		}
	}

	public function container(): Container {
		return $this->container;
	}

	public function modules(): ModuleManager {
		return $this->container->get( 'modules' );
	}

	public function settings( string $module = 'general' ): Settings {
		return $this->container->get( 'settings' )->for( $module );
	}

	public function cache(): Cache {
		return $this->container->get( 'cache' );
	}

	public function logger(): Logger {
		return $this->container->get( 'logger' );
	}

	public function crypto(): Crypto {
		return $this->container->get( 'crypto' );
	}

	public function assets(): Assets {
		return $this->container->get( 'assets' );
	}

	/**
	 * Render an overridable template. Themes override via {theme}/webgram-core/{path}.
	 */
	public function view( string $path, array $args = [], bool $echo = true ): string {
		return $this->container->get( 'template' )->render( $path, $args, $echo );
	}

	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	public function is_elementor_active(): bool {
		return did_action( 'elementor/loaded' ) > 0 || class_exists( '\Elementor\Plugin' );
	}
}
