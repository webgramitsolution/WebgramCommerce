<?php
namespace Webgram\Core\Modules\Instagram;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Support\Helpers;
use Webgram\Core\Support\SettingsBridge;

defined( 'ABSPATH' ) || exit;

/**
 * Instagram feed: Graph API mode (encrypted long-lived token, cached fetch, monthly refresh, test connection)
 * with a manual gallery fallback. Rendered by the shortcode, widget and block through one template.
 */
final class Module extends BaseModule {

	public const CRON      = 'webgram_core_instagram_refresh';
	public const ERROR_OPT = 'webgram_core_instagram_error';
	public const GROUP     = 'instagram';

	public function id(): string {
		return 'instagram';
	}

	public function name(): string {
		return __( 'Instagram Feed', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Show your Instagram posts using a Meta access token, with a manual gallery fallback.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function phase(): int {
		return 5;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		SettingsBridge::attach( $this, fn() => Settings::definitions( $this ) );
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_shortcode( 'webgram_instagram', [ $this, 'shortcode' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		add_action( 'admin_post_webgram_core_instagram_test', [ $this, 'test_connection' ] );
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'webgram_core/settings_saved', [ $this, 'flush_on_save' ] );
		add_filter( 'cron_schedules', [ $this, 'schedules' ] ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval
		add_filter( 'webgram_core/cron_hooks', static fn( array $hooks ) => array_merge( $hooks, [ self::CRON ] ) );
		add_action( self::CRON, [ $this, 'refresh_token' ] );
		add_action( 'init', [ $this, 'schedule' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-instagram', 'css/instagram.css' );
	}

	public function settings_fields(): array {
		if ( ModulesPage::theme_has_panel() ) {
			return [ [ 'id' => 'panel_link', 'label' => __( 'Instagram settings', 'webgram-core' ), 'type' => 'link', 'url' => admin_url( 'admin.php?page=webgram&tab=instagram' ), 'button' => __( 'Open Theme Settings', 'webgram-core' ) ] ];
		}
		return SettingsBridge::fallback_fields(
			Settings::definitions( $this ),
			[ 'manual_items' => [ 'label' => __( 'Manual posts', 'webgram-core' ), 'type' => 'textarea', 'default' => '', 'description' => __( 'One post per line: image URL or attachment ID | link | caption', 'webgram-core' ), 'id' => 'manual_lines' ] ]
		);
	}

	public function schedules( array $schedules ): array {
		$schedules['webgram_30days'] = [ 'interval' => 30 * DAY_IN_SECONDS, 'display' => __( 'Every 30 days (Webgram)', 'webgram-core' ) ];
		return $schedules;
	}

	public function schedule(): void {
		if ( 'api' === $this->settings()->get( 'mode', 'manual' ) && ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'webgram_30days', self::CRON );
		}
	}

	public function flush_on_save( string $tab ): void {
		if ( 'instagram' === $tab ) {
			\webgram_core()->cache()->flush_group( self::GROUP );
		}
	}

	private function token(): string {
		$stored = (string) $this->settings()->get( 'access_token', '' );
		return '' === $stored ? '' : \webgram_core()->crypto()->decrypt( $stored );
	}

	private function api(): ?Api {
		$user = trim( (string) $this->settings()->get( 'ig_user_id', '' ) );
		$tok  = $this->token();
		if ( '' === $user || '' === $tok ) {
			return null;
		}
		return new Api( (string) ( $this->settings()->get( 'api_version', 'v21.0' ) ?: 'v21.0' ), $user, $tok );
	}

	public function status_text(): string {
		$error = (string) get_option( self::ERROR_OPT, '' );
		if ( '' !== $error ) {
			return sprintf( /* translators: %s: error */ __( 'Last error: %s', 'webgram-core' ), $error );
		}
		$ok = get_transient( 'webgram_core_instagram_ok' );
		return $ok ? sprintf( /* translators: %s: username */ __( 'Connected as @%s', 'webgram-core' ), (string) $ok ) : __( 'Not tested yet.', 'webgram-core' );
	}

	public function test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_core_instagram_test' );
		$api = $this->api();
		if ( ! $api ) {
			update_option( self::ERROR_OPT, __( 'Account ID or access token is missing.', 'webgram-core' ), false );
		} else {
			$account = $api->account();
			if ( is_wp_error( $account ) ) {
				update_option( self::ERROR_OPT, $account->get_error_message(), false );
				\webgram_core()->logger()->error( 'Instagram test failed', [ 'error' => $account->get_error_message() ] );
			} else {
				delete_option( self::ERROR_OPT );
				set_transient( 'webgram_core_instagram_ok', $account['username'], DAY_IN_SECONDS );
				\webgram_core()->cache()->flush_group( self::GROUP );
			}
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	public function admin_notice(): void {
		$error = (string) get_option( self::ERROR_OPT, '' );
		if ( '' !== $error && current_user_can( 'manage_options' ) && 'api' === $this->settings()->get( 'mode', 'manual' ) ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( sprintf( /* translators: %s: error */ __( 'Webgram Instagram feed: %s. The feed falls back to manual posts until the connection works.', 'webgram-core' ), $error ) ) );
		}
	}

	public function refresh_token(): void {
		$api = $this->api();
		if ( ! $api ) {
			return;
		}
		$result = $api->refresh();
		if ( is_wp_error( $result ) ) {
			\webgram_core()->logger()->warning( 'Instagram token refresh failed', [ 'error' => $result->get_error_message() ] );
			return;
		}
		$this->settings()->set( 'access_token', \webgram_core()->crypto()->encrypt( $result['token'] ) );
	}

	/** Pure: manual lines "image|link|caption". */
	public static function parse_lines( string $text ): array {
		$out = [];
		foreach ( preg_split( '/\r?\n/', trim( $text ) ) ?: [] as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 3 ) );
			if ( '' === ( $parts[0] ?? '' ) ) {
				continue;
			}
			$out[] = [ 'image' => $parts[0], 'link' => $parts[1] ?? '', 'caption' => $parts[2] ?? '' ];
		}
		return $out;
	}

	/** @return array<int, array{id: string, type: string, image: string, url: string, caption: string, time: string}> */
	public function manual_items( int $limit ): array {
		$rows = array_merge( (array) $this->settings()->get( 'manual_items', [] ), self::parse_lines( (string) $this->settings()->get( 'manual_lines', '' ) ) );
		$out  = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$image = (string) ( $row['image'] ?? '' );
			if ( is_numeric( $image ) ) {
				$image = (string) wp_get_attachment_image_url( (int) $image, 'medium_large' );
			}
			if ( '' === $image ) {
				continue;
			}
			$out[] = [ 'id' => 'm' . count( $out ), 'type' => 'image', 'image' => $image, 'url' => (string) ( $row['link'] ?? '' ), 'caption' => (string) ( $row['caption'] ?? '' ), 'time' => '' ];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	public function items( int $limit ): array {
		if ( 'api' === $this->settings()->get( 'mode', 'manual' ) ) {
			$api = $this->api();
			if ( $api ) {
				$hours = max( 1, (int) $this->settings()->get( 'cache_hours', 12 ) );
				$items = \webgram_core()->cache()->remember(
					'media_' . $limit,
					$hours * HOUR_IN_SECONDS,
					function () use ( $api, $limit ): array {
						$result = $api->media( $limit );
						if ( is_wp_error( $result ) ) {
							update_option( self::ERROR_OPT, $result->get_error_message(), false );
							\webgram_core()->logger()->error( 'Instagram fetch failed', [ 'error' => $result->get_error_message() ] );
							return [];
						}
						delete_option( self::ERROR_OPT );
						return $result;
					},
					self::GROUP
				);
				if ( $items ) {
					return (array) $items;
				}
			}
		}
		return $this->manual_items( $limit );
	}

	public function render( array $args = [] ): string {
		$s     = $this->settings();
		$count = max( 1, min( 24, (int) ( $args['count'] ?? $s->get( 'count', 6 ) ) ) );
		$items = $this->items( $count );
		if ( ! $items ) {
			return '';
		}
		\webgram_core()->assets()->enqueue_module( 'instagram' );
		return $this->view(
			'feed',
			[
				'items'        => $items,
				'title'        => (string) ( $args['title'] ?? $s->get( 'title', __( 'Instagram Feed', 'webgram-core' ) ) ),
				'columns'      => max( 2, min( 8, (int) ( $args['columns'] ?? $s->get( 'columns', 6 ) ) ) ),
				'layout'       => 'slider' === ( $args['layout'] ?? $s->get( 'layout', 'grid' ) ) ? 'slider' : 'grid',
				'show_caption' => Helpers::bool( $args['show_caption'] ?? $s->get( 'show_caption', true ) ),
				'follow_url'   => (string) ( $args['follow_url'] ?? $s->get( 'follow_url', '' ) ),
				'follow_text'  => (string) ( $args['follow_text'] ?? $s->get( 'follow_text', __( 'Follow Us', 'webgram-core' ) ) ),
			],
			false
		);
	}

	public function shortcode( array|string $atts ): string {
		return $this->render( shortcode_atts( [ 'count' => '', 'columns' => '', 'layout' => '', 'title' => '', 'show_caption' => '', 'follow_url' => '', 'follow_text' => '' ], (array) $atts, 'webgram_instagram' ) );
	}

	public function widget_definition( array $widgets ): array {
		$s = $this->settings();
		$widgets['instagram'] = [
			'title'    => __( 'Webgram Instagram Feed', 'webgram-core' ),
			'icon'     => 'eicon-instagram-gallery',
			'controls' => [
				'title'        => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text', 'default' => (string) $s->get( 'title', __( 'Instagram Feed', 'webgram-core' ) ) ],
				'count'        => [ 'label' => __( 'Posts', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 24, 'default' => (int) $s->get( 'count', 6 ) ],
				'columns'      => [ 'label' => __( 'Columns', 'webgram-core' ), 'type' => 'number', 'min' => 2, 'max' => 8, 'default' => (int) $s->get( 'columns', 6 ) ],
				'layout'       => [ 'label' => __( 'Layout', 'webgram-core' ), 'type' => 'select', 'options' => [ 'grid' => __( 'Grid', 'webgram-core' ), 'slider' => __( 'Slider', 'webgram-core' ) ], 'default' => (string) $s->get( 'layout', 'grid' ) ],
				'show_caption' => [ 'label' => __( 'Caption on hover', 'webgram-core' ), 'type' => 'switch', 'default' => Helpers::bool( $s->get( 'show_caption', true ) ) ],
				'follow_url'   => [ 'label' => __( 'Follow link', 'webgram-core' ), 'type' => 'url', 'default' => (string) $s->get( 'follow_url', '' ) ],
				'follow_text'  => [ 'label' => __( 'Follow text', 'webgram-core' ), 'type' => 'text', 'default' => (string) $s->get( 'follow_text', __( 'Follow Us', 'webgram-core' ) ) ],
			],
			'render'   => fn( array $args ) => $this->render( $args ),
		];
		return $widgets;
	}
}
