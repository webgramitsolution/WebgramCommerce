<?php
/**
 * Webgram Child. The parent stylesheet is enqueued by the parent theme; add child styles after it.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'webgram-child', get_stylesheet_uri(), [ 'webgram-main' ], wp_get_theme()->get( 'Version' ) );
	},
	20
);
