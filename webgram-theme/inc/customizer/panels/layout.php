<?php
/**
 * Layout and shape section.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'layout' => [
		'title'    => __( 'Layout and shape', 'webgram' ),
		'priority' => 30,
		'fields'   => [
			'container_width' => [
				'label'       => __( 'Container width (px)', 'webgram' ),
				'type'        => 'number',
				'input_attrs' => [ 'min' => 1140, 'max' => 1600, 'step' => 10 ],
				'transport'   => 'postMessage',
			],
			'radius_scale'    => [
				'label'     => __( 'Corner style', 'webgram' ),
				'type'      => 'select',
				'choices'   => [
					'sharp'   => __( 'Sharp', 'webgram' ),
					'soft'    => __( 'Soft', 'webgram' ),
					'rounded' => __( 'Rounded', 'webgram' ),
					'pill'    => __( 'Pill', 'webgram' ),
				],
				'transport' => 'postMessage',
			],
			'button_radius'   => [
				'label'       => __( 'Button corner radius (px)', 'webgram' ),
				'type'        => 'number',
				'input_attrs' => [ 'min' => 0, 'max' => 40, 'step' => 1 ],
				'transport'   => 'postMessage',
			],
			'shop_layout'     => [
				'label'   => __( 'Shop layout', 'webgram' ),
				'type'    => 'select',
				'choices' => [
					'sidebar-left'  => __( 'Filters on the left', 'webgram' ),
					'sidebar-right' => __( 'Filters on the right', 'webgram' ),
					'full-width'    => __( 'No sidebar', 'webgram' ),
				],
			],
			'blog_layout'     => [
				'label'   => __( 'Blog layout', 'webgram' ),
				'type'    => 'select',
				'choices' => [
					'sidebar-right' => __( 'Sidebar on the right', 'webgram' ),
					'sidebar-left'  => __( 'Sidebar on the left', 'webgram' ),
					'full-width'    => __( 'No sidebar', 'webgram' ),
				],
			],
		],
	],
];
