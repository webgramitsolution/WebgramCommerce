<?php
/**
 * Installs, updates and activates the bundled Webgram Core plugin from the theme's own zip.
 * Own implementation (not TGMPA): one admin-post action, capability install_plugins, nonce, Plugin_Upgrader.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Plugin_Installer {

	public const BUNDLE   = 'plugins/webgram-core.zip';
	public const MANIFEST = 'plugins/webgram-core.json';
	public const BASENAME = 'webgram-core/webgram-core.php';
	public const ACTION   = 'webgram_install_core';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, [ self::class, 'handle' ] );
		add_action( 'admin_notices', [ self::class, 'notice' ] );
	}

	public static function bundle_path(): string {
		return WEBGRAM_DIR . '/' . self::BUNDLE;
	}

	public static function has_bundle(): bool {
		return is_readable( self::bundle_path() );
	}

	/**
	 * Version of the zip shipped with the theme. Reads the manifest written by scripts/package.sh, then the zip header.
	 */
	public static function bundled_version(): string {
		$manifest = WEBGRAM_DIR . '/' . self::MANIFEST;
		if ( is_readable( $manifest ) ) {
			$data = json_decode( (string) file_get_contents( $manifest ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $data ) && ! empty( $data['version'] ) ) {
				return self::clean_version( (string) $data['version'] );
			}
		}
		if ( self::has_bundle() && class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();
			if ( true === $zip->open( self::bundle_path() ) ) {
				$header = (string) $zip->getFromName( self::BASENAME, 4096 );
				$zip->close();
				return self::parse_header_version( $header );
			}
		}
		return '';
	}

	/**
	 * Extracts "Version: x.y.z" from a plugin file header. Pure, covered by the harness.
	 */
	public static function parse_header_version( string $header ): string {
		if ( preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $header, $m ) ) {
			return self::clean_version( trim( $m[1] ) );
		}
		return '';
	}

	public static function clean_version( string $version ): string {
		return preg_replace( '/[^0-9A-Za-z.\-+]/', '', $version ) ?? '';
	}

	public static function installed_version(): string {
		if ( defined( 'WEBGRAM_CORE_VERSION' ) ) {
			return (string) WEBGRAM_CORE_VERSION;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		return isset( $plugins[ self::BASENAME ] ) ? self::clean_version( (string) $plugins[ self::BASENAME ]['Version'] ) : '';
	}

	/**
	 * Decides which action the dashboard offers. Pure, covered by the harness.
	 *
	 * @return string One of: no_bundle, install, activate, update, current.
	 */
	public static function resolve_state( bool $has_bundle, string $bundled, string $installed, bool $active ): string {
		if ( ! $has_bundle ) {
			return 'no_bundle';
		}
		if ( '' === $installed ) {
			return 'install';
		}
		if ( $bundled && version_compare( $bundled, $installed, '>' ) ) {
			return 'update';
		}
		return $active ? 'current' : 'activate';
	}

	public static function state(): string {
		return self::resolve_state( self::has_bundle(), self::bundled_version(), self::installed_version(), function_exists( 'webgram_core' ) );
	}

	public static function action_url( string $task ): string {
		return wp_nonce_url( add_query_arg( [ 'action' => self::ACTION, 'task' => $task ], admin_url( 'admin-post.php' ) ), self::ACTION );
	}

	/**
	 * Renders the install / update / activate button for the System status screen.
	 */
	public static function button(): string {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return '';
		}
		$labels = [
			'install'  => __( 'Install Webgram Core', 'webgram' ),
			'update'   => __( 'Update bundled Webgram Core', 'webgram' ),
			'activate' => __( 'Activate Webgram Core', 'webgram' ),
		];
		$state  = self::state();
		if ( ! isset( $labels[ $state ] ) ) {
			return '';
		}
		$version = self::bundled_version();
		return sprintf(
			'<a class="button button-primary" href="%s">%s</a>%s',
			esc_url( self::action_url( $state ) ),
			esc_html( $labels[ $state ] ),
			$version ? ' <small>' . esc_html( sprintf( /* translators: %s: version number. */ __( 'bundled version %s', 'webgram' ), $version ) ) . '</small>' : ''
		);
	}

	public static function handle(): void {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$task   = isset( $_GET['task'] ) ? sanitize_key( wp_unslash( $_GET['task'] ) ) : 'install';
		$result = self::run( $task );

		wp_safe_redirect( add_query_arg( [ 'page' => 'webgram-status', 'wg_core' => $result ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * @return string Result code shown by notice().
	 */
	public static function run( string $task ): string {
		if ( 'activate' !== $task ) {
			if ( ! self::has_bundle() ) {
				return 'no_bundle';
			}
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$done     = $upgrader->install( self::bundle_path(), [ 'overwrite_package' => 'update' === $task ] );

			if ( true !== $done ) {
				$errors = $skin->get_errors();
				if ( $errors instanceof WP_Error && $errors->has_errors() ) {
					set_transient( 'webgram_core_install_error', sanitize_text_field( $errors->get_error_message() ), MINUTE_IN_SECONDS );
				}
				return 'failed';
			}
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$activated = activate_plugin( self::BASENAME );
		if ( is_wp_error( $activated ) ) {
			set_transient( 'webgram_core_install_error', sanitize_text_field( $activated->get_error_message() ), MINUTE_IN_SECONDS );
			return 'failed';
		}
		return 'update' === $task ? 'updated' : 'installed';
	}

	public static function notice(): void {
		if ( ! isset( $_GET['wg_core'] ) || ! current_user_can( 'install_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$code     = sanitize_key( wp_unslash( $_GET['wg_core'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$messages = [
			'installed' => [ 'success', __( 'Webgram Core is installed and active.', 'webgram' ) ],
			'updated'   => [ 'success', __( 'Webgram Core was updated to the bundled version.', 'webgram' ) ],
			'no_bundle' => [ 'warning', __( 'The bundled Webgram Core zip is missing from the theme folder. Upload the plugin from the download package instead.', 'webgram' ) ],
			'failed'    => [ 'error', __( 'Webgram Core could not be installed.', 'webgram' ) ],
		];
		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}
		[ $type, $text ] = $messages[ $code ];
		$detail          = (string) get_transient( 'webgram_core_install_error' );
		delete_transient( 'webgram_core_install_error' );
		printf( '<div class="notice notice-%s is-dismissible"><p>%s%s</p></div>', esc_attr( $type ), esc_html( $text ), $detail ? ' ' . esc_html( $detail ) : '' );
	}
}

Webgram_Plugin_Installer::init();
