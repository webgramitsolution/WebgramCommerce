<?php
/**
 * Renders navigation menus through the mega menu walker with a per-menu transient cache. Current-item classes are
 * intentionally excluded from the cached markup; JS marks the current link by URL.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Mega_Menu_Frontend {

	public const TRANSIENT = 'webgram_mega_menu';

	public static function menu( array $args, bool $mega = true ): void {
		$args['walker'] = new Webgram_Mega_Menu_Walker( $mega );
		$args['echo']   = false;
		$args           = (array) apply_filters( 'webgram/menu_args', $args );

		$cache_args = $args;
		unset( $cache_args['walker'] );
		$key = md5( wp_json_encode( $cache_args ) . ( $mega ? '1' : '0' ) . get_locale() . ( is_user_logged_in() ? 'u' : 'g' ) );

		$cache = get_transient( self::TRANSIENT );
		$cache = is_array( $cache ) ? $cache : [];
		if ( ! isset( $cache[ $key ] ) || is_customize_preview() || ! apply_filters( 'webgram/menu_cache', true ) ) {
			$cache[ $key ] = (string) wp_nav_menu( $args );
			if ( count( $cache ) > 40 ) {
				$cache = array_slice( $cache, -20, null, true );
			}
			set_transient( self::TRANSIENT, $cache, DAY_IN_SECONDS );
		}
		echo $cache[ $key ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nav_menu output.
	}
}
