<?php
/**
 * Header and footer layout presets. The default header reproduces the reference (marquee top bar, logo,
 * deliver-to, search with mic slot, icon group, red secondary bar).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

function webgram_header_row_defaults( string $row ): array {
	return match ( $row ) {
		'top'    => [ 'enabled' => true, 'height' => 36, 'bg' => 'var(--wg-color-topbar-bg)', 'color' => 'var(--wg-color-topbar-text)', 'border' => false, 'container' => 'boxed', 'shadow' => false ],
		'bottom' => [ 'enabled' => true, 'height' => 40, 'bg' => 'var(--wg-color-primary)', 'color' => '#ffffff', 'border' => false, 'container' => 'boxed', 'shadow' => false ],
		default  => [ 'enabled' => true, 'height' => 72, 'bg' => 'var(--wg-color-header-bg)', 'color' => 'var(--wg-color-header-text)', 'border' => true, 'container' => 'boxed', 'shadow' => false ],
	};
}

/** @return array<string, array{label: string, layout: array}> */
function webgram_header_presets(): array {
	$rows = static fn( array $top, array $main, array $bottom, array $overrides = [] ) => [
		'top'    => [ 'settings' => array_merge( webgram_header_row_defaults( 'top' ), $overrides['top'] ?? [] ) ] + $top,
		'main'   => [ 'settings' => array_merge( webgram_header_row_defaults( 'main' ), $overrides['main'] ?? [] ) ] + $main,
		'bottom' => [ 'settings' => array_merge( webgram_header_row_defaults( 'bottom' ), $overrides['bottom'] ?? [] ) ] + $bottom,
	];

	$mobile_default = $rows(
		[ 'left' => [], 'center' => [ 'announcement' ], 'right' => [] ],
		[ 'left' => [ 'menu_toggle' ], 'center' => [ 'logo' ], 'right' => [ 'search_toggle', 'cart' ] ],
		[ 'left' => [], 'center' => [ 'search' ], 'right' => [] ],
		[ 'main' => [ 'height' => 60 ], 'bottom' => [ 'bg' => 'var(--wg-color-header-bg)', 'color' => 'var(--wg-color-header-text)', 'height' => 56 ] ]
	);

	$presets = [
		'store' => [
			'label'  => __( 'Store (marquee, deliver to, search, icon group, red bar)', 'webgram' ),
			'layout' => [
				'desktop' => $rows(
					[ 'left' => [], 'center' => [ 'announcement' ], 'right' => [] ],
					[ 'left' => [ 'logo', 'deliver_to' ], 'center' => [ 'search' ], 'right' => [ 'track_order', 'bulk_order', 'wishlist', 'compare', 'help', 'cart', 'account' ] ],
					[ 'left' => [], 'center' => [ 'menu_secondary' ], 'right' => [] ]
				),
				'mobile'  => $mobile_default,
			],
		],
		'classic' => [
			'label'  => __( 'Classic (logo, menu, icons)', 'webgram' ),
			'layout' => [
				'desktop' => $rows(
					[ 'left' => [ 'text' ], 'center' => [], 'right' => [ 'social' ] ],
					[ 'left' => [ 'logo' ], 'center' => [ 'menu_primary' ], 'right' => [ 'search_toggle', 'wishlist', 'cart', 'account' ] ],
					[ 'left' => [], 'center' => [], 'right' => [] ],
					[ 'bottom' => [ 'enabled' => false ] ]
				),
				'mobile'  => $mobile_default,
			],
		],
		'minimal' => [
			'label'  => __( 'Minimal (single row)', 'webgram' ),
			'layout' => [
				'desktop' => $rows(
					[ 'left' => [], 'center' => [], 'right' => [] ],
					[ 'left' => [ 'logo' ], 'center' => [ 'search' ], 'right' => [ 'account', 'cart' ] ],
					[ 'left' => [], 'center' => [], 'right' => [] ],
					[ 'top' => [ 'enabled' => false ], 'bottom' => [ 'enabled' => false ] ]
				),
				'mobile'  => $mobile_default,
			],
		],
		'two_row' => [
			'label'  => __( 'Two rows (logo and search, menu bar)', 'webgram' ),
			'layout' => [
				'desktop' => $rows(
					[ 'left' => [], 'center' => [], 'right' => [] ],
					[ 'left' => [ 'logo' ], 'center' => [ 'search' ], 'right' => [ 'phone', 'wishlist', 'cart', 'account' ] ],
					[ 'left' => [ 'menu_vertical' ], 'center' => [ 'menu_primary' ], 'right' => [ 'button' ] ],
					[ 'top' => [ 'enabled' => false ], 'bottom' => [ 'bg' => 'var(--wg-color-header-bg)', 'color' => 'var(--wg-color-header-text)', 'border' => true, 'height' => 48 ] ]
				),
				'mobile'  => $mobile_default,
			],
		],
		'centered' => [
			'label'  => __( 'Centered logo', 'webgram' ),
			'layout' => [
				'desktop' => $rows(
					[ 'left' => [ 'announcement' ], 'center' => [], 'right' => [] ],
					[ 'left' => [ 'search_toggle' ], 'center' => [ 'logo' ], 'right' => [ 'wishlist', 'cart', 'account' ] ],
					[ 'left' => [], 'center' => [ 'menu_primary' ], 'right' => [] ],
					[ 'bottom' => [ 'bg' => 'var(--wg-color-header-bg)', 'color' => 'var(--wg-color-header-text)', 'border' => true, 'height' => 48 ] ]
				),
				'mobile'  => $mobile_default,
			],
		],
	];

	foreach ( $presets as &$preset ) {
		$preset['layout']['sticky']   = [ 'enabled' => true, 'rows' => [ 'main' ], 'shrink' => true, 'hide_on_scroll' => false ];
		$preset['layout']['elements'] = [];
	}
	unset( $preset );

	return (array) apply_filters( 'webgram/header/presets', $presets );
}

