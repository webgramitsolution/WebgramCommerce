<?php
/**
 * Webgram > Import / Export: JSON export of theme settings, header and footer layouts and Core settings; validated
 * import; global reset with confirmation.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Import_Export {

	public const SLUG = 'webgram-import-export';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 60 );
		add_action( 'admin_post_webgram_export', [ self::class, 'export' ] );
		add_action( 'admin_post_webgram_import', [ self::class, 'import' ] );
		add_action( 'admin_post_webgram_reset_all', [ self::class, 'reset_all' ] );
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'Import / Export', 'webgram' ), __( 'Import / Export', 'webgram' ), Webgram_Settings_Page::CAP, self::SLUG, [ self::class, 'render' ] );
	}

	/** @return array<string, mixed> */
	public static function build_export(): array {
		$data = [
			'product'        => 'webgram',
			'version'        => WEBGRAM_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'theme_settings' => Webgram_Settings::instance()->stored(),
			'header_layout'  => get_option( 'webgram_header_layout', [] ),
			'footer_layout'  => get_option( 'webgram_footer_layout', [] ),
			'core'           => [],
		];
		return (array) apply_filters( 'webgram/export_data', $data );
	}

	/**
	 * Validates and applies an import payload. Returns a list of applied sections. Pure enough for the harness.
	 *
	 * @return string[]
	 */
	public static function apply_import( array $data ): array {
		$applied = [];

		if ( isset( $data['theme_settings'] ) && is_array( $data['theme_settings'] ) ) {
			$fields = Webgram_Settings::instance()->theme_fields();
			$clean  = Webgram_Settings_Sanitizer::sanitize_all( $fields, $data['theme_settings'] );
			Webgram_Settings::instance()->update( $clean );
			$applied[] = 'theme_settings';
		}

		if ( isset( $data['header_layout'] ) && is_array( $data['header_layout'] ) && class_exists( 'Webgram_Header_Builder' ) ) {
			update_option( 'webgram_header_layout', Webgram_Header_Builder::instance()->sanitize( $data['header_layout'] ) );
			$applied[] = 'header_layout';
		}

		if ( isset( $data['footer_layout'] ) && is_array( $data['footer_layout'] ) && class_exists( 'Webgram_Footer_Builder' ) ) {
			update_option( 'webgram_footer_layout', Webgram_Footer_Builder::instance()->sanitize( $data['footer_layout'] ) );
			$applied[] = 'footer_layout';
		}

		if ( isset( $data['core'] ) && is_array( $data['core'] ) && $data['core'] ) {
			do_action( 'webgram/import_data', $data['core'] );
			$applied[] = 'core';
		}

		Webgram_Settings::instance()->flush_caches();
		return $applied;
	}

	public static function render(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			return;
		}
		$notice = isset( $_GET['wg_notice'] ) ? sanitize_key( wp_unslash( $_GET['wg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap wg-admin">
			<div class="wg-admin__bar"><h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php esc_html_e( 'Import / Export', 'webgram' ); ?></span></h1></div>

			<?php if ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings imported.', 'webgram' ); ?></p></div>
			<?php elseif ( 'invalid' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The file is not a valid Webgram export.', 'webgram' ); ?></p></div>
			<?php elseif ( 'reset' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All theme settings were reset.', 'webgram' ); ?></p></div>
			<?php endif; ?>

			<div class="wg-admin__cards">
				<div class="wg-admin__card">
					<h2><?php esc_html_e( 'Export', 'webgram' ); ?></h2>
					<p><?php esc_html_e( 'Downloads a JSON file with theme settings, header and footer layouts and Webgram Core settings when the plugin is active.', 'webgram' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="webgram_export">
						<?php wp_nonce_field( 'webgram_export' ); ?>
						<?php submit_button( __( 'Download export', 'webgram' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="wg-admin__card">
					<h2><?php esc_html_e( 'Import', 'webgram' ); ?></h2>
					<p><?php esc_html_e( 'Every value is validated against the field schema. Unknown keys are ignored.', 'webgram' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="webgram_import">
						<?php wp_nonce_field( 'webgram_import' ); ?>
						<p><input type="file" name="webgram_import_file" accept="application/json,.json" required></p>
						<?php submit_button( __( 'Import', 'webgram' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>

				<div class="wg-admin__card wg-admin__card--danger">
					<h2><?php esc_html_e( 'Reset everything', 'webgram' ); ?></h2>
					<p><?php esc_html_e( 'Removes all theme settings and header and footer layouts. Core settings are not touched.', 'webgram' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="webgram_reset_all">
						<?php wp_nonce_field( 'webgram_reset_all' ); ?>
						<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I understand this cannot be undone.', 'webgram' ); ?></label>
						<?php submit_button( __( 'Reset all theme settings', 'webgram' ), 'delete', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<?php do_action( 'webgram/import_export/after' ); ?>
		</div>
		<?php
	}

	public static function export(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_export' );

		$json = wp_json_encode( self::build_export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="webgram-export-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	public static function import(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_import' );

		$notice = 'invalid';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$file = $_FILES['webgram_import_file'] ?? null;
		if ( $file && empty( $file['error'] ) && is_uploaded_file( $file['tmp_name'] ) && (int) $file['size'] < 5 * MB_IN_BYTES ) {
			$data = json_decode( (string) file_get_contents( $file['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( is_array( $data ) && ( $data['product'] ?? '' ) === 'webgram' ) {
				self::apply_import( $data );
				$notice = 'imported';
			}
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'wg_notice' => $notice ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function reset_all(): void {
		if ( ! current_user_can( Webgram_Settings_Page::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( 'webgram_reset_all' );
		if ( empty( $_POST['confirm'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
		}
		Webgram_Settings::instance()->reset();
		delete_option( 'webgram_header_layout' );
		delete_option( 'webgram_footer_layout' );
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'wg_notice' => 'reset' ], admin_url( 'admin.php' ) ) );
		exit;
	}
}

Webgram_Import_Export::init();
