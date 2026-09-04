<?php
/**
 * Tab: General.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'general',
	'label'    => __( 'General', 'webgram' ),
	'icon'     => 'settings',
	'priority' => 10,
	'sections' => [
		'site' => [
			'label'  => __( 'Site layout', 'webgram' ),
			'fields' => [
				'site_layout'     => [ 'label' => __( 'Site layout', 'webgram' ), 'type' => 'radio', 'choices' => [ 'wide' => __( 'Wide', 'webgram' ), 'boxed' => __( 'Boxed', 'webgram' ) ] ],
				'container_width' => [ 'label' => __( 'Container width', 'webgram' ), 'type' => 'number', 'min' => 1140, 'max' => 1600, 'step' => 10, 'unit' => 'px' ],
				'boxed_bg'        => [ 'label' => __( 'Boxed background', 'webgram' ), 'type' => 'color', 'show_if' => [ 'site_layout', '==', 'boxed' ] ],
				'rtl_force'       => [ 'label' => __( 'Force RTL layout', 'webgram' ), 'type' => 'switch', 'description' => __( 'WordPress switches to RTL automatically for RTL languages. Turn this on only to preview RTL on an LTR site.', 'webgram' ) ],
			],
		],
		'extras' => [
			'label'  => __( 'Extras', 'webgram' ),
			'fields' => [
				'preloader'          => [ 'label' => __( 'Preloader', 'webgram' ), 'type' => 'switch', 'description' => __( 'Simple spinner shown until the page has loaded.', 'webgram' ) ],
				'preloader_color'    => [ 'label' => __( 'Preloader color', 'webgram' ), 'type' => 'color', 'show_if' => [ 'preloader', '==', true ] ],
				'back_to_top'        => [ 'label' => __( 'Back to top button', 'webgram' ), 'type' => 'switch' ],
				'back_to_top_offset' => [ 'label' => __( 'Show after scrolling', 'webgram' ), 'type' => 'number', 'min' => 100, 'max' => 2000, 'unit' => 'px', 'show_if' => [ 'back_to_top', '==', true ] ],
				'favicon'            => [ 'label' => __( 'Favicon and logo', 'webgram' ), 'type' => 'link', 'url' => admin_url( 'customize.php?autofocus[section]=title_tagline' ), 'button' => __( 'Open Site Identity', 'webgram' ), 'description' => __( 'Logo, site icon and tagline stay in the WordPress Customizer.', 'webgram' ) ],
			],
		],
	],
];
