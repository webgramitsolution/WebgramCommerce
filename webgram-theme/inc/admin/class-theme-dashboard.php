<?php
/**
 * Appearance > Webgram: status, recommended plugin, links. The bundled Core installer and demo importer are
 * added in Phase 8; this screen already tells the store owner what is missing.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Theme_Dashboard {

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ], 99 );
		add_action( 'admin_notices', [ self::class, 'core_notice' ] );
	}

	public static function menu(): void {
		add_submenu_page( Webgram_Settings_Page::MENU, __( 'System status', 'webgram' ), __( 'System status', 'webgram' ), 'edit_theme_options', 'webgram-status', [ self::class, 'render' ] );
	}

	public static function core_notice(): void {
		if ( webgram_has_core() || ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, [ 'dashboard', 'themes', 'webgram_page_webgram-status' ], true ) ) {
			printf(
				'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Webgram works out of the box. Install the Webgram Core plugin to unlock reviews, wishlist, reels, invoices, WhatsApp notifications and the AI assistant.', 'webgram' ),
				esc_url( admin_url( 'admin.php?page=webgram-status' ) ),
				esc_html__( 'Learn more', 'webgram' )
			);
		}
	}

	public static function render(): void {
		$checks = [
			[ __( 'PHP 8.1 or newer', 'webgram' ), version_compare( PHP_VERSION, '8.1', '>=' ), PHP_VERSION ],
			[ __( 'WooCommerce', 'webgram' ), class_exists( 'WooCommerce' ), defined( 'WC_VERSION' ) ? WC_VERSION : __( 'not installed', 'webgram' ) ],
			[ __( 'Webgram Core', 'webgram' ), webgram_has_core(), defined( 'WEBGRAM_CORE_VERSION' ) ? WEBGRAM_CORE_VERSION : __( 'not installed', 'webgram' ) ],
			[ __( 'Elementor (optional)', 'webgram' ), did_action( 'elementor/loaded' ) > 0, defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : __( 'not installed', 'webgram' ) ],
		];
		?>
		<div class="wrap wg-admin wg-dashboard">
			<div class="wg-admin__bar"><h1><?php echo esc_html( Webgram_Settings_Page::brand() ); ?> <span><?php esc_html_e( 'System status', 'webgram' ); ?></span> <small>v<?php echo esc_html( WEBGRAM_VERSION ); ?></small></h1></div>
			<h2><?php esc_html_e( 'System status', 'webgram' ); ?></h2>
			<table class="widefat striped" style="max-width:720px">
				<tbody>
				<?php foreach ( $checks as [ $label, $ok, $detail ] ) : ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><?php echo $ok ? '<span style="color:#1e7e34">&#10003;</span>' : '<span style="color:#b32d2e">&#10007;</span>'; ?></td>
						<td><?php echo esc_html( (string) $detail ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram' ) ); ?>"><?php esc_html_e( 'Theme Settings', 'webgram' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-header' ) ); ?>"><?php esc_html_e( 'Header Builder', 'webgram' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-footer' ) ); ?>"><?php esc_html_e( 'Footer Builder', 'webgram' ); ?></a>
				<?php if ( webgram_has_core() ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=webgram-core' ) ); ?>"><?php esc_html_e( 'Webgram Core modules', 'webgram' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}

Webgram_Theme_Dashboard::init();
