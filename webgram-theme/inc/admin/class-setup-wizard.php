<?php
/**
 * Webgram > Setup wizard: one screen that installs everything the store needs after the theme is activated.
 *
 * Steps (each an AJAX call so progress is visible and a failure stops only that step):
 *   woocommerce  WooCommerce from wordpress.org (never bundled)
 *   core         Webgram Core from the zip bundled in the theme (plugins/webgram-core.zip)
 *   elementor    Elementor from wordpress.org (optional)
 *   child        Webgram Child from the zip bundled in the theme (plugins/webgram-child.zip), activated on request
 *   demo:*       the demo importer steps
 *
 * Every step checks install_plugins, activate_plugins or switch_themes plus a nonce. Downloads use the
 * WordPress plugin API and Plugin_Upgrader, so the site owner's own WordPress fetches the third party plugins.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Setup_Wizard {

	public const SLUG      = 'webgram-setup';
	public const ACTION    = 'webgram_setup_step';
	public const OPTION    = 'webgram_setup_state';
	public const CHILD_ZIP = 'plugins/webgram-child.zip';

	public static function init(): void {
		add_action( 'after_switch_theme', [ self::class, 'flag_redirect' ] );
		add_action( 'admin_init', [ self::class, 'maybe_redirect' ] );
		// After the top level Webgram menu (priority 10): a submenu registered before its parent gets the wrong page hook and WordPress denies access.
		add_action( 'admin_menu', [ self::class, 'menu' ], 98 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
		add_action( 'wp_ajax_' . self::ACTION, [ self::class, 'ajax' ] );
		add_action( 'admin_notices', [ self::class, 'notice' ] );
		// WooCommerce and Elementor try to open their own onboarding after activation; the wizard finishes first.
		add_filter( 'woocommerce_enable_setup_wizard', [ self::class, 'block_third_party_redirects' ] );
		add_filter( 'woocommerce_prevent_automatic_wizard_redirect', [ self::class, 'block_third_party_redirects' ] );
	}

	/** Steps in execution order for a set of choices. Pure, covered by the harness. */
	public static function plan( array $choices ): array {
		$steps = [ 'woocommerce', 'core' ];
		if ( ! empty( $choices['elementor'] ) ) {
			$steps[] = 'elementor';
		}
		if ( ! empty( $choices['child'] ) ) {
			$steps[] = 'child';
		}
		if ( ! empty( $choices['demo'] ) ) {
			foreach ( Webgram_Demo_Importer::STEPS as $demo ) {
				$steps[] = 'demo:' . $demo;
			}
		}
		return $steps;
	}

	public static function is_running(): bool {
		$state = (array) get_option( self::OPTION, [] );
		return ! empty( $state['running'] ) && (int) $state['running'] > time() - HOUR_IN_SECONDS;
	}

	public static function block_third_party_redirects( $value ) {
		return self::is_running() ? false : $value;
	}

	public static function flag_redirect(): void {
		if ( current_user_can( 'install_plugins' ) && empty( get_option( self::OPTION, [] )['completed'] ) && ! self::is_running() ) {
			set_transient( 'webgram_setup_redirect', 1, MINUTE_IN_SECONDS );
		}
	}

	public static function maybe_redirect(): void {
		if ( ! get_transient( 'webgram_setup_redirect' ) || wp_doing_ajax() || ( isset( $_GET['page'] ) && self::SLUG === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		delete_transient( 'webgram_setup_redirect' );
		if ( current_user_can( 'install_plugins' ) && ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
		}
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Setup wizard', 'webgram' ), __( 'Setup wizard', 'webgram' ), 'install_plugins', self::SLUG, [ self::class, 'render' ] );
	}

	public static function assets( string $hook ): void {
		if ( ! str_ends_with( $hook, '_' . self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'webgram-admin', WEBGRAM_URI . '/assets/admin/settings.css', [], webgram_asset_version( 'admin/settings.css' ) );
		wp_enqueue_script( 'webgram-setup', WEBGRAM_URI . '/assets/admin/setup.js', [], webgram_asset_version( 'admin/setup.js' ), true );
		wp_localize_script(
			'webgram-setup',
			'webgramSetup',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
				'i18n'    => [
					'running' => __( 'Working', 'webgram' ),
					'done'    => __( 'Done', 'webgram' ),
					'failed'  => __( 'Failed', 'webgram' ),
					'skipped' => __( 'Skipped', 'webgram' ),
					'finish'  => __( 'Setup complete. Your store is ready.', 'webgram' ),
					'stopped' => __( 'Setup stopped at a failed step. Fix the issue and run it again; finished steps are kept.', 'webgram' ),
				],
			]
		);
	}

	public static function notice(): void {
		$screen = get_current_screen();
		if ( ! current_user_can( 'install_plugins' ) || ! $screen || ! in_array( $screen->id, [ 'dashboard', 'themes' ], true ) || ! empty( get_option( self::OPTION, [] )['completed'] ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p>%s <a class="button button-primary button-small" href="%s">%s</a></p></div>',
			esc_html__( 'Webgram is active. Run the setup wizard to install WooCommerce, Webgram Core, Elementor and the child theme, then import the demo store.', 'webgram' ),
			esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
			esc_html__( 'Run setup wizard', 'webgram' )
		);
	}

	/** Current status of each installable, for the checklist. */
	public static function status(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$state   = static fn( string $file ): string => is_plugin_active( $file ) ? 'active' : ( isset( $plugins[ $file ] ) ? 'installed' : 'missing' );
		return [
			'woocommerce' => [ 'label' => 'WooCommerce', 'state' => $state( 'woocommerce/woocommerce.php' ), 'source' => __( 'wordpress.org', 'webgram' ) ],
			'core'        => [ 'label' => 'Webgram Core', 'state' => function_exists( 'webgram_core' ) ? 'active' : $state( Webgram_Plugin_Installer::BASENAME ), 'source' => Webgram_Plugin_Installer::has_bundle() ? __( 'bundled in the theme', 'webgram' ) : __( 'bundle missing', 'webgram' ) ],
			'elementor'   => [ 'label' => 'Elementor', 'state' => $state( 'elementor/elementor.php' ), 'source' => __( 'wordpress.org, optional', 'webgram' ) ],
			'child'       => [ 'label' => __( 'Webgram Child theme', 'webgram' ), 'state' => 'webgram-child' === get_stylesheet() ? 'active' : ( wp_get_theme( 'webgram-child' )->exists() ? 'installed' : 'missing' ), 'source' => is_readable( WEBGRAM_DIR . '/' . self::CHILD_ZIP ) ? __( 'bundled in the theme', 'webgram' ) : __( 'bundle missing', 'webgram' ) ],
		];
	}

	public static function render(): void {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			echo '<div class="wrap wg-admin"><div class="notice notice-error"><p>' . esc_html__( 'This site blocks plugin and theme installation (DISALLOW_FILE_MODS). Install WooCommerce, Webgram Core, Elementor and the child theme through your host, then use Webgram > Demo import.', 'webgram' ) . '</p></div></div>';
			return;
		}
		$status = self::status();
		$labels = [ 'active' => __( 'Active', 'webgram' ), 'installed' => __( 'Installed, not active', 'webgram' ), 'missing' => __( 'Not installed', 'webgram' ) ];
		$demo   = (array) get_option( Webgram_Demo_Importer::OPTION, [] );
		?>
		<div class="wrap wg-admin wg-setup" data-wg-setup>
			<div class="wg-admin__bar"><h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php esc_html_e( 'Setup wizard', 'webgram' ); ?></span></h1></div>
			<div class="wg-admin__card" style="max-width:820px">
				<p><?php esc_html_e( 'One click installs everything the store needs. WooCommerce and Elementor are downloaded from wordpress.org by your WordPress; Webgram Core and the child theme come from this theme package. Steps already done are skipped, so you can run the wizard again at any time.', 'webgram' ); ?></p>
				<table class="widefat striped" style="margin:16px 0">
					<thead><tr><th><?php esc_html_e( 'Component', 'webgram' ); ?></th><th><?php esc_html_e( 'Source', 'webgram' ); ?></th><th><?php esc_html_e( 'Status', 'webgram' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $status as $key => $row ) : ?>
						<tr data-wg-setup-row="<?php echo esc_attr( $key ); ?>">
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><?php echo esc_html( $row['source'] ); ?></td>
							<td data-wg-setup-status><?php echo esc_html( $labels[ $row['state'] ] ); ?></td>
						</tr>
					<?php endforeach; ?>
						<tr data-wg-setup-row="demo">
							<td><?php esc_html_e( 'Demo store', 'webgram' ); ?></td>
							<td><?php esc_html_e( 'products, pages, menus, slider, testimonials, coupons', 'webgram' ); ?></td>
							<td data-wg-setup-status><?php echo empty( $demo['pages'] ) ? esc_html__( 'Not imported', 'webgram' ) : esc_html__( 'Imported', 'webgram' ); ?></td>
						</tr>
					</tbody>
				</table>
				<p>
					<label><input type="checkbox" data-wg-setup-choice="elementor" checked> <?php esc_html_e( 'Install Elementor (optional page builder; every Webgram section also works as a Gutenberg block)', 'webgram' ); ?></label><br>
					<label><input type="checkbox" data-wg-setup-choice="child" checked> <?php esc_html_e( 'Install and activate the Webgram Child theme (keeps your customizations safe on updates)', 'webgram' ); ?></label><br>
					<label><input type="checkbox" data-wg-setup-choice="demo" checked> <?php esc_html_e( 'Import the demo store', 'webgram' ); ?></label>
				</p>
				<p>
					<button type="button" class="button button-primary button-hero" data-wg-setup-start><?php esc_html_e( 'Start setup', 'webgram' ); ?></button>
					<a class="button button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-status' ) ); ?>"><?php esc_html_e( 'Skip, I will install manually', 'webgram' ); ?></a>
				</p>
				<div class="wg-setup__log" data-wg-setup-log hidden aria-live="polite"></div>
				<div class="wg-setup__finish" data-wg-setup-finish hidden>
					<h2><?php esc_html_e( 'Next steps', 'webgram' ); ?></h2>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-admin&path=/setup-wizard' ) ); ?>"><?php esc_html_e( 'WooCommerce store details (address, currency, payments)', 'webgram' ); ?></a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram' ) ); ?>"><?php esc_html_e( 'Theme Settings', 'webgram' ); ?></a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-header' ) ); ?>"><?php esc_html_e( 'Header Builder', 'webgram' ); ?></a>
						<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank"><?php esc_html_e( 'View site', 'webgram' ); ?></a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	public static function ajax(): void {
		check_ajax_referer( self::ACTION, 'nonce' );
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to do this.', 'webgram' ) ], 403 );
		}
		$step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : '';
		$last = ! empty( $_POST['last'] );
		update_option( self::OPTION, [ 'running' => time() ] + (array) get_option( self::OPTION, [] ), false );
		set_time_limit( 300 );

		try {
			$result = self::run_step( $step );
		} catch ( \Throwable $e ) {
			$result = [ 'ok' => false, 'message' => $e->getMessage() ];
		}
		if ( $last || ! $result['ok'] ) {
			$state = (array) get_option( self::OPTION, [] );
			unset( $state['running'] );
			if ( $last && $result['ok'] ) {
				$state['completed'] = time();
			}
			update_option( self::OPTION, $state, false );
		}
		if ( $result['ok'] ) {
			wp_send_json_success( $result );
		}
		wp_send_json_error( $result );
	}

	/** @return array{ok: bool, message: string, state?: string} */
	private static function run_step( string $step ): array {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/theme.php';

		switch ( $step ) {
			case 'woocommerce':
				return self::wporg_plugin( 'woocommerce', 'woocommerce/woocommerce.php', 'WooCommerce' );
			case 'elementor':
				return self::wporg_plugin( 'elementor', 'elementor/elementor.php', 'Elementor' );
			case 'core':
				return self::core();
			case 'child':
				return self::child();
		}
		if ( str_starts_with( $step, 'demo:' ) ) {
			$demo = Webgram_Demo_Importer::normalize_steps( [ substr( $step, 5 ) ] );
			if ( ! $demo ) {
				return [ 'ok' => false, 'message' => __( 'Unknown demo step.', 'webgram' ) ];
			}
			if ( 'products' === $demo[0] && ! class_exists( 'WooCommerce' ) ) {
				return [ 'ok' => true, 'state' => 'skipped', 'message' => __( 'WooCommerce is not active, products skipped.', 'webgram' ) ];
			}
			if ( 'core' === $demo[0] && ! webgram_has_core() ) {
				return [ 'ok' => true, 'state' => 'skipped', 'message' => __( 'Webgram Core is not active, Core demo content skipped.', 'webgram' ) ];
			}
			$report = Webgram_Demo_Importer::run( $demo );
			return [ 'ok' => ! $report['errors'], 'message' => implode( ' ', array_merge( $report['lines'], $report['errors'] ) ) ];
		}
		return [ 'ok' => false, 'message' => __( 'Unknown step.', 'webgram' ) ];
	}

	/** Installs (when missing) and activates a plugin from wordpress.org through the WordPress plugin API. */
	private static function wporg_plugin( string $slug, string $file, string $label ): array {
		if ( is_plugin_active( $file ) ) {
			return [ 'ok' => true, 'state' => 'skipped', 'message' => sprintf( /* translators: %s: plugin name. */ __( '%s is already active.', 'webgram' ), $label ) ];
		}
		$installed = get_plugins();
		if ( ! isset( $installed[ $file ] ) ) {
			$api = plugins_api( 'plugin_information', [ 'slug' => $slug, 'fields' => [ 'sections' => false, 'short_description' => false ] ] );
			if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
				return [ 'ok' => false, 'message' => sprintf( /* translators: 1: plugin name, 2: error. */ __( '%1$s could not be fetched from wordpress.org: %2$s. Install it from Plugins > Add New and run the wizard again.', 'webgram' ), $label, is_wp_error( $api ) ? $api->get_error_message() : __( 'no download link', 'webgram' ) ) ];
			}
			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$done     = $upgrader->install( $api->download_link );
			if ( true !== $done ) {
				$errors = $skin->get_errors();
				return [ 'ok' => false, 'message' => sprintf( /* translators: 1: plugin name, 2: error. */ __( '%1$s could not be installed: %2$s', 'webgram' ), $label, $errors instanceof WP_Error && $errors->has_errors() ? $errors->get_error_message() : __( 'unknown error', 'webgram' ) ) ];
			}
			wp_clean_plugins_cache( true );
		}
		$activated = activate_plugin( $file );
		if ( is_wp_error( $activated ) ) {
			return [ 'ok' => false, 'message' => $activated->get_error_message() ];
		}
		delete_transient( '_wc_activation_redirect' );
		delete_transient( 'elementor_activation_redirect' );
		return [ 'ok' => true, 'state' => 'done', 'message' => sprintf( /* translators: %s: plugin name. */ __( '%s installed and activated.', 'webgram' ), $label ) ];
	}

	private static function core(): array {
		$state = Webgram_Plugin_Installer::state();
		if ( 'current' === $state ) {
			return [ 'ok' => true, 'state' => 'skipped', 'message' => __( 'Webgram Core is already active.', 'webgram' ) ];
		}
		if ( 'no_bundle' === $state ) {
			return [ 'ok' => false, 'message' => __( 'The bundled Webgram Core zip is missing from the theme folder.', 'webgram' ) ];
		}
		$result = Webgram_Plugin_Installer::run( $state );
		if ( in_array( $result, [ 'installed', 'updated' ], true ) ) {
			return [ 'ok' => true, 'state' => 'done', 'message' => __( 'Webgram Core installed and activated.', 'webgram' ) ];
		}
		$detail = (string) get_transient( 'webgram_core_install_error' );
		delete_transient( 'webgram_core_install_error' );
		return [ 'ok' => false, 'message' => trim( __( 'Webgram Core could not be installed.', 'webgram' ) . ' ' . $detail ) ];
	}

	private static function child(): array {
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'switch_themes' ) ) {
			return [ 'ok' => false, 'message' => __( 'You cannot install themes on this site.', 'webgram' ) ];
		}
		if ( ! wp_get_theme( 'webgram-child' )->exists() ) {
			$zip = WEBGRAM_DIR . '/' . self::CHILD_ZIP;
			if ( ! is_readable( $zip ) ) {
				return [ 'ok' => false, 'message' => __( 'The bundled child theme zip is missing from the theme folder.', 'webgram' ) ];
			}
			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Theme_Upgrader( $skin );
			$done     = $upgrader->install( $zip );
			if ( true !== $done ) {
				$errors = $skin->get_errors();
				return [ 'ok' => false, 'message' => sprintf( /* translators: %s: error. */ __( 'The child theme could not be installed: %s', 'webgram' ), $errors instanceof WP_Error && $errors->has_errors() ? $errors->get_error_message() : __( 'unknown error', 'webgram' ) ) ];
			}
			wp_clean_themes_cache();
		}
		if ( 'webgram-child' !== get_stylesheet() ) {
			// Switching keeps the wizard state, so after_switch_theme does not restart the redirect.
			switch_theme( 'webgram-child' );
		}
		return [ 'ok' => true, 'state' => 'done', 'message' => __( 'Webgram Child installed and activated.', 'webgram' ) ];
	}
}

Webgram_Setup_Wizard::init();
