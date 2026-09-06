<?php
/**
 * Tab: Page title.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_on = [ 'page_title_show', '==', true ];

return [
	'id'       => 'page_title',
	'label'    => __( 'Page title', 'webgram' ),
	'icon'     => 'heading',
	'priority' => 70,
	'sections' => [
		'title'      => [
			'label'  => __( 'Title band', 'webgram' ),
			'fields' => [
				'page_title_show'        => [ 'label' => __( 'Show page title band', 'webgram' ), 'type' => 'switch' ],
				'page_title_types'       => [ 'label' => __( 'Show on', 'webgram' ), 'type' => 'multicheck', 'choices' => [ 'page' => __( 'Pages', 'webgram' ), 'post' => __( 'Single posts', 'webgram' ), 'blog' => __( 'Blog and archives', 'webgram' ), 'shop' => __( 'Shop and product categories', 'webgram' ), 'search' => __( 'Search results', 'webgram' ) ], 'show_if' => $webgram_on, 'description' => __( 'Single products keep the WooCommerce breadcrumb above the gallery. Any page or post can override this in its Webgram options box.', 'webgram' ) ],
				'page_title_style'       => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'band' => __( 'Band (background color or image)', 'webgram' ), 'minimal' => __( 'Minimal (title and breadcrumb only)', 'webgram' ), 'overlay' => __( 'Hero (image with centered text)', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_size'        => [ 'label' => __( 'Height', 'webgram' ), 'type' => 'select', 'choices' => [ 'small' => __( 'Small', 'webgram' ), 'medium' => __( 'Medium', 'webgram' ), 'large' => __( 'Large', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_size_mobile' => [ 'label' => __( 'Height on mobile', 'webgram' ), 'type' => 'select', 'choices' => [ 'inherit' => __( 'Same as desktop', 'webgram' ), 'small' => __( 'Small', 'webgram' ), 'medium' => __( 'Medium', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_align'       => [ 'label' => __( 'Alignment', 'webgram' ), 'type' => 'radio', 'choices' => [ 'start' => __( 'Start', 'webgram' ), 'center' => __( 'Center', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_heading_size' => [ 'label' => __( 'Title size', 'webgram' ), 'type' => 'select', 'choices' => [ 'h1' => __( 'Heading 1', 'webgram' ), 'h2' => __( 'Heading 2', 'webgram' ), 'h3' => __( 'Heading 3', 'webgram' ) ], 'show_if' => $webgram_on ],
			],
		],
		'background' => [
			'label'  => __( 'Background', 'webgram' ),
			'fields' => [
				'page_title_bg'              => [ 'label' => __( 'Background color', 'webgram' ), 'type' => 'color', 'show_if' => $webgram_on ],
				'page_title_bg_image'        => [ 'label' => __( 'Background image', 'webgram' ), 'type' => 'image', 'show_if' => $webgram_on, 'description' => __( 'Product categories use their own image when one is set.', 'webgram' ) ],
				'page_title_bg_size'         => [ 'label' => __( 'Image size', 'webgram' ), 'type' => 'select', 'choices' => [ 'cover' => __( 'Cover', 'webgram' ), 'contain' => __( 'Contain', 'webgram' ), 'auto' => __( 'Original size', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_bg_position'     => [ 'label' => __( 'Image position', 'webgram' ), 'type' => 'select', 'choices' => [ 'center' => __( 'Center', 'webgram' ), 'top' => __( 'Top', 'webgram' ), 'bottom' => __( 'Bottom', 'webgram' ), 'left' => __( 'Left', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ], 'show_if' => $webgram_on ],
				'page_title_bg_parallax'     => [ 'label' => __( 'Fixed image (parallax feel)', 'webgram' ), 'type' => 'switch', 'show_if' => $webgram_on ],
				'page_title_overlay'         => [ 'label' => __( 'Image overlay color', 'webgram' ), 'type' => 'color', 'show_if' => $webgram_on ],
				'page_title_overlay_opacity' => [ 'label' => __( 'Overlay opacity', 'webgram' ), 'type' => 'range', 'min' => 0, 'max' => 100, 'unit' => '%', 'show_if' => $webgram_on ],
				'page_title_color'           => [ 'label' => __( 'Text color', 'webgram' ), 'type' => 'color', 'show_if' => $webgram_on ],
			],
		],
		'breadcrumb' => [
			'label'  => __( 'Breadcrumb', 'webgram' ),
			'fields' => [
				'breadcrumb_show'     => [ 'label' => __( 'Breadcrumb', 'webgram' ), 'type' => 'switch', 'description' => __( 'Uses Yoast or Rank Math breadcrumbs when active, otherwise the theme breadcrumb.', 'webgram' ) ],
				'breadcrumb_position' => [ 'label' => __( 'Position', 'webgram' ), 'type' => 'radio', 'choices' => [ 'above' => __( 'Above the title', 'webgram' ), 'below' => __( 'Below the title', 'webgram' ) ], 'show_if' => [ 'breadcrumb_show', '==', true ] ],
				'breadcrumb_mobile'   => [ 'label' => __( 'Show breadcrumb on mobile', 'webgram' ), 'type' => 'switch', 'show_if' => [ 'breadcrumb_show', '==', true ] ],
			],
		],
	],
];
