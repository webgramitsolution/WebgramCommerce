<?php
/**
 * Single source of default values for every Customizer setting. Panels read from here; never inline a default twice.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

function webgram_defaults(): array {
	static $defaults = null;

	if ( null === $defaults ) {
		$defaults = [
			// Colors.
			'color_primary'        => '#a0181f',
			'color_primary_hover'  => '#83131a',
			'color_secondary'      => '#1f2937',
			'color_accent'         => '#c9a24d',
			'color_text'           => '#374151',
			'color_text_muted'     => '#6b7280',
			'color_heading'        => '#111827',
			'color_bg'             => '#ffffff',
			'color_bg_alt'         => '#f8f7f5',
			'color_surface'        => '#ffffff',
			'color_border'         => '#e5e7eb',
			'color_success'        => '#15803d',
			'color_warning'        => '#b45309',
			'color_danger'         => '#b91c1c',
			'color_price'          => '#111827',
			'color_sale'           => '#15803d',
			'color_star'           => '#f59e0b',
			'color_topbar_bg'      => '#fff1f2',
			'color_topbar_text'    => '#7f1d1d',
			'color_header_bg'      => '#ffffff',
			'color_header_text'    => '#111827',
			'color_footer_bg'      => '#3f0d12',
			'color_footer_text'    => '#f5e9ea',

			// Typography.
			'font_source'          => 'local',
			'font_body'            => 'Inter',
			'font_heading'         => 'Manrope',
			'font_size_base'       => 16,
			'heading_weight'       => 700,
			'heading_letter_spacing' => -0.01,

			// Layout and shape.
			'container_width'      => 1320,
			'radius_scale'         => 'rounded',
			'button_radius'        => 8,
			'button_style'         => 'solid',
			'shop_layout'          => 'sidebar-left',
			'blog_layout'          => 'sidebar-right',

			// Header (builder arrives in Phase 1; these toggles already drive the preset header).
			'topbar_enabled'       => true,
			'topbar_text'          => __( 'Free shipping on orders above ₹499', 'webgram' ),
			'header_sticky'        => true,
			'header_search'        => true,
			'header_deliver_to'    => true,
			'secondary_bar_enabled' => true,

			// Footer.
			'footer_columns'       => 4,
			'footer_copyright'     => sprintf( '© %s %s', gmdate( 'Y' ), get_bloginfo( 'name' ) ),

			// Shop.
			'shop_columns'         => 5,
			'product_card_style'   => 'standard',
			'category_card_shape'  => 'circle',
		];
	}

	return (array) apply_filters( 'webgram/defaults', $defaults );
}
