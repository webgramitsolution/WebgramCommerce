<?php
/**
 * Colors section. Every field maps 1:1 to a design token.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_color_fields = [];
$webgram_color_labels = [
	'color_primary'       => __( 'Primary', 'webgram' ),
	'color_primary_hover' => __( 'Primary (hover)', 'webgram' ),
	'color_secondary'     => __( 'Secondary', 'webgram' ),
	'color_accent'        => __( 'Accent', 'webgram' ),
	'color_heading'       => __( 'Headings', 'webgram' ),
	'color_text'          => __( 'Body text', 'webgram' ),
	'color_text_muted'    => __( 'Muted text', 'webgram' ),
	'color_bg'            => __( 'Page background', 'webgram' ),
	'color_bg_alt'        => __( 'Alternate background', 'webgram' ),
	'color_surface'       => __( 'Cards and surfaces', 'webgram' ),
	'color_border'        => __( 'Borders', 'webgram' ),
	'color_price'         => __( 'Price', 'webgram' ),
	'color_sale'          => __( 'Sale and savings', 'webgram' ),
	'color_star'          => __( 'Rating stars', 'webgram' ),
	'color_topbar_bg'     => __( 'Top bar background', 'webgram' ),
	'color_topbar_text'   => __( 'Top bar text', 'webgram' ),
	'color_header_bg'     => __( 'Header background', 'webgram' ),
	'color_header_text'   => __( 'Header text and icons', 'webgram' ),
	'color_footer_bg'     => __( 'Footer background', 'webgram' ),
	'color_footer_text'   => __( 'Footer text', 'webgram' ),
];

foreach ( $webgram_color_labels as $webgram_id => $webgram_label ) {
	$webgram_color_fields[ $webgram_id ] = [
		'label'     => $webgram_label,
		'type'      => 'color',
		'transport' => 'postMessage',
	];
}

return [
	'colors' => [
		'title'    => __( 'Colors', 'webgram' ),
		'priority' => 10,
		'fields'   => $webgram_color_fields,
	],
];
