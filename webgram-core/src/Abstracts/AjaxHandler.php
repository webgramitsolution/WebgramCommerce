<?php
namespace Webgram\Core\Abstracts;

use Webgram\Core\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Base for WC-AJAX and admin-ajax handlers.
 * Enforces nonce, optional login, optional capability, and sanitizes input through a declared map before handle() runs.
 *
 * Frontend endpoints are registered as wc_ajax_webgram_{action} (fast, no admin bootstrap) with an admin-ajax fallback
 * for sites without WooCommerce.
 */
abstract class AjaxHandler {

	/** Action slug without the webgram_ prefix. */
	abstract protected function action(): string;

	/** @return array<string, string> field => sanitizer type (see Sanitizer::apply). */
	abstract protected function fields(): array;

	/** @param array<string, mixed> $input sanitized fields */
	abstract protected function handle( array $input ): void;

	protected function requires_login(): bool {
		return false;
	}

	protected function capability(): string {
		return '';
	}

	protected function nonce_action(): string {
		return 'webgram_core_nonce';
	}

	public function register(): void {
		$action = 'webgram_' . $this->action();

		add_action( 'wc_ajax_' . $action, [ $this, 'run' ] );
		add_action( 'wp_ajax_' . $action, [ $this, 'run' ] );

		if ( ! $this->requires_login() ) {
			add_action( 'wp_ajax_nopriv_' . $action, [ $this, 'run' ] );
		}
	}

	public function run(): void {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $this->nonce_action() ) ) {
			$this->error( __( 'Your session has expired. Please refresh the page and try again.', 'webgram-core' ), 403 );
		}

		if ( $this->requires_login() && ! is_user_logged_in() ) {
			$this->error( __( 'Please log in to continue.', 'webgram-core' ), 401 );
		}

		if ( $this->capability() && ! current_user_can( $this->capability() ) ) {
			$this->error( __( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified above.
		$input = Sanitizer::apply( wp_unslash( $_REQUEST ), $this->fields() );

		try {
			$this->handle( $input );
		} catch ( \Throwable $e ) {
			webgram_core()->logger()->error( 'AJAX ' . $this->action() . ' failed', [ 'error' => $e->getMessage() ] );
			$this->error( __( 'Something went wrong. Please try again.', 'webgram-core' ), 500 );
		}
	}

	protected function success( array $data = [], string $message = '' ): void {
		wp_send_json(
			[
				'success' => true,
				'data'    => $data,
				'message' => $message,
			]
		);
	}

	protected function error( string $message, int $status = 400, array $data = [] ): void {
		wp_send_json(
			[
				'success' => false,
				'data'    => $data,
				'message' => $message,
			],
			$status
		);
	}
}
