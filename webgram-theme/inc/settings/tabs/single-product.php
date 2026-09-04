<?php
/**
 * Tab: Single product.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

return [
	'id'       => 'single_product',
	'label'    => __( 'Single product', 'webgram' ),
	'icon'     => 'box',
	'priority' => 140,
	'sections' => [
		'layout' => [
			'label'  => __( 'Layout', 'webgram' ),
			'fields' => [
				'product_layout'        => [ 'label' => __( 'Template', 'webgram' ), 'type' => 'radio', 'choices' => [ 'default' => __( 'Theme layout', 'webgram' ), 'layout' => __( 'Webgram Core Layout (if one matches)', 'webgram' ) ] ],
				'product_panels'        => [ 'label' => __( 'Panels', 'webgram' ), 'type' => 'radio', 'choices' => [ 'panels' => __( 'White panels on cream background', 'webgram' ), 'flat' => __( 'Flat', 'webgram' ) ] ],
				'product_sticky_gallery' => [ 'label' => __( 'Sticky gallery', 'webgram' ), 'type' => 'switch', 'description' => __( 'Releases when the summary column ends.', 'webgram' ) ],
				'product_sticky_bar'    => [ 'label' => __( 'Mobile sticky bar (price, Add to cart, Buy now)', 'webgram' ), 'type' => 'switch' ],
				'product_tabs_style'    => [ 'label' => __( 'Specifications and overview', 'webgram' ), 'type' => 'radio', 'choices' => [ 'stacked' => __( 'Stacked sections (accordions on mobile)', 'webgram' ), 'tabs' => __( 'WooCommerce tabs', 'webgram' ) ] ],
			],
		],
		'gallery' => [
			'label'  => __( 'Gallery', 'webgram' ),
			'fields' => [
				'product_gallery_style'      => [ 'label' => __( 'Thumbnails', 'webgram' ), 'type' => 'radio', 'choices' => [ 'horizontal' => __( 'Strip below image', 'webgram' ), 'vertical' => __( 'Vertical strip on the left', 'webgram' ) ] ],
				'product_thumbs_visible'     => [ 'label' => __( 'Thumbnails visible', 'webgram' ), 'type' => 'range', 'min' => 4, 'max' => 10 ],
				'product_zoom'               => [ 'label' => __( 'Zoom on hover', 'webgram' ), 'type' => 'switch' ],
				'product_auto_slide'         => [ 'label' => __( 'Auto slide', 'webgram' ), 'type' => 'switch', 'description' => __( 'Main image advances automatically until the visitor interacts.', 'webgram' ) ],
				'product_auto_slide_interval' => [ 'label' => __( 'Interval', 'webgram' ), 'type' => 'number', 'min' => 1000, 'max' => 10000, 'step' => 250, 'unit' => 'ms', 'show_if' => [ 'product_auto_slide', '==', true ] ],
				'product_auto_slide_pause'   => [ 'label' => __( 'Pause on hover', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'product_auto_slide', '==', true ] ],
			],
		],
		'order' => [
			'label'       => __( 'Section order', 'webgram' ),
			'description' => __( 'Drag to reorder, untick to hide. Sections provided by disabled Core modules are skipped automatically.', 'webgram' ),
			'fields'      => [
				'product_summary_order' => [
					'label' => __( 'Summary column', 'webgram' ),
					'type'  => 'sortable',
					'full'  => true,
					'items' => [
						'title'             => __( 'Title', 'webgram' ),
						'meta'              => __( 'Meta (SKU, category)', 'webgram' ),
						'price'             => __( 'Price line with rating pill', 'webgram' ),
						'short_description' => __( 'Short description', 'webgram' ),
						'variations'        => __( 'Variant swatches', 'webgram' ),
						'coupon'            => __( 'Coupon box (Core)', 'webgram' ),
						'quantity_cart'     => __( 'Quantity, Add to cart, Buy now', 'webgram' ),
						'payment_strip'     => __( 'Payment strip', 'webgram' ),
						'trust_seals'       => __( 'Trust seals', 'webgram' ),
						'contact_seller'    => __( 'Contact cards (Core)', 'webgram' ),
						'pincode'           => __( 'Delivery check (Core)', 'webgram' ),
						'shipping_returns'  => __( 'Info cards (returns, shipping)', 'webgram' ),
						'specifications'    => __( 'Specifications', 'webgram' ),
						'overview'          => __( 'Overview', 'webgram' ),
						'share'             => __( 'Share', 'webgram' ),
					],
				],
				'product_below_order'   => [
					'label' => __( 'Below the columns', 'webgram' ),
					'type'  => 'sortable',
					'full'  => true,
					'items' => [
						'related'         => __( 'Related products', 'webgram' ),
						'reviews'         => __( 'Reviews', 'webgram' ),
						'reels'           => __( 'Trending reels (Core)', 'webgram' ),
						'recently_viewed' => __( 'Recently viewed (Core)', 'webgram' ),
					],
				],
				'product_related_count' => [ 'label' => __( 'Related products', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 10 ],
				'product_recent_count'  => [ 'label' => __( 'Recently viewed', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 10 ],
				'product_meta_show'     => [ 'label' => __( 'Show meta row', 'webgram' ), 'type' => 'switch' ],
				'product_share_show'    => [ 'label' => __( 'Show share row', 'webgram' ), 'type' => 'switch' ],
			],
		],
		'trust' => [
			'label'  => __( 'Payment strip, seals and info cards', 'webgram' ),
			'fields' => [
				'product_payment_strip'   => [ 'label' => __( 'Payment strip', 'webgram' ), 'type' => 'switch' ],
				'product_payment_title'   => [ 'label' => __( 'Strip title', 'webgram' ), 'type' => 'text', 'show_if' => [ 'product_payment_strip', '==', true ] ],
				'product_payment_logo'    => [ 'label' => __( 'Processor logo', 'webgram' ), 'type' => 'image', 'show_if' => [ 'product_payment_strip', '==', true ] ],
				'product_payment_icons'   => [ 'label' => __( 'Payment methods', 'webgram' ), 'type' => 'multicheck', 'choices' => webgram_payment_icon_choices(), 'show_if' => [ 'product_payment_strip', '==', true ] ],
				'product_payment_caption' => [ 'label' => __( 'Caption', 'webgram' ), 'type' => 'text', 'show_if' => [ 'product_payment_strip', '==', true ] ],
				'product_trust_seals'     => [
					'label'       => __( 'Trust seals', 'webgram' ),
					'type'        => 'repeater',
					'full'        => true,
					'max'         => 6,
					'title_field' => 'label',
					'fields'      => [
						'icon'  => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon' ],
						'label' => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'text' ],
					],
				],
				'product_info_cards'      => [
					'label'       => __( 'Info cards', 'webgram' ),
					'type'        => 'repeater',
					'full'        => true,
					'max'         => 4,
					'title_field' => 'title',
					'fields'      => [
						'icon'  => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon' ],
						'title' => [ 'label' => __( 'Title', 'webgram' ), 'type' => 'text' ],
						'text'  => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'text' ],
					],
				],
			],
		],
	],
];
