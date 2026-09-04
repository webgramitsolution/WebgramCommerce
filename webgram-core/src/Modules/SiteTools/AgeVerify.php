<?php
namespace Webgram\Core\Modules\SiteTools;

defined( 'ABSPATH' ) || exit;

/** Age verification gate. Confirmation stored in wg_age_ok; decline redirects. */
final class AgeVerify {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'render' ], 22 );
	}

	public function render(): void {
		$s = $this->module->settings();
		if ( ! $s->get( 'age_enabled', false ) || is_admin() || is_customize_preview() ) {
			return;
		}
		if ( isset( $_COOKIE['wg_age_ok'] ) || ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'site_tools' );
		\webgram_core()->view(
			'site-tools/age-verify',
			[
				'title'    => (string) $s->get( 'age_title', '' ),
				'text'     => (string) $s->get( 'age_text', '' ),
				'min'      => (int) $s->get( 'age_min', 18 ),
				'mode'     => (string) $s->get( 'age_mode', 'yesno' ),
				'yes'      => (string) $s->get( 'age_yes', __( 'Yes, I am', 'webgram-core' ) ),
				'no'       => (string) $s->get( 'age_no', __( 'No, leave', 'webgram-core' ) ),
				'redirect' => (string) $s->get( 'age_redirect', 'https://www.google.com' ),
				'days'     => (int) $s->get( 'age_remember', 30 ),
				'bg'       => (int) $s->get( 'age_bg', 0 ),
			]
		);
	}
}
