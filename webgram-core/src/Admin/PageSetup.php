<?php
namespace Webgram\Core\Admin;

use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Modules that need a page (wishlist, compare) never create it silently. They describe it through the
 * webgram_core/page_setup/pages filter; this class shows one notice per missing page with a "Create page" button
 * that inserts the page with the shortcode and stores its id in the module settings.
 */
final class PageSetup {

	public function __construct( private Plugin $plugin ) {}

	public function register(): void {
		add_action( 'admin_notices', [ $this, 'notices' ] );
		add_action( 'admin_post_webgram_core_create_page', [ $this, 'create' ] );
	}

	/** @return array<string, array{module: string, setting: string, title: string, shortcode: string, label: string}> */
	private function requests(): array {
		$out = [];
		foreach ( (array) apply_filters( 'webgram_core/page_setup/pages', [] ) as $key => $req ) {
			if ( is_array( $req ) && ! empty( $req['module'] ) && ! empty( $req['shortcode'] ) ) {
				$out[ sanitize_key( (string) $key ) ] = $req + [ 'setting' => 'page_id', 'title' => ucfirst( (string) $key ), 'label' => ucfirst( (string) $key ) ];
			}
		}
		return $out;
	}

	public function notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( $this->requests() as $key => $req ) {
			$page = (int) $this->plugin->settings( $req['module'] )->get( $req['setting'], 0 );
			if ( $page > 0 && get_post_status( $page ) && 'trash' !== get_post_status( $page ) ) {
				continue;
			}
			$url = wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_create_page', 'key' => $key ], admin_url( 'admin-post.php' ) ), 'webgram_core_create_page_' . $key );
			printf(
				'<div class="notice notice-info"><p>%s <a class="button button-small" href="%s">%s</a></p></div>',
				esc_html( sprintf( /* translators: %s: feature name */ __( 'Webgram Core: the %s page is not set. Create it now or choose an existing page in Webgram > Settings.', 'webgram-core' ), $req['label'] ) ),
				esc_url( $url ),
				esc_html( sprintf( /* translators: %s: feature name */ __( 'Create %s page', 'webgram-core' ), $req['label'] ) )
			);
		}
	}

	public function create(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		$key = isset( $_GET['key'] ) ? sanitize_key( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'webgram_core_create_page_' . $key );
		$req = $this->requests()[ $key ] ?? null;
		if ( ! $req ) {
			wp_die( esc_html__( 'Unknown page request.', 'webgram-core' ) );
		}
		$this->create_page( $key );
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/**
	 * Creates the page for a registered request and stores its id in the module settings.
	 * Returns the existing page id when one is already configured. Used by the notice action and the demo importer.
	 */
	public function create_page( string $key ): int {
		$req = $this->requests()[ $key ] ?? null;
		if ( ! $req ) {
			return 0;
		}
		$settings = $this->plugin->settings( $req['module'] );
		$current  = (int) $settings->get( $req['setting'], 0 );
		if ( $current > 0 && get_post_status( $current ) && 'trash' !== get_post_status( $current ) ) {
			return $current;
		}
		$page_id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => (string) $req['title'],
				'post_content' => '<!-- wp:shortcode -->' . (string) $req['shortcode'] . '<!-- /wp:shortcode -->',
				'post_author'  => get_current_user_id(),
			],
			true
		);
		if ( is_wp_error( $page_id ) ) {
			return 0;
		}
		$settings->set( $req['setting'], (int) $page_id );
		do_action( 'webgram_core/page_setup/created', $key, (int) $page_id );
		return (int) $page_id;
	}
}
