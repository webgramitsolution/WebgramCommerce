<?php
/**
 * Tab: Mobile bottom navbar.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'mobile_navbar',
	'label'    => __( 'Mobile bottom navbar', 'webgram' ),
	'icon'     => 'smartphone',
	'priority' => 40,
	'sections' => [
		'navbar' => [
			'fields' => [
				'mobile_nav_enabled'     => [ 'label' => __( 'Enable bottom navbar', 'webgram' ), 'type' => 'switch', 'description' => __( 'Fixed bar on screens under 992px with safe-area padding.', 'webgram' ) ],
				'mobile_nav_style'       => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'select', 'choices' => [ 'light' => __( 'Light', 'webgram' ), 'dark' => __( 'Dark', 'webgram' ), 'primary' => __( 'Primary color', 'webgram' ) ], 'show_if' => [ 'mobile_nav_enabled', '==', true ] ],
				'mobile_nav_labels'      => [ 'label' => __( 'Show labels', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'mobile_nav_enabled', '==', true ] ],
				'mobile_nav_hide_scroll' => [ 'label' => __( 'Hide on scroll down', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'mobile_nav_enabled', '==', true ] ],
				'mobile_nav_items'       => [
					'label'       => __( 'Items', 'webgram' ),
					'type'        => 'repeater',
					'max'         => 6,
					'full'        => true,
					'title_field' => 'label',
					'add_label'   => __( 'Add item', 'webgram' ),
					'show_if'     => [ 'mobile_nav_enabled', '==', true ],
					'fields'      => [
						'action' => [ 'label' => __( 'Action', 'webgram' ), 'type' => 'select', 'choices' => [ 'home' => __( 'Home', 'webgram' ), 'shop' => __( 'Shop', 'webgram' ), 'search' => __( 'Search', 'webgram' ), 'wishlist' => __( 'Wishlist', 'webgram' ), 'compare' => __( 'Compare', 'webgram' ), 'cart' => __( 'Cart', 'webgram' ), 'account' => __( 'Account', 'webgram' ), 'menu' => __( 'Menu', 'webgram' ), 'custom' => __( 'Custom link', 'webgram' ) ] ],
						'icon'   => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon' ],
						'label'  => [ 'label' => __( 'Label', 'webgram' ), 'type' => 'text' ],
						'link'   => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'show_if' => [ 'action', '==', 'custom' ] ],
					],
				],
			],
		],
	],
];
