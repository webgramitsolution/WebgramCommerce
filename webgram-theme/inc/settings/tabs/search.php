<?php
/**
 * Tab: Search.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'search',
	'label'    => __( 'Search', 'webgram' ),
	'icon'     => 'search',
	'priority' => 50,
	'sections' => [
		'live' => [
			'label'  => __( 'Live search', 'webgram' ),
			'fields' => [
				'search_live'          => [ 'label' => __( 'Live suggestions', 'webgram' ), 'type' => 'switch', 'description' => __( 'Shows products, categories and popular searches while typing.', 'webgram' ) ],
				'search_min_chars'     => [ 'label' => __( 'Start after characters', 'webgram' ), 'type' => 'number', 'min' => 1, 'max' => 5, 'show_if' => [ 'search_live', '==', true ] ],
				'search_results_count' => [ 'label' => __( 'Results shown', 'webgram' ), 'type' => 'number', 'min' => 3, 'max' => 12, 'show_if' => [ 'search_live', '==', true ] ],
				'search_categories'    => [ 'label' => __( 'Include categories', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'search_live', '==', true ] ],
				'search_scope'         => [ 'label' => __( 'Search in', 'webgram' ), 'type' => 'select', 'choices' => [ 'product' => __( 'Products only', 'webgram' ), 'all' => __( 'Products and posts', 'webgram' ) ] ],
				'search_popular'       => [ 'label' => __( 'Popular searches', 'webgram' ), 'type' => 'textarea', 'rows' => 4, 'description' => __( 'One per line. Shown before the visitor types.', 'webgram' ) ],
				'voice'                => [ 'label' => __( 'Voice search', 'webgram' ), 'type' => 'info', 'content' => webgram_has_core() ? sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=webgram-core' ) ), esc_html__( 'Configured in Webgram Core > Modules > Voice search.', 'webgram' ) ) : esc_html__( 'Requires the Webgram Core plugin.', 'webgram' ) ],
			],
		],
		'page' => [
			'label'  => __( 'Search results page', 'webgram' ),
			'fields' => [
				'search_page_columns' => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'range', 'min' => 2, 'max' => 5 ],
			],
		],
	],
];
