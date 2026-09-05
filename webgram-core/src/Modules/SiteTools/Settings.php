<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Site Tools settings tabs in the shared field schema (id, label, type, choices, default, show_if). Registered into
 * the theme panel via webgram/settings/tabs with Core-owned values/save callbacks.
 */
final class Settings {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'webgram/settings/tabs', [ $this, 'tabs' ] );
	}

	/** @return array<string, array> */
	public static function definitions( Module $module ): array {
		$roles = [];
		if ( function_exists( 'wp_roles' ) ) {
			foreach ( wp_roles()->get_names() as $slug => $label ) {
				$roles[ $slug ] = translate_user_role( $label );
			}
		}
		$devices = [ 'desktop' => __( 'Desktop', 'webgram-core' ), 'tablet' => __( 'Tablet', 'webgram-core' ), 'mobile' => __( 'Mobile', 'webgram-core' ) ];
		$pages   = [ 'home' => __( 'Homepage', 'webgram-core' ), 'shop' => __( 'Shop and categories', 'webgram-core' ), 'product' => __( 'Product pages', 'webgram-core' ), 'cart' => __( 'Cart', 'webgram-core' ), 'checkout' => __( 'Checkout', 'webgram-core' ), 'account' => __( 'My account', 'webgram-core' ), 'blog' => __( 'Blog', 'webgram-core' ), 'page' => __( 'Pages', 'webgram-core' ), 'other' => __( 'Everything else', 'webgram-core' ) ];
		$blocks  = admin_url( 'edit.php?post_type=wg_block' );
		$layouts = admin_url( 'edit.php?post_type=wg_layout' );

		return [
			'promo_popup' => [
				'id'       => 'promo_popup',
				'label'    => __( 'Promo popup', 'webgram-core' ),
				'icon'     => 'megaphone',
				'priority' => 32,
				'sections' => [
					'popup' => [
						'fields' => [
							'popup_enabled'   => [ 'label' => __( 'Enable promo popup', 'webgram-core' ), 'type' => 'switch', 'default' => false ],
							'popup_block'     => [ 'label' => __( 'HTML Block', 'webgram-core' ), 'type' => 'html_block', 'default' => 0, 'description' => sprintf( '<a href="%s">%s</a>', esc_url( $blocks ), esc_html__( 'Manage HTML Blocks', 'webgram-core' ) ), 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_content'   => [ 'label' => __( 'Or simple content', 'webgram-core' ), 'type' => 'html', 'rows' => 4, 'default' => '', 'description' => __( 'Used when no HTML Block is selected. Basic HTML allowed.', 'webgram-core' ), 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_image'     => [ 'label' => __( 'Side image', 'webgram-core' ), 'type' => 'image', 'default' => 0, 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_width'     => [ 'label' => __( 'Width', 'webgram-core' ), 'type' => 'number', 'min' => 320, 'max' => 1000, 'unit' => 'px', 'default' => 640, 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_trigger'   => [ 'label' => __( 'Trigger', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'delay' => __( 'After a delay', 'webgram-core' ), 'scroll' => __( 'After scrolling', 'webgram-core' ), 'exit' => __( 'Exit intent (desktop)', 'webgram-core' ) ], 'default' => 'delay', 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_delay'     => [ 'label' => __( 'Delay', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 120, 'unit' => 's', 'default' => 5, 'show_if' => [ 'popup_trigger', '==', 'delay' ] ],
							'popup_scroll'    => [ 'label' => __( 'Scroll depth', 'webgram-core' ), 'type' => 'number', 'min' => 5, 'max' => 100, 'unit' => '%', 'default' => 40, 'show_if' => [ 'popup_trigger', '==', 'scroll' ] ],
							'popup_frequency' => [ 'label' => __( 'Show again', 'webgram-core' ), 'type' => 'select', 'choices' => [ 'session' => __( 'Once per session', 'webgram-core' ), 'day' => __( 'Once per day', 'webgram-core' ), 'week' => __( 'Once per week', 'webgram-core' ), 'always' => __( 'Every page load', 'webgram-core' ) ], 'default' => 'day', 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_devices'   => [ 'label' => __( 'Devices', 'webgram-core' ), 'type' => 'multicheck', 'choices' => $devices, 'default' => [ 'desktop', 'tablet', 'mobile' ], 'show_if' => [ 'popup_enabled', '==', true ] ],
							'popup_pages'     => [ 'label' => __( 'Pages', 'webgram-core' ), 'type' => 'multicheck', 'choices' => $pages, 'default' => [ 'home', 'shop', 'product', 'blog', 'page', 'other' ], 'show_if' => [ 'popup_enabled', '==', true ] ],
						],
					],
				],
			],
			'age_verify' => [
				'id'       => 'age_verify',
				'label'    => __( 'Age verify popup', 'webgram-core' ),
				'icon'     => 'shield',
				'priority' => 34,
				'sections' => [
					'age' => [
						'fields' => [
							'age_enabled'   => [ 'label' => __( 'Enable age verification', 'webgram-core' ), 'type' => 'switch', 'default' => false ],
							'age_title'     => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Are you over 18?', 'webgram-core' ), 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_text'      => [ 'label' => __( 'Text', 'webgram-core' ), 'type' => 'textarea', 'rows' => 3, 'default' => __( 'You must be of legal age to enter this website.', 'webgram-core' ), 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_min'       => [ 'label' => __( 'Minimum age', 'webgram-core' ), 'type' => 'number', 'min' => 13, 'max' => 30, 'default' => 18, 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_mode'      => [ 'label' => __( 'Input', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'yesno' => __( 'Yes / No buttons', 'webgram-core' ), 'date' => __( 'Date of birth', 'webgram-core' ) ], 'default' => 'yesno', 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_yes'       => [ 'label' => __( 'Confirm label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Yes, I am', 'webgram-core' ), 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_no'        => [ 'label' => __( 'Decline label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'No, leave', 'webgram-core' ), 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_redirect'  => [ 'label' => __( 'Redirect on decline', 'webgram-core' ), 'type' => 'url', 'default' => 'https://www.google.com', 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_remember'  => [ 'label' => __( 'Remember for', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 365, 'unit' => __( 'days', 'webgram-core' ), 'default' => 30, 'show_if' => [ 'age_enabled', '==', true ] ],
							'age_bg'        => [ 'label' => __( 'Background image', 'webgram-core' ), 'type' => 'image', 'default' => 0, 'show_if' => [ 'age_enabled', '==', true ] ],
						],
					],
				],
			],
			'cookie_law' => [
				'id'       => 'cookie_law',
				'label'    => __( 'Cookie law info', 'webgram-core' ),
				'icon'     => 'info',
				'priority' => 36,
				'sections' => [
					'cookie' => [
						'fields' => [
							'cookie_enabled'      => [ 'label' => __( 'Show cookie notice', 'webgram-core' ), 'type' => 'switch', 'default' => false ],
							'cookie_text'         => [ 'label' => __( 'Text', 'webgram-core' ), 'type' => 'html', 'rows' => 3, 'default' => __( 'We use cookies to improve your experience and analyze traffic. By continuing you agree to our cookie policy.', 'webgram-core' ), 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_accept'       => [ 'label' => __( 'Accept button', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Accept', 'webgram-core' ), 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_reject_show'  => [ 'label' => __( 'Show reject button (GDPR)', 'webgram-core' ), 'type' => 'switch', 'default' => true, 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_reject'       => [ 'label' => __( 'Reject button', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Reject', 'webgram-core' ), 'show_if' => [ 'cookie_reject_show', '==', true ] ],
							'cookie_policy_page'  => [ 'label' => __( 'Policy page', 'webgram-core' ), 'type' => 'page', 'default' => 0, 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_policy_label' => [ 'label' => __( 'Policy link label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Learn more', 'webgram-core' ), 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_position'     => [ 'label' => __( 'Position', 'webgram-core' ), 'type' => 'select', 'choices' => [ 'bottom' => __( 'Bottom bar', 'webgram-core' ), 'bottom-left' => __( 'Bottom left card', 'webgram-core' ), 'bottom-right' => __( 'Bottom right card', 'webgram-core' ), 'top' => __( 'Top bar', 'webgram-core' ) ], 'default' => 'bottom-left', 'show_if' => [ 'cookie_enabled', '==', true ] ],
							'cookie_remember'     => [ 'label' => __( 'Remember choice for', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 365, 'unit' => __( 'days', 'webgram-core' ), 'default' => 180, 'show_if' => [ 'cookie_enabled', '==', true ] ],
						],
					],
				],
			],
			'maintenance' => [
				'id'       => 'maintenance',
				'label'    => __( 'Maintenance', 'webgram-core' ),
				'icon'     => 'lock',
				'priority' => 172,
				'sections' => [
					'mode' => [
						'fields' => [
							'maint_mode'      => [ 'label' => __( 'Mode', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'off' => __( 'Off', 'webgram-core' ), 'coming_soon' => __( 'Coming soon (200)', 'webgram-core' ), 'maintenance' => __( 'Maintenance (503)', 'webgram-core' ) ], 'default' => 'off' ],
							'maint_page'      => [ 'label' => __( 'Show this page', 'webgram-core' ), 'type' => 'page', 'default' => 0, 'description' => __( 'A page built with Elementor or Gutenberg. Leave empty to use the block or the simple content below.', 'webgram-core' ), 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_block'     => [ 'label' => __( 'Or HTML Block', 'webgram-core' ), 'type' => 'html_block', 'default' => 0, 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_title'     => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text', 'default' => __( 'We are launching soon', 'webgram-core' ), 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_text'      => [ 'label' => __( 'Text', 'webgram-core' ), 'type' => 'textarea', 'rows' => 3, 'default' => __( 'Our store is getting a fresh look. Please check back shortly.', 'webgram-core' ), 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_countdown' => [ 'label' => __( 'Countdown to (date and time)', 'webgram-core' ), 'type' => 'text', 'default' => '', 'placeholder' => '2026-12-31 09:00', 'description' => __( 'Site timezone. Leave empty to hide the countdown.', 'webgram-core' ), 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_bg'        => [ 'label' => __( 'Background image', 'webgram-core' ), 'type' => 'image', 'default' => 0, 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
							'maint_roles'     => [ 'label' => __( 'Roles that can still browse', 'webgram-core' ), 'type' => 'multicheck', 'choices' => $roles, 'default' => [ 'administrator', 'shop_manager' ], 'description' => __( 'Administrators always bypass.', 'webgram-core' ), 'show_if' => [ 'maint_mode', '!=', 'off' ] ],
						],
					],
				],
			],
			'white_label' => [
				'id'       => 'white_label',
				'label'    => __( 'White label', 'webgram-core' ),
				'icon'     => 'award',
				'priority' => 174,
				'sections' => [
					'brand' => [
						'description' => __( 'Renames the Webgram panel for your clients. Attribution in code and licensing is not affected.', 'webgram-core' ),
						'fields'      => [
							'wl_name'          => [ 'label' => __( 'Panel name', 'webgram-core' ), 'type' => 'text', 'default' => '', 'placeholder' => 'Webgram' ],
							'wl_logo'          => [ 'label' => __( 'Panel logo', 'webgram-core' ), 'type' => 'image', 'default' => 0 ],
							'wl_hide_sections' => [ 'label' => __( 'Hide from clients', 'webgram-core' ), 'type' => 'multicheck', 'choices' => [ 'status' => __( 'System status page', 'webgram-core' ), 'import_export' => __( 'Import / Export page', 'webgram-core' ), 'modules' => __( 'Modules page', 'webgram-core' ) ], 'default' => [] ],
						],
					],
				],
			],
			'api_integrations' => [
				'id'       => 'api_integrations',
				'label'    => __( 'API integrations', 'webgram-core' ),
				'icon'     => 'globe',
				'priority' => 165,
				'sections' => [
					'geo' => [
						'label'  => __( 'Location and maps', 'webgram-core' ),
						'fields' => [
							'geo_adapter'     => [ 'label' => __( 'Reverse geocoding ("Use my current location")', 'webgram-core' ), 'type' => 'select', 'choices' => (array) apply_filters( 'webgram_core/geo/adapter_choices', [ 'none' => __( 'None (button hidden)', 'webgram-core' ), 'nominatim' => __( 'OpenStreetMap Nominatim (free, attribution required)', 'webgram-core' ) ] ), 'default' => 'none' ],
							'nominatim_email' => [ 'label' => __( 'Contact email for Nominatim', 'webgram-core' ), 'type' => 'email', 'default' => '', 'description' => __( 'Nominatim usage policy asks for a contact address and allows at most one request per second. Not for heavy traffic.', 'webgram-core' ), 'show_if' => [ 'geo_adapter', '==', 'nominatim' ] ],
							'maps_key'        => [ 'label' => __( 'Google Maps API key (optional, future use)', 'webgram-core' ), 'type' => 'secret', 'default' => '' ],
						],
					],
					'links' => [
						'label'  => __( 'Module credentials', 'webgram-core' ),
						'fields' => [
							'core_settings_link' => [ 'label' => __( 'Instagram, WhatsApp, AI providers', 'webgram-core' ), 'type' => 'link', 'url' => admin_url( 'admin.php?page=webgram-core-settings' ), 'button' => __( 'Open Core Settings', 'webgram-core' ), 'description' => __( 'Each module keeps its own credentials on the Core Settings screen. Secrets are stored encrypted.', 'webgram-core' ) ],
						],
					],
				],
			],
			'custom_js' => [
				'id'       => 'custom_js',
				'label'    => __( 'Custom JS', 'webgram-core' ),
				'icon'     => 'code',
				'priority' => 182,
				'sections' => [
					'js' => [
						'description' => __( 'Only users with the unfiltered_html capability can save scripts. Printed with wp_add_inline_script.', 'webgram-core' ),
						'fields'      => [
							'js_header' => [ 'label' => __( 'Header scripts', 'webgram-core' ), 'type' => 'code', 'language' => 'javascript', 'full' => true, 'default' => '', 'rows' => 8 ],
							'js_footer' => [ 'label' => __( 'Footer scripts', 'webgram-core' ), 'type' => 'code', 'language' => 'javascript', 'full' => true, 'default' => '', 'rows' => 8 ],
						],
					],
				],
			],
			'layouts' => [
				'id'       => 'layouts',
				'label'    => __( 'Layouts', 'webgram-core' ),
				'icon'     => 'layout',
				'priority' => 200,
				'sections' => [
					'layouts' => [
						'description' => __( 'A Layout is an Elementor or Gutenberg template that replaces a theme template (shop, product, cart, checkout, thank you, account, 404, blog, header, footer) when its conditions match.', 'webgram-core' ),
						'fields'      => [
							'layouts_link' => [ 'label' => __( 'Manage layouts', 'webgram-core' ), 'type' => 'link', 'url' => $layouts, 'button' => __( 'Open Layouts', 'webgram-core' ) ],
						],
					],
				],
			],
			'html_blocks' => [
				'id'       => 'html_blocks',
				'label'    => __( 'HTML Blocks', 'webgram-core' ),
				'icon'     => 'code',
				'priority' => 205,
				'sections' => [
					'blocks' => [
						'description' => __( 'Reusable content blocks for the header builder, footer, mega menu, product page positions, popups and empty states. Shortcode: [webgram_block id=""].', 'webgram-core' ),
						'fields'      => [
							'blocks_link' => [ 'label' => __( 'Manage blocks', 'webgram-core' ), 'type' => 'link', 'url' => $blocks, 'button' => __( 'Open HTML Blocks', 'webgram-core' ) ],
						],
					],
				],
			],
			'help_page' => [
				'id'       => 'help_page',
				'label'    => __( 'Help page', 'webgram-core' ),
				'icon'     => 'help-circle',
				'priority' => 192,
				'sections' => [
					'faq' => [
						'description' => __( 'Shown by the theme "Help" page template as an accordion, with contact cards from the Contact seller settings.', 'webgram-core' ),
						'fields'      => [
							'help_faqs' => [ 'label' => __( 'FAQ entries', 'webgram-core' ), 'type' => 'textarea', 'rows' => 12, 'full' => true, 'default' => '', 'description' => __( 'One entry per block separated by a blank line: first line is the question, the following lines are the answer.', 'webgram-core' ) ],
						],
					],
				],
			],
			'portfolio' => [
				'id'       => 'portfolio',
				'label'    => __( 'Portfolio', 'webgram-core' ),
				'icon'     => 'image',
				'priority' => 115,
				'sections' => [
					'help_page' => [
				'id'       => 'help_page',
				'label'    => __( 'Help page', 'webgram-core' ),
				'icon'     => 'help-circle',
				'priority' => 192,
				'sections' => [
					'faq' => [
						'description' => __( 'Shown by the theme "Help" page template as an accordion, with contact cards from the Contact seller settings.', 'webgram-core' ),
						'fields'      => [
							'help_faqs' => [ 'label' => __( 'FAQ entries', 'webgram-core' ), 'type' => 'textarea', 'rows' => 12, 'full' => true, 'default' => '', 'description' => __( 'One entry per block separated by a blank line: first line is the question, the following lines are the answer.', 'webgram-core' ) ],
						],
					],
				],
			],
			'portfolio' => [
						'fields' => [
							'portfolio_enabled'  => [ 'label' => __( 'Enable portfolio post type', 'webgram-core' ), 'type' => 'switch', 'default' => false, 'description' => __( 'Adds a Portfolio post type with categories. Save permalinks after enabling.', 'webgram-core' ) ],
							'portfolio_slug'     => [ 'label' => __( 'URL slug', 'webgram-core' ), 'type' => 'text', 'default' => 'portfolio', 'show_if' => [ 'portfolio_enabled', '==', true ] ],
							'portfolio_per_page' => [ 'label' => __( 'Items per page', 'webgram-core' ), 'type' => 'number', 'min' => 3, 'max' => 60, 'default' => 12, 'show_if' => [ 'portfolio_enabled', '==', true ] ],
						],
					],
				],
			],
		];
	}

	/** Attach tabs to the theme panel with Core-owned storage callbacks. */
	public function tabs( array $tabs ): array {
		$module = $this->module;
		foreach ( self::definitions( $module ) as $id => $tab ) {
			$tab['owner']  = 'core';
			$tab['values'] = static fn() => $module->settings()->all();
			$tab['save']   = static function ( array $values ) use ( $module, $tab ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}
				$fields = [];
				foreach ( $tab['sections'] as $section ) {
					foreach ( (array) ( $section['fields'] ?? [] ) as $fid => $field ) {
						$field['id']    = $fid;
						$fields[ $fid ] = $field;
					}
				}
				$clean = self::sanitize_values( $fields, $values );
				foreach ( $fields as $fid => $field ) {
					if ( 'secret' === ( $field['type'] ?? '' ) && array_key_exists( $fid, $clean ) ) {
						$clean[ $fid ] = '' === $clean[ $fid ] ? '' : \webgram_core()->crypto()->encrypt( (string) $clean[ $fid ] );
					}
				}
				$module->settings()->save( array_merge( $module->settings()->all(), $clean ) );
			};
			$tab['reset'] = static function () use ( $module, $tab ) {
				$all = $module->settings()->all();
				foreach ( $tab['sections'] as $section ) {
					foreach ( array_keys( (array) ( $section['fields'] ?? [] ) ) as $fid ) {
						unset( $all[ $fid ] );
					}
				}
				$module->settings()->save( $all );
			};
			$tabs[ $id ] = $tab;
		}
		return $tabs;
	}

	/**
	 * Core-side sanitization for values arriving from the theme panel or an import. Mirrors the shared schema.
	 *
	 * @param array<string, array> $fields
	 * @param array<string, mixed> $values
	 */
	public static function sanitize_values( array $fields, array $values ): array {
		$out = [];
		foreach ( $fields as $id => $field ) {
			if ( ! array_key_exists( $id, $values ) ) {
				continue;
			}
			$type  = $field['type'] ?? 'text';
			$value = $values[ $id ];
			switch ( $type ) {
				case 'heading':
				case 'link':
				case 'info':
					continue 2;
				case 'switch':
					$out[ $id ] = in_array( $value, [ 1, '1', true, 'true', 'on' ], true );
					break;
				case 'number':
					$n = is_numeric( $value ) ? $value + 0 : ( $field['default'] ?? 0 );
					if ( isset( $field['min'] ) ) {
						$n = max( $field['min'], $n );
					}
					if ( isset( $field['max'] ) ) {
						$n = min( $field['max'], $n );
					}
					$out[ $id ] = $n;
					break;
				case 'select':
				case 'radio':
					$choices    = (array) ( $field['choices'] ?? [] );
					$out[ $id ] = array_key_exists( (string) $value, $choices ) ? (string) $value : (string) ( $field['default'] ?? '' );
					break;
				case 'multicheck':
					$choices    = array_map( 'strval', array_keys( (array) ( $field['choices'] ?? [] ) ) );
					$out[ $id ] = array_values( array_intersect( array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) ), $choices ) );
					break;
				case 'image':
				case 'page':
				case 'html_block':
					$out[ $id ] = absint( $value );
					break;
				case 'url':
					$out[ $id ] = esc_url_raw( (string) $value );
					break;
				case 'email':
					$out[ $id ] = sanitize_email( (string) $value );
					break;
				case 'textarea':
					$out[ $id ] = sanitize_textarea_field( (string) $value );
					break;
				case 'html':
					$out[ $id ] = wp_kses_post( (string) $value );
					break;
				case 'code':
					if ( 'javascript' === ( $field['language'] ?? '' ) && ! current_user_can( 'unfiltered_html' ) ) {
						continue 2;
					}
					$out[ $id ] = str_replace( "\0", '', (string) $value );
					break;
				case 'secret':
					$out[ $id ] = sanitize_text_field( (string) $value );
					break;
				default:
					$out[ $id ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}
}
