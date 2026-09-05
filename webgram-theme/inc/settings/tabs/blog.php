<?php
/**
 * Tab: Blog.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'blog',
	'label'    => __( 'Blog', 'webgram' ),
	'icon'     => 'edit',
	'priority' => 110,
	'sections' => [
		'archive' => [
			'label'  => __( 'Blog archive', 'webgram' ),
			'fields' => [
				'blog_card_style'     => [ 'label' => __( 'Card style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'grid' => __( 'Grid', 'webgram' ), 'list' => __( 'List', 'webgram' ), 'masonry' => __( 'Masonry', 'webgram' ) ] ],
				'blog_columns'        => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'range', 'min' => 1, 'max' => 4 ],
				'blog_meta'           => [ 'label' => __( 'Meta shown', 'webgram' ), 'type' => 'multicheck', 'choices' => [ 'date' => __( 'Date', 'webgram' ), 'author' => __( 'Author', 'webgram' ), 'category' => __( 'Category', 'webgram' ), 'comments' => __( 'Comments', 'webgram' ) ] ],
				'blog_excerpt_length' => [ 'label' => __( 'Excerpt length (words)', 'webgram' ), 'type' => 'number', 'min' => 10, 'max' => 80 ],
				'blog_featured_image' => [ 'label' => __( 'Show featured images', 'webgram' ), 'type' => 'switch', 'description' => __( 'Applies to post cards and the single post header.', 'webgram' ) ],
				'blog_pagination'     => [ 'label' => __( 'Pagination', 'webgram' ), 'type' => 'select', 'choices' => [ 'numbers' => __( 'Page numbers', 'webgram' ), 'load_more' => __( 'Load more button', 'webgram' ) ] ],
			],
		],
		'single' => [
			'label'  => __( 'Single post', 'webgram' ),
			'fields' => [
				'blog_sidebar_single' => [ 'label' => __( 'Sidebar on single posts', 'webgram' ), 'type' => 'switch' ],
				'blog_related'        => [ 'label' => __( 'Related posts', 'webgram' ), 'type' => 'switch' ],
				'blog_related_count'  => [ 'label' => __( 'Related posts count', 'webgram' ), 'type' => 'range', 'min' => 2, 'max' => 6, 'show_if' => [ 'blog_related', '==', true ] ],
				'blog_share'          => [ 'label' => __( 'Share buttons', 'webgram' ), 'type' => 'switch', 'description' => __( 'Networks are chosen under Social profiles.', 'webgram' ) ],
			],
		],
	],
];
