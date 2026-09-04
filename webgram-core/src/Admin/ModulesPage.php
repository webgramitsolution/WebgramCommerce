<?php
namespace Webgram\Core\Admin;

use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Webgram > Modules: enable/disable toggles for every registered module.
 */
final class ModulesPage {

	public const SLUG = 'webgram-core';

	public function __construct( private Plugin $plugin ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 20 );
		add_action( 'admin_post_webgram_core_save_modules', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	/**
	 * Parent menu slug. When the active theme declares an admin menu in its webgram-core theme support, Core pages
	 * attach under it; otherwise Core owns its own top-level "Webgram" menu.
	 */
	public static function parent_slug(): string {
		$support = get_theme_support( 'webgram-core' );
		if ( is_array( $support ) && ! empty( $support[0]['admin_menu'] ) ) {
			return sanitize_key( (string) $support[0]['admin_menu'] );
		}
		return self::SLUG;
	}

	public static function theme_has_panel(): bool {
		$support = get_theme_support( 'webgram-core' );
		return is_array( $support ) && ! empty( $support[0]['settings_panel'] );
	}

	public function menu(): void {
		$parent = self::parent_slug();
		if ( self::SLUG === $parent ) {
			add_menu_page(
				__( 'Webgram', 'webgram-core' ),
				__( 'Webgram', 'webgram-core' ),
				'manage_options',
				self::SLUG,
				[ $this, 'render' ],
				'dashicons-layout',
				58
			);
		}
		add_submenu_page( $parent, __( 'Modules', 'webgram-core' ), __( 'Modules', 'webgram-core' ), 'manage_options', self::SLUG, [ $this, 'render' ] );
	}

	public function assets( string $hook ): void {
		if ( str_contains( $hook, self::SLUG ) ) {
			wp_enqueue_style( 'webgram-core-admin' );
			wp_enqueue_script( 'webgram-core-admin' );
		}
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manager = $this->plugin->modules();
		$modules = $manager->all();
		$phases  = [];
		foreach ( $modules as $id => $module ) {
			$phases[ $module->phase() ][ $id ] = $module;
		}
		ksort( $phases );
		?>
		<div class="wrap wgc-admin">
			<h1><?php esc_html_e( 'Webgram Core Modules', 'webgram-core' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Turn features on or off. Disabled modules load no code and no assets on the front end.', 'webgram-core' ); ?></p>

			<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Module settings saved.', 'webgram-core' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="webgram_core_save_modules">
				<?php wp_nonce_field( 'webgram_core_save_modules' ); ?>

				<div class="wgc-modules">
				<?php foreach ( $modules as $id => $module ) :
					$enabled     = $manager->is_enabled_in_settings( $id );
					$implemented = $module->is_implemented();
					$blocked     = $manager->blocked_reason( $id );
					?>
					<div class="wgc-module <?php echo $implemented ? '' : 'is-stub'; ?>">
						<div class="wgc-module__head">
							<label class="wgc-toggle">
								<input type="checkbox" name="modules[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( $enabled ); ?> <?php disabled( ! $implemented ); ?>>
								<span class="wgc-toggle__track"></span>
							</label>
							<h2><?php echo esc_html( $module->name() ); ?></h2>
						</div>
						<p><?php echo esc_html( $module->description() ); ?></p>
						<?php if ( ! $implemented ) : ?>
							<span class="wgc-badge wgc-badge--muted"><?php printf( esc_html__( 'Coming in phase %d', 'webgram-core' ), (int) $module->phase() ); ?></span>
						<?php elseif ( $blocked ) : ?>
							<span class="wgc-badge wgc-badge--warn"><?php printf( esc_html__( 'Needs: %s', 'webgram-core' ), esc_html( $blocked ) ); ?></span>
						<?php elseif ( $enabled ) : ?>
							<span class="wgc-badge wgc-badge--ok"><?php esc_html_e( 'Active', 'webgram-core' ); ?></span>
						<?php endif; ?>
						<?php if ( $module->settings_fields() && $implemented ) : ?>
							<a class="wgc-module__settings" href="<?php echo esc_url( admin_url( 'admin.php?page=' . SettingsPage::SLUG . '&tab=' . $id ) ); ?>"><?php esc_html_e( 'Settings', 'webgram-core' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save changes', 'webgram-core' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_core_save_modules' );

		$raw    = isset( $_POST['modules'] ) && is_array( $_POST['modules'] ) ? wp_unslash( $_POST['modules'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$states = [];
		foreach ( $raw as $id => $value ) {
			$states[ sanitize_key( (string) $id ) ] = ! empty( $value );
		}

		$this->plugin->modules()->save_states( $states );

		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
