<?php
namespace Webgram\Core\Modules\SiteTools;

defined( 'ABSPATH' ) || exit;

/** Cookie law notice with accept and optional reject; choice stored in the wg_cookie_ok cookie (accepted|rejected). */
final class CookieNotice {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'render' ], 21 );
	}

	public function render(): void {
		$s = $this->module->settings();
		if ( ! $s->get( 'cookie_enabled', false ) || is_admin() ) {
			return;
		}
		if ( isset( $_COOKIE['wg_cookie_ok'] ) ) {
			return; // Already answered; nothing to print (cached pages are handled by JS as well).
		}
		\webgram_core()->assets()->enqueue_module( 'site_tools' );
		$page = (int) $s->get( 'cookie_policy_page', 0 );
		\webgram_core()->view(
			'site-tools/cookie-notice',
			[
				'text'         => (string) $s->get( 'cookie_text', '' ),
				'accept'       => (string) $s->get( 'cookie_accept', __( 'Accept', 'webgram-core' ) ),
				'reject'       => $s->get( 'cookie_reject_show', true ) ? (string) $s->get( 'cookie_reject', __( 'Reject', 'webgram-core' ) ) : '',
				'policy_url'   => $page ? (string) get_permalink( $page ) : '',
				'policy_label' => (string) $s->get( 'cookie_policy_label', __( 'Learn more', 'webgram-core' ) ),
				'position'     => (string) $s->get( 'cookie_position', 'bottom-left' ),
				'days'         => (int) $s->get( 'cookie_remember', 180 ),
			]
		);
	}
}
