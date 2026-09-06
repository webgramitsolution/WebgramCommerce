<?php
/**
 * Tab: Typography.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_fonts = webgram_font_choices();
$webgram_size  = [ 'type' => 'dimensions', 'min' => 10, 'max' => 96, 'unit' => 'px' ];

return [
	'id'       => 'typography',
	'label'    => __( 'Typography', 'webgram' ),
	'icon'     => 'type',
	'priority' => 90,
	'sections' => [
		'fonts' => [
			'label'  => __( 'Font families', 'webgram' ),
			'fields' => [
				'font_source'      => [ 'label' => __( 'Font loading', 'webgram' ), 'type' => 'radio', 'choices' => [ 'local' => __( 'Self-hosted (Inter, Manrope)', 'webgram' ), 'google' => __( 'Google Fonts', 'webgram' ) ], 'description' => __( 'Self-hosted fonts avoid third-party requests (GDPR friendly).', 'webgram' ) ],
				'font_body'        => [ 'label' => __( 'Body font', 'webgram' ), 'type' => 'select', 'choices' => $webgram_fonts ],
				'font_heading'     => [ 'label' => __( 'Heading font', 'webgram' ), 'type' => 'select', 'choices' => $webgram_fonts ],
				'font_menu'        => [ 'label' => __( 'Menu font', 'webgram' ), 'type' => 'select', 'choices' => [ 'inherit' => __( 'Same as body', 'webgram' ) ] + $webgram_fonts ],
				'font_button'      => [ 'label' => __( 'Button font', 'webgram' ), 'type' => 'select', 'choices' => [ 'inherit' => __( 'Same as body', 'webgram' ) ] + $webgram_fonts ],
				'font_custom_name' => [ 'label' => __( 'Custom font name', 'webgram' ), 'type' => 'text', 'description' => __( 'Used when "Custom" is selected above.', 'webgram' ) ],
				'font_custom_url'  => [ 'label' => __( 'Custom font file (woff2 URL)', 'webgram' ), 'type' => 'url', 'description' => __( 'Upload the file in Media Library and paste its URL.', 'webgram' ) ],
			],
		],
		'body' => [
			'label'  => __( 'Body', 'webgram' ),
			'fields' => [
				'font_size_base'   => [ 'label' => __( 'Base font size', 'webgram' ) ] + $webgram_size,
				'body_line_height' => [ 'label' => __( 'Line height', 'webgram' ), 'type' => 'number', 'min' => 1, 'max' => 2.2, 'step' => 0.05 ],
			],
		],
		'headings' => [
			'label'  => __( 'Headings', 'webgram' ),
			'fields' => [
				'heading_weight'         => [ 'label' => __( 'Weight', 'webgram' ), 'type' => 'select', 'choices' => [ 500 => '500', 600 => '600', 700 => '700', 800 => '800' ] ],
				'heading_letter_spacing' => [ 'label' => __( 'Letter spacing (em)', 'webgram' ), 'type' => 'number', 'min' => -0.1, 'max' => 0.2, 'step' => 0.005 ],
				'heading_line_height'    => [ 'label' => __( 'Line height', 'webgram' ), 'type' => 'number', 'min' => 0.9, 'max' => 1.8, 'step' => 0.05 ],
				'font_size_h1'           => [ 'label' => 'H1' ] + $webgram_size,
				'font_size_h2'           => [ 'label' => 'H2' ] + $webgram_size,
				'font_size_h3'           => [ 'label' => 'H3' ] + $webgram_size,
				'font_size_h4'           => [ 'label' => 'H4' ] + $webgram_size,
				'font_size_h5'           => [ 'label' => 'H5' ] + $webgram_size,
				'font_size_h6'           => [ 'label' => 'H6' ] + $webgram_size,
			],
		],
		'menu' => [
			'label'  => __( 'Menu and buttons', 'webgram' ),
			'fields' => [
				'menu_font_size'        => [ 'label' => __( 'Menu font size', 'webgram' ), 'type' => 'number', 'min' => 11, 'max' => 20, 'unit' => 'px' ],
				'menu_font_weight'      => [ 'label' => __( 'Menu weight', 'webgram' ), 'type' => 'select', 'choices' => [ 400 => '400', 500 => '500', 600 => '600', 700 => '700' ] ],
				'menu_letter_spacing'   => [ 'label' => __( 'Menu letter spacing (em)', 'webgram' ), 'type' => 'number', 'min' => -0.05, 'max' => 0.2, 'step' => 0.005 ],
				'button_font_weight'    => [ 'label' => __( 'Button weight', 'webgram' ), 'type' => 'select', 'choices' => [ 500 => '500', 600 => '600', 700 => '700' ] ],
				'button_letter_spacing' => [ 'label' => __( 'Button letter spacing (em)', 'webgram' ), 'type' => 'number', 'min' => -0.05, 'max' => 0.2, 'step' => 0.005 ],
				'button_uppercase'      => [ 'label' => __( 'Uppercase buttons', 'webgram' ), 'type' => 'switch' ],
			],
		],
	],
];
