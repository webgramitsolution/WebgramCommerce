<?php
/**
 * Webgram admin menu and the Theme Settings panel (WoodMart-style tab list, save bar, per-tab reset).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Settings_Page {

	public const MENU  = 'webgram';
	public const CAP   = 'edit_theme_options';
	public const NONCE = 'webgram_save_settings';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 9 );
		add_action( 'admin_post_webgram_save_settings', [ self::class, 'save' ] );
		add_action( 'admin_post_webgram_reset_settings', [ self::class, 'reset' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
	}

	/** Brand name, overridable by Core white label. */
	public static function brand(): string {
		$label = (array) apply_filters( 'webgram/white_label', [] );
		return ! empty( $label['name'] ) ? (string) $label['name'] : 'Webgram';
	}

	public static function menu(): void {
		$brand = self::brand();
		add_menu_page( $brand, $brand, self::CAP, self::MENU, [ self::class, 'render' ], 'dashicons-layout', 58 );
		add_submenu_page( self::MENU, __( 'Theme Settings', 'webgram' ), __( 'Theme Settings', 'webgram' ), self::CAP, self::MENU, [ self::class, 'render' ] );
	}

	public static function is_webgram_screen( string $hook ): bool {
		return str_contains( $hook, 'page_webgram' ) || 'toplevel_page_webgram' === $hook;
	}

	public static function assets( string $hook ): void {
		if ( ! self::is_webgram_screen( $hook ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'webgram-admin', WEBGRAM_URI . '/assets/admin/settings.css', [], webgram_asset_version( 'admin/settings.css' ) );
		wp_enqueue_script( 'webgram-admin', WEBGRAM_URI . '/assets/admin/settings.js', [ 'jquery', 'wp-color-picker' ], webgram_asset_version( 'admin/settings.js' ), true );

		$code = [];
		foreach ( [ 'css', 'javascript', 'htmlmixed' ] as $mode ) {
			$settings = wp_enqueue_code_editor( [ 'type' => 'text/' . ( 'javascript' === $mode ? 'javascript' : ( 'css' === $mode ? 'css' : 'html' ) ) ] );
			if ( $settings ) {
				$code[ $mode ] = $settings;
			}
		}

		wp_localize_script(
			'webgram-admin',
			'webgramAdmin',
			[
				'codeEditor' => $code,
				'i18n'       => [
					'unsaved'  => __( 'You have unsaved changes. Leave anyway?', 'webgram' ),
					'confirm'  => __( 'Reset these settings to their defaults? This cannot be undone.', 'webgram' ),
					'remove'   => __( 'Remove this item?', 'webgram' ),
					'choose'   => __( 'Choose image', 'webgram' ),
				],
			]
		);
	}

	public static function current_tab(): string {
		$tabs = Webgram_Settings::instance()->tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $tabs[ $tab ] ) ? $tab : (string) array_key_first( $tabs );
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$settings = Webgram_Settings::instance();
		$tabs     = $settings->tabs();
		$current  = self::current_tab();
		$tab      = $tabs[ $current ];
		$values   = $settings->tab_values( $current );
		$notice   = isset( $_GET['wg_notice'] ) ? sanitize_key( wp_unslash( $_GET['wg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap wg-admin wg-settings" data-wg-settings>
			<div class="wg-admin__bar">
				<h1><?php echo esc_html( self::brand() ); ?> <span><?php esc_html_e( 'Theme Settings', 'webgram' ); ?></span> <small>v<?php echo esc_html( WEBGRAM_VERSION ); ?></small></h1>
				<div class="wg-admin__bar-actions">
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View site', 'webgram' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-import-export' ) ); ?>"><?php esc_html_e( 'Import / Export', 'webgram' ); ?></a>
				</div>
			</div>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'webgram' ); ?></p></div>
			<?php elseif ( 'reset' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings reset to defaults.', 'webgram' ); ?></p></div>
			<?php endif; ?>

			<div class="wg-settings__layout">
				<nav class="wg-settings__tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'webgram' ); ?>">
					<?php foreach ( $tabs as $id => $t ) : ?>
						<a class="wg-settings__tab<?php echo $id === $current ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( [ 'page' => self::MENU, 'tab' => $id ], admin_url( 'admin.php' ) ) ); ?>">
							<?php echo webgram_icon( (string) ( $t['icon'] ?? 'settings' ), '', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( $t['label'] ); ?></span>
							<?php if ( 'core' === $t['owner'] ) : ?><em><?php esc_html_e( 'Core', 'webgram' ); ?></em><?php endif; ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<form class="wg-settings__content" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-wg-form>
					<input type="hidden" name="action" value="webgram_save_settings">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $current ); ?>">
					<?php wp_nonce_field( self::NONCE . '_' . $current ); ?>

					<header class="wg-settings__header">
						<h2><?php echo esc_html( $tab['label'] ); ?></h2>
						<?php if ( ! empty( $tab['description'] ) ) : ?>
							<p><?php echo wp_kses_post( $tab['description'] ); ?></p>
						<?php endif; ?>
					</header>

					<?php do_action( 'webgram/settings/tab_before', $current ); ?>

					<?php foreach ( $tab['sections'] as $section_id => $section ) : ?>
						<section class="wg-settings__section" id="wg-section-<?php echo esc_attr( $section_id ); ?>">
							<?php if ( ! empty( $section['label'] ) ) : ?>
								<h3 class="wg-settings__section-title"><?php echo esc_html( $section['label'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $section['description'] ) ) : ?>
								<p class="wg-settings__intro"><?php echo wp_kses_post( $section['description'] ); ?></p>
							<?php endif; ?>
							<?php
							foreach ( (array) ( $section['fields'] ?? [] ) as $id => $field ) {
								$field['id'] = $id;
								Webgram_Settings_Fields::render( $field, $values[ $id ] ?? ( $field['default'] ?? '' ) );
							}
							?>
						</section>
					<?php endforeach; ?>

					<?php do_action( 'webgram/settings/tab_after', $current ); ?>

					<div class="wg-settings__savebar">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save changes', 'webgram' ); ?></button>
						<button type="submit" class="button button-link-delete" formaction="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" name="action" value="webgram_reset_settings" data-wg-confirm><?php esc_html_e( 'Reset this tab', 'webgram' ); ?></button>
						<span class="wg-settings__unsaved" hidden><?php esc_html_e( 'Unsaved changes', 'webgram' ); ?></span>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	public static function save(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( self::NONCE . '_' . $tab );

		$settings = Webgram_Settings::instance();
		$fields   = $settings->fields( $tab );
		if ( ! $fields ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'webgram' ) );
		}

		$raw   = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$clear = isset( $_POST['settings__clear'] ) && is_array( $_POST['settings__clear'] ) ? array_map( 'boolval', wp_unslash( $_POST['settings__clear'] ) ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw   = self::strip_none_markers( $raw );

		// Custom JS and raw HTML need unfiltered_html on top of the panel capability.
		foreach ( $fields as $id => $field ) {
			if ( 'code' === ( $field['type'] ?? '' ) && 'javascript' === ( $field['language'] ?? '' ) && ! current_user_can( 'unfiltered_html' ) ) {
				unset( $raw[ $id ] );
			}
		}

		$clean = Webgram_Settings_Sanitizer::sanitize_all( $fields, $raw );

		// Secrets: empty input means keep; explicit clear removes.
		foreach ( $fields as $id => $field ) {
			if ( 'secret' === ( $field['type'] ?? '' ) ) {
				if ( ! empty( $clear[ $id ] ) ) {
					$clean[ $id ] = '';
				} elseif ( '' === trim( (string) ( $raw[ $id ] ?? '' ) ) ) {
					unset( $clean[ $id ] );
				}
			}
		}

		$clean = (array) apply_filters( 'webgram/settings/before_save', $clean, $tab );
		$settings->save_tab( $tab, $clean );

		wp_safe_redirect( add_query_arg( [ 'page' => self::MENU, 'tab' => $tab, 'wg_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function reset(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_admin_referer( self::NONCE . '_' . $tab );

		$settings = Webgram_Settings::instance();
		$def      = $settings->tab( $tab );
		if ( $def ) {
			if ( ! empty( $def['reset'] ) && is_callable( $def['reset'] ) ) {
				call_user_func( $def['reset'] );
			} else {
				$settings->reset( array_keys( $settings->fields( $tab ) ) );
			}
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::MENU, 'tab' => $tab, 'wg_notice' => 'reset' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Multicheck, sortable and repeater inputs post a "__none" marker so an empty selection still arrives. */
	public static function strip_none_markers( array $raw ): array {
		foreach ( $raw as $key => $value ) {
			if ( is_array( $value ) ) {
				unset( $value['__none'] );
				$raw[ $key ] = self::strip_none_markers( $value );
			}
		}
		return $raw;
	}
}

Webgram_Settings_Page::init();
