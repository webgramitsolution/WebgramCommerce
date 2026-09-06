<?php
/**
 * Tab: Performance.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'performance',
	'label'    => __( 'Performance', 'webgram' ),
	'icon'     => 'zap',
	'priority' => 170,
	'sections' => [
		'assets' => [
			'fields' => [
				'perf_lazy_load'       => [ 'label' => __( 'Lazy load images below the fold', 'webgram' ), 'type' => 'switch' ],
				'perf_font_preload'    => [ 'label' => __( 'Preload primary font files', 'webgram' ), 'type' => 'switch' ],
				'perf_disable_emojis'  => [ 'label' => __( 'Disable WordPress emoji script', 'webgram' ), 'type' => 'switch' ],
				'perf_disable_embeds'  => [ 'label' => __( 'Disable oEmbed discovery script', 'webgram' ), 'type' => 'switch' ],
				'perf_critical_header' => [ 'label' => __( 'Inline critical header CSS', 'webgram' ), 'type' => 'switch', 'description' => __( 'Prints the header layout rules inline so the header paints before main.css.', 'webgram' ) ],
				'perf_cdn_prefix'      => [ 'label' => __( 'CDN URL for theme assets', 'webgram' ), 'type' => 'url', 'placeholder' => 'https://cdn.example.com', 'description' => __( 'Replaces the site URL in theme asset links. Leave empty when a caching plugin handles the CDN.', 'webgram' ) ],
				'core_perf'            => [ 'label' => __( 'Module assets', 'webgram' ), 'type' => 'info', 'content' => esc_html__( 'Webgram Core modules load their scripts only on pages that render them. Disable unused modules under Webgram > Modules.', 'webgram' ) ],
			],
		],
	],
];
