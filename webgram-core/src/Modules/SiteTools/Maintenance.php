<?php
namespace Webgram\Core\Modules\SiteTools;

defined( 'ABSPATH' ) || exit;

/**
 * Maintenance (503) and coming soon (200) modes. Allowed roles keep browsing; login, admin, REST, AJAX and cron
 * requests are never blocked.
 */
final class Maintenance {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_block' ], 1 );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 100 );
	}

	public function mode(): string {
		$mode = (string) $this->module->settings()->get( 'maint_mode', 'off' );
		return in_array( $mode, [ 'coming_soon', 'maintenance' ], true ) ? $mode : 'off';
	}

	public function user_can_bypass(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$roles = (array) $this->module->settings()->get( 'maint_roles', [] );
		$user  = wp_get_current_user();
		return (bool) array_intersect( $roles, (array) $user->roles );
	}

	public function maybe_block(): void {
		$mode = $this->mode();
		if ( 'off' === $mode || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
			return;
		}
		if ( $this->user_can_bypass() || (bool) apply_filters( 'webgram_core/maintenance/bypass', false ) ) {
			return;
		}
		$script = $GLOBALS['pagenow'] ?? '';
		if ( in_array( $script, [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		$s    = $this->module->settings();
		$page = (int) $s->get( 'maint_page', 0 );
		if ( $page && get_post_status( $page ) === 'publish' && is_page( $page ) ) {
			// The chosen page renders normally with the theme; other pages redirect here.
			$this->headers( $mode );
			return;
		}
		if ( $page && get_post_status( $page ) === 'publish' ) {
			wp_safe_redirect( (string) get_permalink( $page ), 302 );
			exit;
		}

		$this->headers( $mode );
		\webgram_core()->assets()->enqueue_module( 'site_tools' );
		\webgram_core()->view(
			'site-tools/maintenance',
			[
				'mode'      => $mode,
				'block'     => (int) $s->get( 'maint_block', 0 ) ? Blocks::render( (int) $s->get( 'maint_block' ) ) : '',
				'title'     => (string) $s->get( 'maint_title', '' ),
				'text'      => (string) $s->get( 'maint_text', '' ),
				'countdown' => self::countdown_timestamp( (string) $s->get( 'maint_countdown', '' ) ),
				'bg'        => (int) $s->get( 'maint_bg', 0 ),
			]
		);
		exit;
	}

	private function headers( string $mode ): void {
		nocache_headers();
		if ( 'maintenance' === $mode ) {
			status_header( 503 );
			header( 'Retry-After: 3600' );
		}
	}

	/** Parses "YYYY-MM-DD HH:MM" in the site timezone; 0 when empty or invalid. */
	public static function countdown_timestamp( string $value ): int {
		$value = trim( $value );
		if ( '' === $value ) {
			return 0;
		}
		try {
			$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
			$dt = new \DateTimeImmutable( $value, $tz );
			return $dt->getTimestamp();
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	public function admin_bar( \WP_Admin_Bar $bar ): void {
		if ( 'off' === $this->mode() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			[
				'id'    => 'webgram-maintenance',
				'title' => 'maintenance' === $this->mode() ? __( 'Maintenance mode is ON', 'webgram-core' ) : __( 'Coming soon mode is ON', 'webgram-core' ),
				'href'  => admin_url( \Webgram\Core\Admin\ModulesPage::theme_has_panel() ? 'admin.php?page=webgram&tab=maintenance' : 'admin.php?page=webgram-core-settings&tab=site_tools' ),
				'meta'  => [ 'class' => 'wgc-adminbar-warning' ],
			]
		);
	}
}
