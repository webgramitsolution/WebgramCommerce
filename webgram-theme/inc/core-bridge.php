<?php
/**
 * The only place the theme talks about Webgram Core. Templates use webgram_has_core() and hooks; never Core classes.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Webgram Core (optionally a specific module) is active.
 */
function webgram_has_core( string $module = '' ): bool {
	if ( ! function_exists( 'webgram_core' ) ) {
		return false;
	}
	if ( '' === $module ) {
		return true;
	}
	return webgram_core()->modules()->is_active( $module );
}

/**
 * Version handshake. Shows a notice instead of failing when Core is older than the theme expects.
 */
function webgram_core_version_notice(): void {
	if ( ! current_user_can( 'manage_options' ) || ! defined( 'WEBGRAM_CORE_VERSION' ) ) {
		return;
	}
	if ( version_compare( WEBGRAM_CORE_VERSION, WEBGRAM_MIN_CORE_VERSION, '<' ) ) {
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			sprintf(
				/* translators: 1: installed core version, 2: required version */
				esc_html__( 'Webgram Theme expects Webgram Core %2$s or newer, but %1$s is installed. Some features stay hidden until Core is updated.', 'webgram' ),
				esc_html( WEBGRAM_CORE_VERSION ),
				esc_html( WEBGRAM_MIN_CORE_VERSION )
			)
		);
	}
}
add_action( 'admin_notices', 'webgram_core_version_notice' );

/**
 * Core publishes its CSS variables under --wgc-*. When the theme is active, map them to theme tokens so Core
 * components inherit the site's design without Core shipping duplicate styles.
 */
function webgram_core_token_bridge( array $tokens ): array {
	$tokens['wgc-color-primary']          = 'var(--wg-color-primary)';
	$tokens['wgc-color-primary-contrast'] = '#ffffff';
	$tokens['wgc-color-text']             = 'var(--wg-color-text)';
	$tokens['wgc-color-muted']            = 'var(--wg-color-text-muted)';
	$tokens['wgc-color-border']           = 'var(--wg-color-border)';
	$tokens['wgc-color-surface']          = 'var(--wg-color-surface)';
	$tokens['wgc-color-bg-alt']           = 'var(--wg-color-bg-alt)';
	$tokens['wgc-radius']                 = 'var(--wg-radius-md)';
	$tokens['wgc-font']                   = 'var(--wg-font-body)';
	return $tokens;
}
add_filter( 'webgram/tokens', 'webgram_core_token_bridge', 5 );
