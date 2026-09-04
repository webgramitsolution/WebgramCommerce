<?php
/**
 * Tab: Shop (archive grid and product card).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_attributes = [ 'first' => __( 'First attribute of each product', 'webgram' ) ];
if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
	foreach ( wc_get_attribute_taxonomies() as $webgram_attr ) {
		$webgram_attributes[ 'pa_' . $webgram_attr->attribute_name ] = $webgram_attr->attribute_label;
	}
}

return [
	'id'       => 'shop',
	'label'    => __( 'Shop', 'webgram' ),
	'icon'     => 'shopping-bag',
	'priority' => 120,
	'sections' => [
		'grid' => [
			'label'  => __( 'Grid', 'webgram' ),
			'fields' => [
				'shop_columns'          => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'dimensions', 'min' => 1, 'max' => 6 ],
				'shop_per_page'         => [ 'label' => __( 'Products per page', 'webgram' ), 'type' => 'number', 'min' => 4, 'max' => 100 ],
				'shop_toolbar'          => [ 'label' => __( 'Toolbar (count, sort, view)', 'webgram' ), 'type' => 'switch' ],
				'shop_grid_list_toggle' => [ 'label' => __( 'Grid / list toggle', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'shop_toolbar', '==', true ] ],
				'shop_filters'          => [ 'label' => __( 'Filters', 'webgram' ), 'type' => 'radio', 'choices' => [ 'sidebar' => __( 'Sidebar', 'webgram' ), 'offcanvas' => __( 'Off-canvas drawer', 'webgram' ) ] ],
				'shop_pagination'       => [ 'label' => __( 'Pagination', 'webgram' ), 'type' => 'radio', 'choices' => [ 'numbers' => __( 'Numbers', 'webgram' ), 'load_more' => __( 'Load more button', 'webgram' ), 'infinite' => __( 'Infinite scroll', 'webgram' ) ] ],
				'shop_ajax'             => [ 'label' => __( 'AJAX filtering and sorting', 'webgram' ), 'type' => 'switch' ],
			],
		],
		'card' => [
			'label'  => __( 'Product card', 'webgram' ),
			'fields' => [
				'product_card_style'      => [ 'label' => __( 'Default card variant', 'webgram' ), 'type' => 'radio', 'choices' => [ 'standard' => __( 'Standard', 'webgram' ), 'list' => __( 'List', 'webgram' ) ] ],
				'card_image_ratio'        => [ 'label' => __( 'Image ratio', 'webgram' ), 'type' => 'radio', 'choices' => [ '1-1' => __( 'Square 1:1', 'webgram' ), '3-4' => __( 'Portrait 3:4', 'webgram' ) ] ],
				'card_hover_effect'       => [ 'label' => __( 'Image hover effect', 'webgram' ), 'type' => 'radio', 'choices' => [ 'none' => __( 'None', 'webgram' ), 'swap' => __( 'Swap to second image', 'webgram' ), 'slideshow' => __( 'Slideshow through gallery', 'webgram' ) ] ],
				'card_slideshow_interval' => [ 'label' => __( 'Slideshow interval', 'webgram' ), 'type' => 'number', 'min' => 500, 'max' => 5000, 'step' => 100, 'unit' => 'ms', 'show_if' => [ 'card_hover_effect', '==', 'slideshow' ] ],
				'card_title_lines'        => [ 'label' => __( 'Title lines', 'webgram' ), 'type' => 'range', 'min' => 1, 'max' => 3 ],
				'card_badge_position'     => [ 'label' => __( 'Badge position', 'webgram' ), 'type' => 'select', 'choices' => [ 'top-left' => __( 'Top left', 'webgram' ), 'top-right' => __( 'Top right', 'webgram' ) ] ],
				'card_actions_position'   => [ 'label' => __( 'Quick actions (wishlist, compare, quick view)', 'webgram' ), 'type' => 'radio', 'choices' => [ 'hover' => __( 'Show on hover', 'webgram' ), 'always' => __( 'Always visible', 'webgram' ), 'hidden' => __( 'Hidden', 'webgram' ) ] ],
				'card_rating_position'    => [ 'label' => __( 'Rating pill', 'webgram' ), 'type' => 'radio', 'choices' => [ 'price_line' => __( 'Right end of price line', 'webgram' ), 'under_title' => __( 'Under title', 'webgram' ), 'hidden' => __( 'Hidden', 'webgram' ) ] ],
				'card_show_cart'          => [ 'label' => __( 'Cart icon button', 'webgram' ), 'type' => 'switch' ],
				'card_show_buy_now'       => [ 'label' => __( 'Buy now button', 'webgram' ), 'type' => 'switch', 'description' => __( 'Goes to checkout with the selected variation. Requires Webgram Core for the direct-to-checkout behavior; without it the button opens the product page.', 'webgram' ) ],
				'card_buy_now_label'      => [ 'label' => __( 'Buy now label', 'webgram' ), 'type' => 'text', 'show_if' => [ 'card_show_buy_now', '==', true ] ],
				'card_show_chips'         => [ 'label' => __( 'Variation chips under price', 'webgram' ), 'type' => 'switch' ],
				'card_chip_attribute'     => [ 'label' => __( 'Chip attribute', 'webgram' ), 'type' => 'select', 'choices' => $webgram_attributes, 'description' => __( 'Can be overridden per product.', 'webgram' ), 'show_if' => [ 'card_show_chips', '==', true ] ],
				'card_chip_style'         => [ 'label' => __( 'Chip style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'chips' => __( 'Text chips', 'webgram' ), 'colors' => __( 'Color circles', 'webgram' ), 'images' => __( 'Image thumbs', 'webgram' ) ], 'show_if' => [ 'card_show_chips', '==', true ] ],
				'card_chips_max'          => [ 'label' => __( 'Chips shown before "+N"', 'webgram' ), 'type' => 'range', 'min' => 2, 'max' => 5, 'show_if' => [ 'card_show_chips', '==', true ] ],
				'quick_view'              => [ 'label' => __( 'Quick view', 'webgram' ), 'type' => 'info', 'content' => webgram_has_core( 'quick_view' ) ? esc_html__( 'Provided by Webgram Core (Quick View module).', 'webgram' ) : esc_html__( 'Enable the Quick View module in Webgram Core.', 'webgram' ) ],
			],
		],
	],
];
