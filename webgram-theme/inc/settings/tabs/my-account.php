<?php
/**
 * Tab: My account (login page and dashboard).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'my_account',
	'label'    => __( 'My account', 'webgram' ),
	'icon'     => 'user',
	'priority' => 150,
	'sections' => [
		'login' => [
			'label'  => __( 'Login and register page', 'webgram' ),
			'fields' => [
				'login_image'             => [ 'label' => __( 'Right side image (desktop)', 'webgram' ), 'type' => 'image' ],
				'login_image_mobile'      => [ 'label' => __( 'Image (mobile)', 'webgram' ), 'type' => 'image' ],
				'login_image_mobile_show' => [ 'label' => __( 'Show image on mobile', 'webgram' ), 'type' => 'switch' ],
				'login_trust_logos'       => [
					'label'  => __( 'Trust logos under the form', 'webgram' ),
					'type'   => 'repeater',
					'max'    => 8,
					'fields' => [
						'image' => [ 'label' => __( 'Logo', 'webgram' ), 'type' => 'image' ],
						'label' => [ 'label' => __( 'Alt text', 'webgram' ), 'type' => 'text' ],
					],
				],
			],
		],
		'dashboard' => [
			'label'  => __( 'Dashboard', 'webgram' ),
			'fields' => [
				'account_dashboard_cards' => [ 'label' => __( 'Dashboard cards', 'webgram' ), 'type' => 'switch' ],
				'account_nav_icons'       => [ 'label' => __( 'Navigation icons', 'webgram' ), 'type' => 'switch' ],
			],
		],
	],
];
