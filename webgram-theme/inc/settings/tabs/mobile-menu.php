<?php
/**
 * Tab: Mobile menu (off-canvas drawer).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'mobile_menu',
	'label'    => __( 'Mobile menu', 'webgram' ),
	'icon'     => 'menu',
	'priority' => 45,
	'sections' => [
		'drawer' => [
			'fields' => [
				'mobile_menu_position'   => [ 'label' => __( 'Drawer position', 'webgram' ), 'type' => 'radio', 'choices' => [ 'left' => __( 'Left', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ] ],
				'mobile_menu_width'      => [ 'label' => __( 'Drawer width', 'webgram' ), 'type' => 'number', 'min' => 260, 'max' => 480, 'unit' => 'px' ],
				'mobile_menu_tabs'       => [ 'label' => __( 'Menu and Categories tabs', 'webgram' ), 'type' => 'switch', 'description' => __( 'Categories tab lists WooCommerce product categories with thumbnails.', 'webgram' ) ],
				'mobile_menu_categories' => [ 'label' => __( 'Categories shown', 'webgram' ), 'type' => 'select', 'choices' => [ 'top' => __( 'Top level only', 'webgram' ), 'all' => __( 'All levels (accordion)', 'webgram' ) ], 'show_if' => [ 'mobile_menu_tabs', '==', true ] ],
				'mobile_menu_search'     => [ 'label' => __( 'Search field in drawer', 'webgram' ), 'type' => 'switch' ],
				'mobile_menu_account'    => [ 'label' => __( 'Account links at the bottom', 'webgram' ), 'type' => 'switch' ],
			],
		],
	],
];
