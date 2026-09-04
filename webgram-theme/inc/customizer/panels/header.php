<?php
/**
 * Header section (preset controls; the drag-and-drop builder lands in Phase 1 and reads the same settings).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'header' => [
		'title'    => __( 'Header', 'webgram' ),
		'priority' => 40,
		'fields'   => [
			'topbar_enabled'        => [ 'label' => __( 'Show announcement bar', 'webgram' ), 'type' => 'checkbox' ],
			'topbar_text'           => [ 'label' => __( 'Announcement text', 'webgram' ), 'type' => 'text', 'transport' => 'postMessage' ],
			'header_sticky'         => [ 'label' => __( 'Sticky header', 'webgram' ), 'type' => 'checkbox' ],
			'header_search'         => [ 'label' => __( 'Show search bar', 'webgram' ), 'type' => 'checkbox' ],
			'header_deliver_to'     => [ 'label' => __( 'Show "Deliver to" location', 'webgram' ), 'type' => 'checkbox' ],
			'secondary_bar_enabled' => [ 'label' => __( 'Show secondary menu bar below header', 'webgram' ), 'type' => 'checkbox' ],
		],
	],
	'footer' => [
		'title'    => __( 'Footer', 'webgram' ),
		'priority' => 50,
		'fields'   => [
			'footer_columns'   => [
				'label'   => __( 'Widget columns', 'webgram' ),
				'type'    => 'select',
				'choices' => [ 2 => '2', 3 => '3', 4 => '4' ],
			],
			'footer_copyright' => [ 'label' => __( 'Copyright text', 'webgram' ), 'type' => 'text', 'transport' => 'postMessage' ],
		],
	],
];
