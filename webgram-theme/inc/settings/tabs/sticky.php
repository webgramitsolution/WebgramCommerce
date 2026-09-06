<?php
/**
 * Tab: Sticky navigation.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'sticky',
	'label'    => __( 'Sticky navigation', 'webgram' ),
	'icon'     => 'pin',
	'priority' => 60,
	'sections' => [
		'sticky' => [
			'fields' => [
				'sticky_enabled'        => [ 'label' => __( 'Sticky header', 'webgram' ), 'type' => 'switch' ],
				'sticky_rows'           => [ 'label' => __( 'Rows that stick', 'webgram' ), 'type' => 'multicheck', 'choices' => [ 'top' => __( 'Top bar', 'webgram' ), 'main' => __( 'Main row', 'webgram' ), 'bottom' => __( 'Bottom row (secondary menu)', 'webgram' ) ], 'show_if' => [ 'sticky_enabled', '==', true ] ],
				'sticky_shrink'         => [ 'label' => __( 'Shrink main row to 60px when stuck', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'sticky_enabled', '==', true ] ],
				'sticky_shadow'         => [ 'label' => __( 'Shadow when stuck', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'sticky_enabled', '==', true ] ],
				'sticky_hide_on_scroll' => [ 'label' => __( 'Hide on scroll down, show on scroll up', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'sticky_enabled', '==', true ] ],
				'sticky_mobile'         => [ 'label' => __( 'Sticky on mobile', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'sticky_enabled', '==', true ] ],
			],
		],
	],
];
