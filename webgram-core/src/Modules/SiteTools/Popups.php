<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Popups: one wg_popup post per popup with its own content (editor, Elementor or an HTML Block), trigger
 * (delay, scroll depth, exit intent, click on a selector), frequency, devices and page targeting.
 * Page targeting runs on the server; device targeting runs in the browser so cached pages stay correct.
 * The legacy single promo popup settings are migrated into a post on first run.
 */
final class Popups {

	public const POST_TYPE = 'wg_popup';
	public const META      = '_wg_popup';
	public const NONCE     = 'wgc_popup';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'register_type' ] );
		add_action( 'init', [ $this, 'migrate_legacy' ], 20 );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ $this, 'metabox' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save' ], 10, 2 );
		add_filter( 'elementor/cpt_support', [ $this, 'elementor_support' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'column' ], 10, 2 );
		add_action( 'save_post', [ $this, 'flush' ] );
		add_action( 'wp_footer', [ $this, 'render' ], 20 );
	}

	public static function defaults(): array {
		return [
			'enabled'   => true,
			'source'    => 'content',
			'block'     => 0,
			'image'     => 0,
			'width'     => 640,
			'trigger'   => 'delay',
			'delay'     => 5,
			'scroll'    => 40,
			'selector'  => '',
			'frequency' => 'days',
			'days'      => 1,
			'devices'   => [ 'desktop', 'tablet', 'mobile' ],
			'pages'     => [ 'home', 'shop', 'product', 'blog', 'page', 'other' ],
			'include'   => '',
			'exclude'   => '',
			'label'     => '',
		];
	}

	/** Pure sanitizer, covered by the harness. */
	public static function sanitize( array $raw ): array {
		$d       = self::defaults();
		$devices = array_values( array_intersect( [ 'desktop', 'tablet', 'mobile' ], array_map( 'sanitize_key', (array) ( $raw['devices'] ?? [] ) ) ) );
		$pages   = array_values( array_intersect( [ 'home', 'shop', 'product', 'cart', 'checkout', 'account', 'blog', 'page', 'other' ], array_map( 'sanitize_key', (array) ( $raw['pages'] ?? [] ) ) ) );
		$ids     = static fn( $v ): string => implode( ',', array_filter( array_map( 'absint', preg_split( '/[\s,]+/', (string) $v ) ?: [] ) ) );
		return [
			'enabled'   => ! empty( $raw['enabled'] ),
			'source'    => 'block' === ( $raw['source'] ?? '' ) ? 'block' : 'content',
			'block'     => absint( $raw['block'] ?? 0 ),
			'image'     => absint( $raw['image'] ?? 0 ),
			'width'     => max( 320, min( 1200, (int) ( $raw['width'] ?? $d['width'] ) ) ),
			'trigger'   => in_array( $raw['trigger'] ?? '', [ 'delay', 'scroll', 'exit', 'click', 'load' ], true ) ? (string) $raw['trigger'] : 'delay',
			'delay'     => max( 0, min( 300, (int) ( $raw['delay'] ?? $d['delay'] ) ) ),
			'scroll'    => max( 1, min( 100, (int) ( $raw['scroll'] ?? $d['scroll'] ) ) ),
			'selector'  => substr( sanitize_text_field( (string) ( $raw['selector'] ?? '' ) ), 0, 120 ),
			'frequency' => in_array( $raw['frequency'] ?? '', [ 'always', 'session', 'days' ], true ) ? (string) $raw['frequency'] : 'days',
			'days'      => max( 1, min( 365, (int) ( $raw['days'] ?? $d['days'] ) ) ),
			'devices'   => $devices ?: $d['devices'],
			'pages'     => $pages,
			'include'   => $ids( $raw['include'] ?? '' ),
			'exclude'   => $ids( $raw['exclude'] ?? '' ),
			'label'     => sanitize_text_field( (string) ( $raw['label'] ?? '' ) ),
		];
	}

	/**
	 * Whether a popup applies to the current request. Pure: $context has page_type and post_id.
	 * Specific ids win over page types: an included id always shows, an excluded id never shows.
	 */
	public static function targets( array $settings, array $context ): bool {
		$post_id = (int) ( $context['post_id'] ?? 0 );
		$include = array_filter( array_map( 'intval', explode( ',', (string) ( $settings['include'] ?? '' ) ) ) );
		$exclude = array_filter( array_map( 'intval', explode( ',', (string) ( $settings['exclude'] ?? '' ) ) ) );
		if ( $post_id && in_array( $post_id, $exclude, true ) ) {
			return false;
		}
		if ( $post_id && in_array( $post_id, $include, true ) ) {
			return true;
		}
		if ( $include && ! in_array( (string) ( $context['page_type'] ?? '' ), (array) ( $settings['pages'] ?? [] ), true ) ) {
			return false;
		}
		return in_array( (string) ( $context['page_type'] ?? '' ), (array) ( $settings['pages'] ?? [] ), true );
	}

	public function register_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Popups', 'webgram-core' ),
					'singular_name' => __( 'Popup', 'webgram-core' ),
					'add_new_item'  => __( 'Add popup', 'webgram-core' ),
					'edit_item'     => __( 'Edit popup', 'webgram-core' ),
					'menu_name'     => __( 'Popups', 'webgram-core' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => ModulesPage::parent_slug(),
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'supports'            => [ 'title', 'editor', 'revisions' ],
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
			]
		);
	}

	public function elementor_support( array $types ): array {
		$types[] = self::POST_TYPE;
		return array_unique( $types );
	}

	/** One time: the old single promo popup settings become the first popup post. */
	public function migrate_legacy(): void {
		if ( get_option( 'webgram_core_popups_migrated' ) ) {
			return;
		}
		update_option( 'webgram_core_popups_migrated', 1, false );
		$s = $this->module->settings();
		if ( ! $s->get( 'popup_enabled', false ) ) {
			return;
		}
		$id = wp_insert_post(
			[
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => __( 'Promo popup', 'webgram-core' ),
				'post_content' => (string) $s->get( 'popup_content', '' ),
			],
			true
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		$legacy = (string) $s->get( 'popup_frequency', 'day' );
		update_post_meta(
			(int) $id,
			self::META,
			self::sanitize(
				[
					'enabled'   => true,
					'source'    => (int) $s->get( 'popup_block', 0 ) ? 'block' : 'content',
					'block'     => (int) $s->get( 'popup_block', 0 ),
					'image'     => (int) $s->get( 'popup_image', 0 ),
					'width'     => (int) $s->get( 'popup_width', 640 ),
					'trigger'   => (string) $s->get( 'popup_trigger', 'delay' ),
					'delay'     => (int) $s->get( 'popup_delay', 5 ),
					'scroll'    => (int) $s->get( 'popup_scroll', 40 ),
					'frequency' => 'always' === $legacy ? 'always' : ( 'session' === $legacy ? 'session' : 'days' ),
					'days'      => 'week' === $legacy ? 7 : 1,
					'devices'   => (array) $s->get( 'popup_devices', [ 'desktop', 'tablet', 'mobile' ] ),
					'pages'     => (array) $s->get( 'popup_pages', [] ),
				]
			)
		);
	}

	public static function get( int $post_id ): array {
		$meta = get_post_meta( $post_id, self::META, true );
		return array_merge( self::defaults(), is_array( $meta ) ? $meta : [] );
	}

	public function metabox(): void {
		add_meta_box( 'wgc-popup', __( 'Popup settings', 'webgram-core' ), [ $this, 'render_metabox' ], self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'wgc-popup-usage', __( 'How it works', 'webgram-core' ), [ $this, 'render_usage' ], self::POST_TYPE, 'side' );
	}

	public function render_metabox( \WP_Post $post ): void {
		$s      = self::get( $post->ID );
		$blocks = (array) apply_filters( 'webgram/html_blocks', [] );
		wp_nonce_field( self::NONCE, self::NONCE );
		\webgram_core()->view( 'site-tools/admin-popup', [ 'settings' => $s, 'blocks' => $blocks ] );
	}

	public function render_usage(): void {
		echo '<p>' . esc_html__( 'Write the popup content in the editor above (or build it with Elementor), or pick an HTML Block. Published popups show on the targeted pages according to their trigger and frequency. Several popups can be active; only one opens at a time.', 'webgram-core' ) . '</p>';
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = isset( $_POST['wg_popup'] ) && is_array( $_POST['wg_popup'] ) ? wp_unslash( $_POST['wg_popup'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in sanitize().
		update_post_meta( $post_id, self::META, self::sanitize( $raw ) );
	}

	public function flush( int $post_id ): void {
		if ( get_post_type( $post_id ) === self::POST_TYPE ) {
			\webgram_core()->cache()->flush_group( 'popups' );
		}
	}

	public function columns( array $columns ): array {
		$columns['wgc_trigger'] = __( 'Trigger', 'webgram-core' );
		$columns['wgc_pages']   = __( 'Shown on', 'webgram-core' );
		return $columns;
	}

	public function column( string $column, int $post_id ): void {
		$s = self::get( $post_id );
		if ( 'wgc_trigger' === $column ) {
			$labels = [ 'delay' => __( 'After %d s', 'webgram-core' ), 'scroll' => __( 'At %d%% scroll', 'webgram-core' ), 'exit' => __( 'Exit intent', 'webgram-core' ), 'click' => __( 'Click', 'webgram-core' ), 'load' => __( 'On load', 'webgram-core' ) ];
			$value  = 'delay' === $s['trigger'] ? $s['delay'] : $s['scroll'];
			echo esc_html( str_contains( $labels[ $s['trigger'] ], '%d' ) ? sprintf( $labels[ $s['trigger'] ], $value ) : $labels[ $s['trigger'] ] ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			echo $s['enabled'] ? '' : ' <em>(' . esc_html__( 'paused', 'webgram-core' ) . ')</em>';
		} elseif ( 'wgc_pages' === $column ) {
			echo esc_html( implode( ', ', $s['pages'] ) . ( $s['include'] ? ' +' . $s['include'] : '' ) );
		}
	}

	/** @return array<int, array{id: int, settings: array}> */
	public function active(): array {
		return \webgram_core()->cache()->remember(
			'popups_active',
			HOUR_IN_SECONDS,
			static function (): array {
				$out = [];
				foreach ( get_posts( [ 'post_type' => self::POST_TYPE, 'post_status' => 'publish', 'numberposts' => 20, 'orderby' => 'menu_order title', 'order' => 'ASC' ] ) as $post ) {
					$settings = self::get( $post->ID );
					if ( $settings['enabled'] ) {
						$out[] = [ 'id' => $post->ID, 'settings' => $settings ];
					}
				}
				return $out;
			},
			'popups'
		);
	}

	public function render(): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}
		$context = [ 'page_type' => Helpers::page_type(), 'post_id' => is_singular() ? get_queried_object_id() : 0 ];
		$shown   = 0;
		foreach ( $this->active() as $popup ) {
			if ( ! self::targets( $popup['settings'], $context ) || ! apply_filters( 'webgram_core/popup/show', true, $popup['id'], $popup['settings'] ) ) {
				continue;
			}
			$s       = $popup['settings'];
			$content = 'block' === $s['source'] && $s['block'] ? Blocks::render( (int) $s['block'] ) : $this->content( $popup['id'] );
			if ( '' === trim( wp_strip_all_tags( $content ) ) && ! str_contains( $content, '<img' ) ) {
				continue;
			}
			if ( 0 === $shown ) {
				\webgram_core()->assets()->enqueue_module( 'site_tools' );
			}
			++$shown;
			\webgram_core()->view(
				'site-tools/promo-popup',
				[
					'id'        => $popup['id'],
					'label'     => $s['label'] ?: get_the_title( $popup['id'] ),
					'content'   => $content,
					'image'     => (int) $s['image'],
					'width'     => (int) $s['width'],
					'trigger'   => $s['trigger'],
					'delay'     => (int) $s['delay'],
					'scroll'    => (int) $s['scroll'],
					'selector'  => $s['selector'],
					'frequency' => $s['frequency'],
					'days'      => (int) $s['days'],
					'devices'   => $s['devices'],
					'key'       => 'wg_popup_' . $popup['id'] . '_' . substr( md5( (string) get_post_modified_time( 'U', true, $popup['id'] ) ), 0, 6 ),
				]
			);
		}
	}

	private function content( int $post_id ): string {
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents->get( $post_id ) && \Elementor\Plugin::$instance->documents->get( $post_id )->is_built_with_elementor() ) {
			return (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id );
		}
		$post = get_post( $post_id );
		return $post ? (string) apply_filters( 'the_content', $post->post_content ) : '';
	}
}
