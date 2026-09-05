<?php
namespace Webgram\Core\Admin;

use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class Notices {

	public function __construct( private Plugin $plugin ) {}

	public function register(): void {
		add_action( 'admin_notices', [ $this, 'render' ] );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->plugin->is_woocommerce_active() ) {
			$this->notice( 'warning', __( 'Webgram Core: WooCommerce is not active. Ecommerce modules (reviews, wishlist, invoices, notifications and others) stay disabled until WooCommerce is installed and activated.', 'webgram-core' ) );
		}

		if ( ! $this->plugin->crypto()->is_available() ) {
			$this->notice( 'error', __( 'Webgram Core: the PHP Sodium extension is disabled on this server. API credentials cannot be stored securely, so modules that need API keys will not save them. Ask your host to enable ext-sodium.', 'webgram-core' ) );
		}

		$theme_version = wp_get_theme()->get( 'Version' );
		$theme_name    = wp_get_theme()->get( 'TextDomain' );
		if ( 'webgram' === $theme_name && $theme_version && version_compare( (string) $theme_version, WEBGRAM_CORE_MIN_THEME_VERSION, '<' ) ) {
			$this->notice( 'warning', sprintf( /* translators: %s: placeholder value. */ __( 'Webgram Core %1$s works best with Webgram Theme %2$s or newer. Please update the theme.', 'webgram-core' ), WEBGRAM_CORE_VERSION, WEBGRAM_CORE_MIN_THEME_VERSION ) );
		}

		if ( get_transient( 'webgram_core_just_activated' ) ) {
			delete_transient( 'webgram_core_just_activated' );
			$this->notice(
				'success',
				sprintf(
					/* translators: %s: link to modules page */
					__( 'Webgram Core is active. Choose which features to enable on the %s screen.', 'webgram-core' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=' . ModulesPage::SLUG ) ) . '">' . esc_html__( 'Webgram Modules', 'webgram-core' ) . '</a>'
				)
			);
		}
	}

	private function notice( string $type, string $html ): void {
		printf( '<div class="notice notice-%s"><p>%s</p></div>', esc_attr( $type ), wp_kses_post( $html ) );
	}
}
