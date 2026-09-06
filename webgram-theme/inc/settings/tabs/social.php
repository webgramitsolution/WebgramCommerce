<?php
/**
 * Tab: Social profiles.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'social',
	'label'    => __( 'Social profiles', 'webgram' ),
	'icon'     => 'share',
	'priority' => 160,
	'sections' => [
		'profiles' => [
			'fields' => [
				'social_links' => [
					'label'       => __( 'Profiles', 'webgram' ),
					'type'        => 'repeater',
					'full'        => true,
					'max'         => 12,
					'title_field' => 'network',
					'fields'      => [
						'network' => [ 'label' => __( 'Network', 'webgram' ), 'type' => 'select', 'choices' => webgram_social_networks() ],
						'url'     => [ 'label' => __( 'URL', 'webgram' ), 'type' => 'url' ],
					],
				],
			],
		],
		'sidebar' => [
			'label'  => __( 'Floating social sidebar', 'webgram' ),
			'fields' => [
				'social_sidebar'         => [ 'label' => __( 'Show floating sidebar', 'webgram' ), 'type' => 'switch', 'description' => __( 'Stacked square buttons on the right edge in brand colors.', 'webgram' ) ],
				'social_sidebar_position' => [ 'label' => __( 'Side', 'webgram' ), 'type' => 'radio', 'choices' => [ 'right' => __( 'Right edge', 'webgram' ), 'left' => __( 'Left edge', 'webgram' ) ], 'show_if' => [ 'social_sidebar', '==', true ] ],
				'social_sidebar_devices' => [ 'label' => __( 'Visible on', 'webgram' ), 'type' => 'multicheck', 'choices' => [ 'desktop' => __( 'Desktop', 'webgram' ), 'tablet' => __( 'Tablet', 'webgram' ), 'mobile' => __( 'Mobile', 'webgram' ) ], 'show_if' => [ 'social_sidebar', '==', true ] ],
			],
		],
		'share' => [
			'label'  => __( 'Share buttons', 'webgram' ),
			'fields' => [
				'social_share_networks' => [ 'label' => __( 'Networks', 'webgram' ), 'type' => 'multicheck', 'choices' => [ 'facebook' => 'Facebook', 'x' => 'X', 'whatsapp' => 'WhatsApp', 'pinterest' => 'Pinterest', 'telegram' => 'Telegram', 'linkedin' => 'LinkedIn', 'email' => __( 'Email', 'webgram' ), 'copy' => __( 'Copy link', 'webgram' ) ] ],
			],
		],
	],
];