/** @return array<string, array{label: string, layout: array}> */
function webgram_footer_presets(): array {
	$presets = [
		'store' => [
			'label'  => __( 'Store (brand, 3 menus, connect, copyright)', 'webgram' ),
			'layout' => [
				'widgets' => [
					'enabled'  => true,
					'columns'  => 5,
					'areas'    => [
						'col_1' => [ 'logo', 'description' ],
						'col_2' => [ 'menu_1' ],
						'col_3' => [ 'menu_2' ],
						'col_4' => [ 'menu_3' ],
						'col_5' => [ 'social' ],
						'col_6' => [],
					],
					'settings' => [ 'first_wide' => true, 'separators' => true, 'padding' => 64 ],
				],
				'bottom'  => [
					'enabled'  => true,
					'left'     => [],
					'center'   => [ 'copyright' ],
					'right'    => [],
					'settings' => [ 'border' => true ],
				],
				'elements' => [],
			],
		],
		'widgets' => [
			'label'  => __( 'Widget areas (4 columns)', 'webgram' ),
			'layout' => [
				'widgets' => [
					'enabled'  => true,
					'columns'  => 4,
					'areas'    => [
						'col_1' => [ 'widget_area_1' ],
						'col_2' => [ 'widget_area_2' ],
						'col_3' => [ 'widget_area_3' ],
						'col_4' => [ 'widget_area_4' ],
						'col_5' => [],
						'col_6' => [],
					],
					'settings' => [ 'first_wide' => false, 'padding' => 64 ],
				],
				'bottom'  => [
					'enabled'  => true,
					'left'     => [ 'copyright' ],
					'center'   => [],
					'right'    => [ 'payment_icons' ],
					'settings' => [ 'border' => true ],
				],
				'elements' => [],
			],
		],
	];
	return (array) apply_filters( 'webgram/footer/presets', $presets );
}
