<?php
/**
 * Safe mode: loaded instead of the theme when the server does not meet the minimum requirements.
 *
 * Without this file the templates would call undefined functions and the site would show a critical error.
 * Here the pages still render with WordPress defaults and every admin sees exactly what to fix.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

function webgram_requirement_message(): string {
	return sprintf(
		/* translators: 1: required PHP version, 2: current PHP version. */
		__( 'Webgram needs PHP %1$s or newer. This server runs PHP %2$s, so the theme cannot load its styles or templates and the site falls back to plain WordPress output. Ask your host to switch this site to PHP 8.1, 8.2 or 8.3 (hPanel, cPanel or Plesk have a PHP version selector), then reload this page.', 'webgram' ),
		'8.1',
		PHP_VERSION
	);
}

add_action(
	'admin_notices',
	static function (): void {
		printf( '<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>', esc_html__( 'Webgram is not running.', 'webgram' ), esc_html( webgram_requirement_message() ) );
	}
);

/** Same message on the frontend, visible only to administrators. */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div style="position:fixed;inset-inline:0;bottom:0;z-index:99999;background:#b32d2e;color:#fff;padding:14px 18px;font:14px/1.5 system-ui,sans-serif;text-align:center">%s</div>',
			esc_html( webgram_requirement_message() )
		);
	}
);

/*
 * Stubs for the functions the templates call, so pages render instead of failing.
 */
function webgram_option( string $key, mixed $default = null ): mixed {
	return $default;
}

function webgram_part( string $slug, array $args = [] ): void {}

function webgram_icon( string $name, string $class = '', bool $echo = true ): string {
	return '';
}

function webgram_layout(): string {
	return 'container';
}

function webgram_content_classes(): string {
	return 'wg-content';
}

function webgram_layout_id( string $type ): int {
	return 0;
}

function webgram_render_block( int $id ): void {}

function webgram_has_core( string $module = '' ): bool {
	return false;
}
