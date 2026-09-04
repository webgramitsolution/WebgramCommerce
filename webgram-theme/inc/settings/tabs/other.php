<?php
/**
 * Tab: Other (404 page and links).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'other',
	'label'    => __( 'Other', 'webgram' ),
	'icon'     => 'more',
	'priority' => 190,
	'sections' => [
		'404' => [
			'label'  => __( '404 page', 'webgram' ),
			'fields' => [
				'page_404_block'  => [ 'label' => __( 'Replace with HTML Block', 'webgram' ), 'type' => 'html_block' ],
				'page_404_title'  => [ 'label' => __( 'Title', 'webgram' ), 'type' => 'text' ],
				'page_404_text'   => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'textarea', 'rows' => 3 ],
				'page_404_image'  => [ 'label' => __( 'Image', 'webgram' ), 'type' => 'image' ],
				'page_404_button' => [ 'label' => __( 'Button label', 'webgram' ), 'type' => 'text' ],
				'page_404_search' => [ 'label' => __( 'Show search form', 'webgram' ), 'type' => 'switch' ],
			],
		],
		'links' => [
			'label'  => __( 'Site tools', 'webgram' ),
			'fields' => [
				'maintenance_link' => [ 'label' => __( 'Maintenance and coming soon', 'webgram' ), 'type' => 'info', 'content' => webgram_has_core( 'site_tools' ) ? sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=webgram&tab=maintenance' ) ), esc_html__( 'Open the Maintenance tab.', 'webgram' ) ) : esc_html__( 'Provided by the Webgram Core plugin (Site Tools module).', 'webgram' ) ],
			],
		],
	],
];
