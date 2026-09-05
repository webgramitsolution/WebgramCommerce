<?php
/**
 * Presentational sections (section heading, banner, benefits row, blog grid, about split, marquee strip).
 * One renderer per section in template-parts/sections. When Webgram Core is active its Integrations module turns
 * these definitions into Elementor widgets, blocks and shortcodes through the webgram_core/elementor/widgets
 * filter; without Core the theme registers the same sections as server-rendered Gutenberg blocks itself.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Sections {

	public static function init(): void {
		add_filter( 'webgram_core/elementor/widgets', [ self::class, 'core_definitions' ] );
		add_action( 'init', [ self::class, 'register_blocks' ], 30 );
		add_filter( 'block_categories_all', [ self::class, 'block_category' ] );
	}

	/** @return array<string, array> */
	public static function definitions(): array {
		$benefit_fields = [
			'icon'  => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'select', 'options' => static fn() => webgram_icon_choices(), 'default' => 'check-circle' ],
			'title' => [ 'label' => __( 'Title', 'webgram' ), 'type' => 'text', 'default' => '' ],
			'text'  => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'text', 'default' => '' ],
		];
		return [
			'section_heading' => [
				'title'    => __( 'Webgram Section Heading', 'webgram' ),
				'icon'     => 'eicon-heading',
				'controls' => [
					'title'     => [ 'label' => __( 'Title', 'webgram' ), 'type' => 'text', 'default' => __( 'Section title', 'webgram' ) ],
					'subtitle'  => [ 'label' => __( 'Subtitle', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'align'     => [ 'label' => __( 'Align', 'webgram' ), 'type' => 'select', 'options' => [ 'center' => __( 'Center', 'webgram' ), 'start' => __( 'Left', 'webgram' ) ], 'default' => 'center' ],
					'link_text' => [ 'label' => __( 'Link text', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'link_url'  => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'default' => '' ],
					'tag'       => [ 'label' => __( 'Heading tag', 'webgram' ), 'type' => 'select', 'options' => [ 'h2' => 'H2', 'h1' => 'H1', 'h3' => 'H3', 'h4' => 'H4' ], 'default' => 'h2' ],
				],
			],
			'banner' => [
				'title'    => __( 'Webgram Banner', 'webgram' ),
				'icon'     => 'eicon-banner',
				'controls' => [
					'image'        => [ 'label' => __( 'Image', 'webgram' ), 'type' => 'image', 'default' => 0 ],
					'image_mobile' => [ 'label' => __( 'Mobile image', 'webgram' ), 'type' => 'image', 'default' => 0 ],
					'heading'      => [ 'label' => __( 'Heading', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'text'         => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'textarea', 'default' => '' ],
					'button_text'  => [ 'label' => __( 'Button text', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'button_url'   => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'default' => '' ],
					'align'        => [ 'label' => __( 'Content align', 'webgram' ), 'type' => 'select', 'options' => [ 'left' => __( 'Left', 'webgram' ), 'center' => __( 'Center', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ], 'default' => 'left' ],
					'height'       => [ 'label' => __( 'Min height (px)', 'webgram' ), 'type' => 'number', 'min' => 120, 'max' => 900, 'default' => 320 ],
					'overlay'      => [ 'label' => __( 'Overlay darkness (%)', 'webgram' ), 'type' => 'number', 'min' => 0, 'max' => 100, 'default' => 20 ],
					'text_color'   => [ 'label' => __( 'Text color', 'webgram' ), 'type' => 'color', 'default' => '#ffffff' ],
					'radius'       => [ 'label' => __( 'Rounded corners', 'webgram' ), 'type' => 'switch', 'default' => true ],
				],
			],
			'benefits' => [
				'title'    => __( 'Webgram Benefits Row', 'webgram' ),
				'icon'     => 'eicon-info-box',
				'controls' => [
					'items'   => [ 'label' => __( 'Benefits', 'webgram' ), 'type' => 'repeater', 'max' => 6, 'fields' => $benefit_fields, 'default' => self::default_benefits() ],
					'columns' => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'number', 'min' => 1, 'max' => 6, 'default' => 5 ],
					'style'   => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'select', 'options' => [ 'row' => __( 'Plain row', 'webgram' ), 'cards' => __( 'Cards', 'webgram' ) ], 'default' => 'row' ],
				],
			],
			'blog_grid' => [
				'title'    => __( 'Webgram Blog Grid', 'webgram' ),
				'icon'     => 'eicon-posts-grid',
				'controls' => [
					'title'        => [ 'label' => __( 'Title', 'webgram' ), 'type' => 'text', 'default' => __( 'From Our Blog', 'webgram' ) ],
					'subtitle'     => [ 'label' => __( 'Subtitle', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'count'        => [ 'label' => __( 'Posts', 'webgram' ), 'type' => 'number', 'min' => 1, 'max' => 12, 'default' => 4 ],
					'columns'      => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'number', 'min' => 1, 'max' => 4, 'default' => 4 ],
					'category'     => [ 'label' => __( 'Category slug', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'show_excerpt' => [ 'label' => __( 'Show excerpt', 'webgram' ), 'type' => 'switch', 'default' => true ],
					'link_text'    => [ 'label' => __( 'Link text', 'webgram' ), 'type' => 'text', 'default' => __( 'View All', 'webgram' ) ],
					'link_url'     => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'default' => '' ],
				],
			],
			'about' => [
				'title'    => __( 'Webgram About Split', 'webgram' ),
				'icon'     => 'eicon-info-box',
				'controls' => [
					'image'          => [ 'label' => __( 'Image', 'webgram' ), 'type' => 'image', 'default' => 0 ],
					'image_position' => [ 'label' => __( 'Image position', 'webgram' ), 'type' => 'select', 'options' => [ 'left' => __( 'Left', 'webgram' ), 'right' => __( 'Right', 'webgram' ) ], 'default' => 'left' ],
					'title'          => [ 'label' => __( 'Heading', 'webgram' ), 'type' => 'text', 'default' => __( 'About Us', 'webgram' ) ],
					'subtitle'       => [ 'label' => __( 'Subtitle', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'text'           => [ 'label' => __( 'Paragraphs', 'webgram' ), 'type' => 'textarea', 'default' => '' ],
					'items'          => [ 'label' => __( 'Benefit cards', 'webgram' ), 'type' => 'repeater', 'max' => 3, 'fields' => $benefit_fields, 'default' => [] ],
					'button_text'    => [ 'label' => __( 'Button text', 'webgram' ), 'type' => 'text', 'default' => __( 'Know More', 'webgram' ) ],
					'button_url'     => [ 'label' => __( 'Button link', 'webgram' ), 'type' => 'url', 'default' => '' ],
				],
			],
			'strip' => [
				'title'    => __( 'Webgram Marquee Strip', 'webgram' ),
				'icon'     => 'eicon-marquee',
				'controls' => [
					'items'      => [ 'label' => __( 'Messages', 'webgram' ), 'type' => 'repeater', 'max' => 8, 'fields' => [ 'icon' => $benefit_fields['icon'], 'text' => $benefit_fields['text'] ], 'default' => [ [ 'icon' => 'truck', 'text' => __( 'Free shipping across India on orders above ₹499', 'webgram' ) ], [ 'icon' => 'award', 'text' => __( 'Trusted by 25,000+ homes and businesses', 'webgram' ) ] ] ],
					'bg_color'   => [ 'label' => __( 'Background', 'webgram' ), 'type' => 'color', 'default' => '' ],
					'text_color' => [ 'label' => __( 'Text color', 'webgram' ), 'type' => 'color', 'default' => '' ],
					'speed'      => [ 'label' => __( 'Speed (pixels per second)', 'webgram' ), 'type' => 'number', 'min' => 10, 'max' => 200, 'default' => 50 ],
					'separator'  => [ 'label' => __( 'Separator', 'webgram' ), 'type' => 'select', 'options' => [ 'dot' => __( 'Dot', 'webgram' ), 'line' => __( 'Line', 'webgram' ), 'none' => __( 'None', 'webgram' ) ], 'default' => 'dot' ],
				],
			],
		];
	}

	public static function default_benefits(): array {
		return [
			[ 'icon' => 'award', 'title' => __( 'Great Value', 'webgram' ), 'text' => __( 'Quality products at honest prices', 'webgram' ) ],
			[ 'icon' => 'truck', 'title' => __( 'Nationwide Delivery', 'webgram' ), 'text' => __( 'Shipping across India', 'webgram' ) ],
			[ 'icon' => 'lock', 'title' => __( 'Secure Payment', 'webgram' ), 'text' => __( 'UPI, cards, wallets and COD', 'webgram' ) ],
			[ 'icon' => 'shield', 'title' => __( 'Buyer Protection', 'webgram' ), 'text' => __( 'Easy returns and refunds', 'webgram' ) ],
			[ 'icon' => 'headset', 'title' => __( '365 Days Help Desk', 'webgram' ), 'text' => __( 'We are here whenever you need us', 'webgram' ) ],
		];
	}

	/** Render a section through its template part; $args are already sanitized by the caller. */
	public static function render( string $id, array $args ): string {
		if ( ! isset( self::definitions()[ $id ] ) ) {
			return '';
		}
		ob_start();
		webgram_part( 'sections/' . str_replace( '_', '-', $id ), $args );
		return (string) ob_get_clean();
	}

	/** Hand the definitions to Webgram Core so they become Elementor widgets, blocks and shortcodes. */
	public static function core_definitions( array $widgets ): array {
		foreach ( self::definitions() as $id => $def ) {
			$def['render']  = static fn( array $args ) => self::render( $id, $args );
			$def['category'] = 'webgram';
			$widgets[ $id ] = $def;
		}
		return $widgets;
	}

	public static function block_category( array $categories ): array {
		foreach ( $categories as $cat ) {
			if ( 'webgram' === ( $cat['slug'] ?? '' ) ) {
				return $categories;
			}
		}
		array_unshift( $categories, [ 'slug' => 'webgram', 'title' => __( 'Webgram', 'webgram' ), 'icon' => null ] );
		return $categories;
	}

	/** Pure: sanitize block attributes against a definition's controls. */
	public static function sanitize( array $controls, array $raw ): array {
		$out = [];
		foreach ( $controls as $cid => $c ) {
			$value = array_key_exists( $cid, $raw ) ? $raw[ $cid ] : ( $c['default'] ?? '' );
			switch ( $c['type'] ) {
				case 'number':
					$n = is_numeric( $value ) ? $value + 0 : (int) ( $c['default'] ?? 0 );
					$out[ $cid ] = min( $c['max'] ?? PHP_INT_MAX, max( $c['min'] ?? PHP_INT_MIN, $n ) );
					break;
				case 'switch':
					$out[ $cid ] = in_array( $value, [ true, 1, '1', 'yes', 'true' ], true );
					break;
				case 'select':
					$options     = is_callable( $c['options'] ?? null ) ? ( $c['options'] )() : (array) ( $c['options'] ?? [] );
					$out[ $cid ] = array_key_exists( (string) $value, $options ) ? (string) $value : (string) ( $c['default'] ?? '' );
					break;
				case 'url':
					$out[ $cid ] = esc_url_raw( (string) $value );
					break;
				case 'color':
					$out[ $cid ] = (string) sanitize_hex_color( (string) $value );
					break;
				case 'image':
					$out[ $cid ] = absint( $value );
					break;
				case 'repeater':
					$rows = [];
					foreach ( (array) $value as $row ) {
						if ( is_array( $row ) ) {
							$rows[] = self::sanitize( (array) ( $c['fields'] ?? [] ), $row );
						}
					}
					$out[ $cid ] = array_slice( $rows, 0, (int) ( $c['max'] ?? 50 ) );
					break;
				case 'textarea':
					$out[ $cid ] = sanitize_textarea_field( (string) $value );
					break;
				default:
					$out[ $cid ] = sanitize_text_field( (string) $value );
			}
		}
		return $out;
	}

	/** Without Core, the theme registers its own server-rendered blocks (Core registers everything otherwise). */
	public static function register_blocks(): void {
		if ( webgram_has_core( 'integrations' ) || ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$definitions = [];
		wp_register_script( 'webgram-blocks', WEBGRAM_URI . '/assets/js/blocks.js', [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ], webgram_asset_version( 'js/blocks.js' ), true );
		foreach ( self::definitions() as $id => $def ) {
			$attributes = [ 'align' => [ 'type' => 'string', 'default' => '' ], 'className' => [ 'type' => 'string', 'default' => '' ] ];
			$controls   = [];
			foreach ( $def['controls'] as $cid => $c ) {
				$attributes[ $cid ] = match ( $c['type'] ) {
					'number', 'image' => [ 'type' => 'number', 'default' => (int) ( $c['default'] ?? 0 ) ],
					'switch'   => [ 'type' => 'boolean', 'default' => (bool) ( $c['default'] ?? false ) ],
					'repeater' => [ 'type' => 'array', 'default' => (array) ( $c['default'] ?? [] ) ],
					default    => [ 'type' => 'string', 'default' => (string) ( $c['default'] ?? '' ) ],
				};
				if ( isset( $c['options'] ) && is_callable( $c['options'] ) ) {
					$c['options'] = ( $c['options'] )();
				}
				if ( 'repeater' === $c['type'] ) {
					foreach ( $c['fields'] as $fid => $f ) {
						if ( isset( $f['options'] ) && is_callable( $f['options'] ) ) {
							$c['fields'][ $fid ]['options'] = ( $f['options'] )();
						}
					}
				}
				$controls[ $cid ] = $c;
			}
			$definitions[ $id ] = [ 'title' => $def['title'], 'icon' => 'layout', 'controls' => $controls ];
			register_block_type(
				'webgram/' . str_replace( '_', '-', $id ),
				[
					'api_version'     => 3,
					'title'           => $def['title'],
					'category'        => 'webgram',
					'attributes'      => $attributes,
					'editor_script'   => 'webgram-blocks',
					'supports'        => [ 'align' => [ 'wide', 'full' ], 'html' => false ],
					'render_callback' => static function ( array $attrs ) use ( $id, $def ): string {
						$html = self::render( $id, self::sanitize( $def['controls'], $attrs ) );
						return '' === $html ? '' : '<div class="wp-block-webgram-' . esc_attr( str_replace( '_', '-', $id ) ) . ( ! empty( $attrs['align'] ) ? ' align' . esc_attr( sanitize_html_class( (string) $attrs['align'] ) ) : '' ) . '">' . $html . '</div>';
					},
				]
			);
		}
		wp_localize_script( 'webgram-blocks', 'webgramCoreBlocks', [ 'definitions' => $definitions ] );
	}
}

Webgram_Sections::init();
