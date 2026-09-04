<?php
/**
 * Tab: Product archive (category pages).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'product_archive',
	'label'    => __( 'Product archive', 'webgram' ),
	'icon'     => 'folder',
	'priority' => 130,
	'sections' => [
		'archive' => [
			'fields' => [
				'archive_banner'        => [ 'label' => __( 'Category banner', 'webgram' ), 'type' => 'switch', 'description' => __( 'Title band with the category image and description.', 'webgram' ) ],
				'archive_banner_height' => [ 'label' => __( 'Banner height', 'webgram' ), 'type' => 'select', 'choices' => [ 'small' => __( 'Small', 'webgram' ), 'medium' => __( 'Medium', 'webgram' ), 'large' => __( 'Large', 'webgram' ) ], 'show_if' => [ 'archive_banner', '==', true ] ],
				'archive_description'   => [ 'label' => __( 'Description position', 'webgram' ), 'type' => 'radio', 'choices' => [ 'top' => __( 'In the banner', 'webgram' ), 'bottom' => __( 'Below products', 'webgram' ), 'hidden' => __( 'Hidden', 'webgram' ) ] ],
				'subcategory_chips'     => [ 'label' => __( 'Subcategory chips above products', 'webgram' ), 'type' => 'switch' ],
				'category_card_shape'   => [ 'label' => __( 'Category card shape', 'webgram' ), 'type' => 'radio', 'choices' => [ 'circle' => __( 'Circle', 'webgram' ), 'square' => __( 'Square', 'webgram' ), 'rounded' => __( 'Rounded', 'webgram' ) ] ],
			],
		],
	],
];
