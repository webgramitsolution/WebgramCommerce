<?php
/**
 * Tab: Layout.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_layouts = [
	'container'     => __( 'No sidebar', 'webgram' ),
	'full-width'    => __( 'Full width', 'webgram' ),
	'sidebar-left'  => __( 'Sidebar left', 'webgram' ),
	'sidebar-right' => __( 'Sidebar right', 'webgram' ),
];

return [
	'id'       => 'layout',
	'label'    => __( 'Layout', 'webgram' ),
	'icon'     => 'layout',
	'priority' => 20,
	'sections' => [
		'defaults' => [
			'label'  => __( 'Default layouts', 'webgram' ),
			'fields' => [
				'page_layout'    => [ 'label' => __( 'Pages', 'webgram' ), 'type' => 'select', 'choices' => $webgram_layouts ],
				'blog_layout'    => [ 'label' => __( 'Blog', 'webgram' ), 'type' => 'select', 'choices' => $webgram_layouts ],
				'shop_layout'    => [ 'label' => __( 'Shop', 'webgram' ), 'type' => 'select', 'choices' => [ 'sidebar-left' => __( 'Filters on the left', 'webgram' ), 'sidebar-right' => __( 'Filters on the right', 'webgram' ), 'full-width' => __( 'No sidebar', 'webgram' ) ] ],
			],
		],
		'spacing' => [
			'label'  => __( 'Spacing and borders', 'webgram' ),
			'fields' => [
				'spacing_scale' => [ 'label' => __( 'Spacing scale', 'webgram' ), 'type' => 'radio', 'choices' => [ 'compact' => __( 'Compact', 'webgram' ), 'default' => __( 'Default', 'webgram' ), 'relaxed' => __( 'Relaxed', 'webgram' ) ], 'description' => __( 'Scales every gap, padding and section space in the theme.', 'webgram' ) ],
				'section_gap'   => [ 'label' => __( 'Space between homepage sections', 'webgram' ), 'type' => 'dimensions', 'min' => 24, 'max' => 160, 'unit' => 'px' ],
				'border_width'  => [ 'label' => __( 'Border width', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 3, 'unit' => 'px' ],
			],
		],
		'sidebar' => [
			'label'  => __( 'Sidebar', 'webgram' ),
			'fields' => [
				'sidebar_width'  => [ 'label' => __( 'Sidebar width', 'webgram' ), 'type' => 'range', 'min' => 20, 'max' => 35, 'unit' => '%' ],
				'sidebar_sticky' => [ 'label' => __( 'Sticky sidebar', 'webgram' ), 'type' => 'switch' ],
			],
		],
	],
];
