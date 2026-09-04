<?php
/**
 * Typography section.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_fonts = [
	'Inter'             => 'Inter',
	'Manrope'           => 'Manrope',
	'DM Sans'           => 'DM Sans',
	'Plus Jakarta Sans' => 'Plus Jakarta Sans',
	'Poppins'           => 'Poppins',
	'Outfit'            => 'Outfit',
	'Playfair Display'  => 'Playfair Display',
	'system'            => __( 'System font stack', 'webgram' ),
];

return [
	'typography' => [
		'title'    => __( 'Typography', 'webgram' ),
		'priority' => 20,
		'fields'   => [
			'font_source'    => [
				'label'       => __( 'Font loading', 'webgram' ),
				'type'        => 'select',
				'choices'     => [
					'local'  => __( 'Self-hosted (Inter and Manrope, no external requests)', 'webgram' ),
					'google' => __( 'Google Fonts (any family below)', 'webgram' ),
				],
				'description' => __( 'Self-hosted keeps the site GDPR-friendly and slightly faster. Choose Google Fonts to use other families.', 'webgram' ),
			],
			'font_body'      => [
				'label'     => __( 'Body font', 'webgram' ),
				'type'      => 'select',
				'choices'   => $webgram_fonts,
				'transport' => 'postMessage',
			],
			'font_heading'   => [
				'label'     => __( 'Heading font', 'webgram' ),
				'type'      => 'select',
				'choices'   => $webgram_fonts,
				'transport' => 'postMessage',
			],
			'font_size_base' => [
				'label'       => __( 'Base font size (px)', 'webgram' ),
				'type'        => 'number',
				'input_attrs' => [ 'min' => 14, 'max' => 20, 'step' => 1 ],
				'transport'   => 'postMessage',
			],
			'heading_weight' => [
				'label'     => __( 'Heading weight', 'webgram' ),
				'type'      => 'select',
				'choices'   => [ 500 => '500', 600 => '600', 700 => '700', 800 => '800' ],
				'transport' => 'postMessage',
			],
		],
	],
];
