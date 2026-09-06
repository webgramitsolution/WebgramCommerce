<?php
/**
 * Tab: Styles and colors. Every color field maps 1:1 to a design token.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_colors = static function ( array $labels ): array {
	$out = [];
	foreach ( $labels as $id => $label ) {
		$out[ $id ] = [ 'label' => $label, 'type' => 'color' ];
	}
	return $out;
};

return [
	'id'       => 'styles',
	'label'    => __( 'Styles and colors', 'webgram' ),
	'icon'     => 'palette',
	'priority' => 100,
	'sections' => [
		'brand' => [
			'label'  => __( 'Brand colors', 'webgram' ),
			'fields' => $webgram_colors(
				[
					'color_primary'       => __( 'Primary', 'webgram' ),
					'color_primary_hover' => __( 'Primary (hover)', 'webgram' ),
					'color_secondary'     => __( 'Secondary (dark navy bands)', 'webgram' ),
					'color_accent'        => __( 'Accent (gold)', 'webgram' ),
				]
			),
		],
		'text' => [
			'label'  => __( 'Text and surfaces', 'webgram' ),
			'fields' => $webgram_colors(
				[
					'color_heading'    => __( 'Headings', 'webgram' ),
					'color_text'       => __( 'Body text', 'webgram' ),
					'color_text_muted' => __( 'Muted text', 'webgram' ),
					'color_bg'         => __( 'Page background', 'webgram' ),
					'color_bg_alt'     => __( 'Alternate background (cream)', 'webgram' ),
					'color_surface'    => __( 'Cards and surfaces', 'webgram' ),
					'color_border'     => __( 'Borders', 'webgram' ),
					'color_link'       => __( 'Links', 'webgram' ),
					'color_link_hover' => __( 'Links (hover)', 'webgram' ),
				]
			),
		],
		'state' => [
			'label'  => __( 'Shop and status colors', 'webgram' ),
			'fields' => $webgram_colors(
				[
					'color_price'   => __( 'Price', 'webgram' ),
					'color_sale'    => __( 'Sale and savings', 'webgram' ),
					'color_star'    => __( 'Rating stars', 'webgram' ),
					'color_success' => __( 'Success', 'webgram' ),
					'color_warning' => __( 'Warning', 'webgram' ),
					'color_danger'  => __( 'Danger', 'webgram' ),
				]
			),
		],
		'header_footer' => [
			'label'  => __( 'Header and footer', 'webgram' ),
			'fields' => $webgram_colors(
				[
					'color_topbar_bg'   => __( 'Top bar background', 'webgram' ),
					'color_topbar_text' => __( 'Top bar text', 'webgram' ),
					'color_header_bg'   => __( 'Header background', 'webgram' ),
					'color_header_text' => __( 'Header text and icons', 'webgram' ),
					'color_footer_bg'   => __( 'Footer background', 'webgram' ),
					'color_footer_text' => __( 'Footer text', 'webgram' ),
				]
			),
		],
		'shape' => [
			'label'  => __( 'Shape and components', 'webgram' ),
			'fields' => [
				'radius_scale'  => [ 'label' => __( 'Corner style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'sharp' => __( 'Sharp', 'webgram' ), 'soft' => __( 'Soft', 'webgram' ), 'rounded' => __( 'Rounded', 'webgram' ), 'pill' => __( 'Pill', 'webgram' ) ] ],
				'shadow_scale'  => [ 'label' => __( 'Shadows', 'webgram' ), 'type' => 'radio', 'choices' => [ 'none' => __( 'None', 'webgram' ), 'soft' => __( 'Soft', 'webgram' ), 'normal' => __( 'Normal', 'webgram' ), 'strong' => __( 'Strong', 'webgram' ) ] ],
				'button_style'  => [ 'label' => __( 'Button style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'solid' => __( 'Solid', 'webgram' ), 'outline' => __( 'Outline', 'webgram' ), 'soft' => __( 'Soft', 'webgram' ) ] ],
				'button_radius' => [ 'label' => __( 'Button corner radius', 'webgram' ), 'type' => 'number', 'min' => 0, 'max' => 40, 'unit' => 'px' ],
				'button_shine'  => [ 'label' => __( 'Diagonal shine on primary buttons', 'webgram' ), 'type' => 'switch' ],
				'form_style'    => [ 'label' => __( 'Form fields', 'webgram' ), 'type' => 'radio', 'choices' => [ 'filled' => __( 'Filled', 'webgram' ), 'outline' => __( 'Outline', 'webgram' ) ] ],
				'card_style'    => [ 'label' => __( 'Product card style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'bordered' => __( 'Bordered', 'webgram' ), 'shadow' => __( 'Shadow', 'webgram' ), 'flat' => __( 'Flat', 'webgram' ) ] ],
				'badge_style'   => [ 'label' => __( 'Badge shape', 'webgram' ), 'type' => 'radio', 'choices' => [ 'wave' => __( 'Wave', 'webgram' ), 'pill' => __( 'Pill', 'webgram' ), 'rectangle' => __( 'Rectangle', 'webgram' ), 'ribbon' => __( 'Ribbon', 'webgram' ) ] ],
			],
		],
	],
];
