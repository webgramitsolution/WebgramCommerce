<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Asset registration helpers. Registration happens on every request (cheap); enqueueing happens only where a
 * component renders, so a page loads only the module assets it uses.
 */
final class Assets {

	private bool $frontend_data_printed = false;

	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin' ], 5 );
	}

	public function register_frontend(): void {
		$this->style( 'webgram-core-base', 'css/base.css' );
		$this->script( 'webgram-core-base', 'js/base.js', [], true );

		do_action( 'webgram_core/register_assets', $this );
	}

	public function register_admin(): void {
		$this->style( 'webgram-core-admin', 'admin/admin.css' );
		$this->script( 'webgram-core-admin', 'admin/admin.js', [ 'wp-i18n' ], true );

		do_action( 'webgram_core/register_admin_assets', $this );
	}

	public function style( string $handle, string $relative, array $deps = [], string $media = 'all' ): void {
		$file = WEBGRAM_CORE_PATH . 'assets/' . $relative;
		if ( is_readable( $file ) ) {
			wp_register_style( $handle, WEBGRAM_CORE_URL . 'assets/' . $relative, $deps, $this->version( $file ), $media );
		}
	}

	public function script( string $handle, string $relative, array $deps = [], bool $in_footer = true ): void {
		$file = WEBGRAM_CORE_PATH . 'assets/' . $relative;
		if ( is_readable( $file ) ) {
			wp_register_script(
				$handle,
				WEBGRAM_CORE_URL . 'assets/' . $relative,
				$deps,
				$this->version( $file ),
				[ 'in_footer' => $in_footer, 'strategy' => 'defer' ]
			);
		}
	}

	/**
	 * Enqueue the shared front-end bundle plus the localized data object. Safe to call many times; prints once.
	 * Modules call this from their renderer before enqueuing their own handle.
	 */
	public function enqueue_base(): void {
		wp_enqueue_script( 'webgram-core-base' );

		if ( ! $this->theme_provides_styles() ) {
			wp_enqueue_style( 'webgram-core-base' ); // Theme did not declare Core styling support; load fallback CSS.
		}

		if ( $this->frontend_data_printed ) {
			return;
		}
		$this->frontend_data_printed = true;

		$data = [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'wcAjax'   => class_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
			'restUrl'  => esc_url_raw( rest_url( 'webgram/v1/' ) ),
			'nonce'    => wp_create_nonce( 'webgram_core_nonce' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'i18n'     => [
				'error'   => __( 'Something went wrong. Please try again.', 'webgram-core' ),
				'loading' => __( 'Loading', 'webgram-core' ),
			],
		];
		wp_localize_script( 'webgram-core-base', 'webgramCore', (array) apply_filters( 'webgram_core/frontend_data', $data ) );
	}

	/** Enqueue a module's own assets. Handles follow webgram-core-{module}. */
	public function enqueue_module( string $module ): void {
		$this->enqueue_base();
		$handle = 'webgram-core-' . str_replace( '_', '-', $module );
		if ( ! $this->theme_provides_styles() ) {
			wp_enqueue_style( $handle );
		}
		wp_enqueue_script( $handle );
	}

	public function theme_provides_styles(): bool {
		$support = get_theme_support( 'webgram-core' );
		return is_array( $support ) && ! empty( $support[0]['styles'] );
	}

	private function version( string $file ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return (string) filemtime( $file );
		}
		return WEBGRAM_CORE_VERSION;
	}
}
