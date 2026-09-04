<?php
/**
 * Tab: Footer.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'footer',
	'label'    => __( 'Footer', 'webgram' ),
	'icon'     => 'footer',
	'priority' => 80,
	'sections' => [
		'builder' => [
			'fields' => [
				'footer_builder_link'     => [ 'label' => __( 'Footer layout', 'webgram' ), 'type' => 'link', 'url' => admin_url( 'admin.php?page=webgram-footer' ), 'button' => __( 'Open Footer Builder', 'webgram' ), 'description' => __( 'Columns, menus, social icons and widget areas are arranged in the builder.', 'webgram' ) ],
				'footer_headings_divider' => [ 'label' => __( 'Thin divider under column headings', 'webgram' ), 'type' => 'switch' ],
				'footer_columns'          => [ 'label' => __( 'Widget areas', 'webgram' ), 'type' => 'range', 'min' => 1, 'max' => 6, 'description' => __( 'Number of "Footer column" widget areas registered under Appearance > Widgets.', 'webgram' ) ],
			],
		],
		'bottom' => [
			'label'  => __( 'Bottom row', 'webgram' ),
			'fields' => [
				'footer_bottom_show'   => [ 'label' => __( 'Show bottom row', 'webgram' ), 'type' => 'switch' ],
				'footer_copyright'     => [ 'label' => __( 'Copyright text', 'webgram' ), 'type' => 'html', 'rows' => 2, 'description' => __( 'Use {year} and {site} as placeholders.', 'webgram' ) ],
				'footer_payment_icons' => [ 'label' => __( 'Payment icons', 'webgram' ), 'type' => 'multicheck', 'choices' => webgram_payment_icon_choices() ],
			],
		],
	],
];
