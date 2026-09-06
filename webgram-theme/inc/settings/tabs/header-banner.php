<?php
/**
 * Tab: Header banner (promotional strip above or below the header).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'header_banner',
	'label'    => __( 'Header banner', 'webgram' ),
	'icon'     => 'megaphone',
	'priority' => 30,
	'sections' => [
		'banner' => [
			'fields' => [
				'header_banner_enabled'  => [ 'label' => __( 'Enable header banner', 'webgram' ), 'type' => 'switch' ],
				'header_banner_position' => [ 'label' => __( 'Position', 'webgram' ), 'type' => 'radio', 'choices' => [ 'above' => __( 'Above header', 'webgram' ), 'below' => __( 'Below header', 'webgram' ) ], 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_type'     => [ 'label' => __( 'Content', 'webgram' ), 'type' => 'radio', 'choices' => [ 'text' => __( 'Text', 'webgram' ), 'image' => __( 'Image', 'webgram' ), 'block' => __( 'HTML Block', 'webgram' ) ], 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_text'     => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'html', 'rows' => 2, 'show_if' => [ 'header_banner_type', '==', 'text' ] ],
				'header_banner_image'    => [ 'label' => __( 'Image', 'webgram' ), 'type' => 'image', 'show_if' => [ 'header_banner_type', '==', 'image' ] ],
				'header_banner_block'    => [ 'label' => __( 'HTML Block', 'webgram' ), 'type' => 'html_block', 'show_if' => [ 'header_banner_type', '==', 'block' ] ],
				'header_banner_link'     => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_bg'       => [ 'label' => __( 'Background', 'webgram' ), 'type' => 'color', 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_color'    => [ 'label' => __( 'Text color', 'webgram' ), 'type' => 'color', 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_close'    => [ 'label' => __( 'Close button', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'header_banner_enabled', '==', true ] ],
				'header_banner_remember' => [ 'label' => __( 'Stay closed for', 'webgram' ), 'type' => 'number', 'min' => 0, 'max' => 365, 'unit' => __( 'days', 'webgram' ), 'description' => __( '0 remembers for the session only.', 'webgram' ), 'show_if' => [ 'header_banner_close', '==', true ] ],
			],
		],
	],
];
