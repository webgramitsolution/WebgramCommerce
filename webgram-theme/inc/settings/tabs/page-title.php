<?php
/**
 * Tab: Page title.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'page_title',
	'label'    => __( 'Page title', 'webgram' ),
	'icon'     => 'heading',
	'priority' => 70,
	'sections' => [
		'title' => [
			'fields' => [
				'page_title_show'     => [ 'label' => __( 'Show page title band', 'webgram' ), 'type' => 'switch' ],
				'page_title_size'     => [ 'label' => __( 'Height', 'webgram' ), 'type' => 'select', 'choices' => [ 'small' => __( 'Small', 'webgram' ), 'medium' => __( 'Medium', 'webgram' ), 'large' => __( 'Large', 'webgram' ) ], 'show_if' => [ 'page_title_show', '==', true ] ],
				'page_title_align'    => [ 'label' => __( 'Alignment', 'webgram' ), 'type' => 'radio', 'choices' => [ 'start' => __( 'Start', 'webgram' ), 'center' => __( 'Center', 'webgram' ) ], 'show_if' => [ 'page_title_show', '==', true ] ],
				'page_title_bg'       => [ 'label' => __( 'Background color', 'webgram' ), 'type' => 'color', 'show_if' => [ 'page_title_show', '==', true ] ],
				'page_title_bg_image' => [ 'label' => __( 'Background image', 'webgram' ), 'type' => 'image', 'show_if' => [ 'page_title_show', '==', true ] ],
				'page_title_color'    => [ 'label' => __( 'Text color', 'webgram' ), 'type' => 'color', 'show_if' => [ 'page_title_show', '==', true ] ],
				'breadcrumb_show'     => [ 'label' => __( 'Breadcrumb', 'webgram' ), 'type' => 'switch', 'description' => __( 'Uses Yoast or Rank Math breadcrumbs when active, otherwise the theme breadcrumb.', 'webgram' ) ],
			],
		],
	],
];
