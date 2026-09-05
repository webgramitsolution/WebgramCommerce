<?php
/**
 * "Sync tokens to Elementor": copies the theme design tokens (colors, fonts) into the active Elementor kit as
 * custom global colors and fonts so pages styled inside Elementor stay consistent with Theme Settings.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Elementor_Sync {

	public const ACTION = 'webgram_sync_elementor';

	public static function init(): void {
		add_action( 'admin_post_' . self::ACTION, [ self::class, 'sync' ] );
		add_action( 'admin_notices', [ self::class, 'notice' ] );
	}

	public static function available(): bool {
		return class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->kits_manager );
	}

	public static function url(): string {
		return wp_nonce_url( add_query_arg( 'action', self::ACTION, admin_url( 'admin-post.php' ) ), self::ACTION );
	}

	/** Pure: token map to Elementor kit settings (custom_colors and custom_typography). */
	public static function kit_settings( array $tokens ): array {
		$colors = [
			'primary'   => [ 'wg-color-primary', __( 'Webgram Primary', 'webgram' ) ],
			'secondary' => [ 'wg-color-secondary', __( 'Webgram Secondary', 'webgram' ) ],
			'accent'    => [ 'wg-color-accent', __( 'Webgram Accent', 'webgram' ) ],
			'text'      => [ 'wg-color-text', __( 'Webgram Text', 'webgram' ) ],
			'heading'   => [ 'wg-color-heading', __( 'Webgram Heading', 'webgram' ) ],
			'muted'     => [ 'wg-color-text-muted', __( 'Webgram Muted', 'webgram' ) ],
			'bg_alt'    => [ 'wg-color-bg-alt', __( 'Webgram Background Alt', 'webgram' ) ],
			'border'    => [ 'wg-color-border', __( 'Webgram Border', 'webgram' ) ],
		];
		$custom_colors = [];
		foreach ( $colors as $id => [ $token, $title ] ) {
			$value = (string) ( $tokens[ $token ] ?? '' );
			if ( preg_match( '/^#[0-9a-f]{3,8}$/i', $value ) ) {
				$custom_colors[] = [ '_id' => 'webgram_' . $id, 'title' => $title, 'color' => strtoupper( $value ) ];
			}
		}
		$typography = [];
		foreach ( [ 'body' => [ 'wg-font-body', __( 'Webgram Body', 'webgram' ) ], 'heading' => [ 'wg-font-heading', __( 'Webgram Heading', 'webgram' ) ] ] as $id => [ $token, $title ] ) {
			$family = trim( (string) strtok( (string) ( $tokens[ $token ] ?? '' ), ',' ), '" ' );
			if ( '' !== $family ) {
				$typography[] = [ '_id' => 'webgram_' . $id, 'title' => $title, 'typography_typography' => 'custom', 'typography_font_family' => $family ];
			}
		}
		return [ 'custom_colors' => $custom_colors, 'custom_typography' => $typography ];
	}

	public static function sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram' ), 403 );
		}
		check_admin_referer( self::ACTION );
		$status = 'missing';
		if ( self::available() ) {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
			if ( $kit && $kit->get_id() ) {
				$tokens   = Webgram_CSS_Generator::instance()->tokens();
				$new      = self::kit_settings( $tokens );
				$settings = (array) $kit->get_settings();
				foreach ( [ 'custom_colors', 'custom_typography' ] as $key ) {
					$kept = array_values( array_filter( (array) ( $settings[ $key ] ?? [] ), static fn( $row ) => ! str_starts_with( (string) ( $row['_id'] ?? '' ), 'webgram_' ) ) );
					$kit->update_settings( [ $key => array_merge( $kept, $new[ $key ] ) ] );
				}
				\Elementor\Plugin::$instance->files_manager->clear_cache();
				$status = 'done';
			}
		}
		set_transient( 'webgram_elementor_sync_' . get_current_user_id(), $status, 60 );
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=webgram-dashboard' ) );
		exit;
	}

	public static function notice(): void {
		$status = get_transient( 'webgram_elementor_sync_' . get_current_user_id() );
		if ( ! $status ) {
			return;
		}
		delete_transient( 'webgram_elementor_sync_' . get_current_user_id() );
		if ( 'done' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Theme tokens synced to the Elementor global colors and fonts.', 'webgram' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Elementor is not active or has no active kit, nothing was synced.', 'webgram' ) . '</p></div>';
		}
	}
}

Webgram_Elementor_Sync::init();
