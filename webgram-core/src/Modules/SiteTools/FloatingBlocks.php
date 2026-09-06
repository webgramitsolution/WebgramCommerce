<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Floating blocks: up to three fixed buttons (WhatsApp chat, custom link, HTML Block) in a corner of the screen,
 * with position, offset, device visibility and an optional "show after scrolling" threshold.
 * The theme owns its own back to top button and floating social sidebar; this stack sits next to them.
 */
final class FloatingBlocks {

	public const SLOTS = 3;

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'render' ], 25 );
		add_action( 'admin_menu', [ $this, 'menu' ], 60 );
	}

	/** Submenu entry that opens the Floating blocks tab of the settings panel. */
	public function menu(): void {
		$parent = ModulesPage::parent_slug();
		$url    = function_exists( 'webgram_option' ) ? 'admin.php?page=webgram&tab=floating' : 'admin.php?page=webgram-core-settings&tab=site_tools';
		add_submenu_page( $parent, __( 'Floating Blocks', 'webgram-core' ), __( 'Floating Blocks', 'webgram-core' ), 'manage_options', $url );
	}

	/** Field definitions for the settings panel (three slots plus global options). */
	public static function fields(): array {
		$fields = [
			'float_position' => [ 'label' => __( 'Position', 'webgram-core' ), 'type' => 'select', 'choices' => [ 'bottom-right' => __( 'Bottom right', 'webgram-core' ), 'bottom-left' => __( 'Bottom left', 'webgram-core' ), 'middle-right' => __( 'Middle right', 'webgram-core' ), 'middle-left' => __( 'Middle left', 'webgram-core' ) ], 'default' => 'bottom-right' ],
			'float_offset'   => [ 'label' => __( 'Offset from the edge', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 120, 'unit' => 'px', 'default' => 20 ],
			'float_devices'  => [ 'label' => __( 'Devices', 'webgram-core' ), 'type' => 'multicheck', 'choices' => [ 'desktop' => __( 'Desktop', 'webgram-core' ), 'tablet' => __( 'Tablet', 'webgram-core' ), 'mobile' => __( 'Mobile', 'webgram-core' ) ], 'default' => [ 'desktop', 'tablet', 'mobile' ] ],
			'float_scroll'   => [ 'label' => __( 'Show after scrolling', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 2000, 'unit' => 'px', 'default' => 0, 'description' => __( '0 shows the buttons at once.', 'webgram-core' ) ],
			'float_labels'   => [ 'label' => __( 'Show labels next to icons on desktop', 'webgram-core' ), 'type' => 'switch', 'default' => false ],
		];
		for ( $i = 1; $i <= self::SLOTS; $i++ ) {
			$on = [ 'float_' . $i . '_type', '!=', 'none' ];
			$fields += [
				'float_' . $i . '_heading' => [ 'label' => sprintf( /* translators: %d: slot number. */ __( 'Button %d', 'webgram-core' ), $i ), 'type' => 'heading' ],
				'float_' . $i . '_type'    => [ 'label' => __( 'Type', 'webgram-core' ), 'type' => 'select', 'choices' => [ 'none' => __( 'Off', 'webgram-core' ), 'whatsapp' => __( 'WhatsApp chat', 'webgram-core' ), 'link' => __( 'Link with icon', 'webgram-core' ), 'block' => __( 'HTML Block', 'webgram-core' ) ], 'default' => 'none' ],
				'float_' . $i . '_label'   => [ 'label' => __( 'Label', 'webgram-core' ), 'type' => 'text', 'default' => '', 'show_if' => $on, 'description' => __( 'Read by screen readers and shown when labels are on.', 'webgram-core' ) ],
				'float_' . $i . '_number'  => [ 'label' => __( 'WhatsApp number', 'webgram-core' ), 'type' => 'text', 'default' => '', 'placeholder' => '+91 98765 43210', 'show_if' => [ 'float_' . $i . '_type', '==', 'whatsapp' ] ],
				'float_' . $i . '_message' => [ 'label' => __( 'Prefilled message', 'webgram-core' ), 'type' => 'text', 'default' => '', 'show_if' => [ 'float_' . $i . '_type', '==', 'whatsapp' ] ],
				'float_' . $i . '_url'     => [ 'label' => __( 'Link URL', 'webgram-core' ), 'type' => 'url', 'default' => '', 'show_if' => [ 'float_' . $i . '_type', '==', 'link' ] ],
				'float_' . $i . '_icon'    => [ 'label' => __( 'Icon', 'webgram-core' ), 'type' => 'icon', 'default' => 'message-circle', 'show_if' => [ 'float_' . $i . '_type', '==', 'link' ] ],
				'float_' . $i . '_block'   => [ 'label' => __( 'HTML Block', 'webgram-core' ), 'type' => 'html_block', 'default' => 0, 'show_if' => [ 'float_' . $i . '_type', '==', 'block' ] ],
				'float_' . $i . '_color'   => [ 'label' => __( 'Background color', 'webgram-core' ), 'type' => 'color', 'default' => '', 'show_if' => $on, 'description' => __( 'WhatsApp defaults to the brand green; links to the primary color.', 'webgram-core' ) ],
			];
		}
		return $fields;
	}

	/** Builds a wa.me link. Pure, covered by the harness. */
	public static function whatsapp_url( string $number, string $message = '' ): string {
		$digits = preg_replace( '/\D+/', '', $number ) ?? '';
		if ( strlen( $digits ) < 8 ) {
			return '';
		}
		$url = 'https://wa.me/' . $digits;
		return '' !== trim( $message ) ? $url . '?text=' . rawurlencode( trim( $message ) ) : $url;
	}

	/** @return array<int, array{type: string, label: string, url: string, icon: string, block: int, color: string}> */
	public function items(): array {
		$s   = $this->module->settings();
		$out = [];
		for ( $i = 1; $i <= self::SLOTS; $i++ ) {
			$type = (string) $s->get( 'float_' . $i . '_type', 'none' );
			if ( 'none' === $type ) {
				continue;
			}
			$item = [ 'type' => $type, 'label' => (string) $s->get( 'float_' . $i . '_label', '' ), 'url' => '', 'icon' => 'message-circle', 'block' => 0, 'color' => (string) sanitize_hex_color( (string) $s->get( 'float_' . $i . '_color', '' ) ) ];
			if ( 'whatsapp' === $type ) {
				$item['url']   = self::whatsapp_url( (string) $s->get( 'float_' . $i . '_number', '' ), (string) $s->get( 'float_' . $i . '_message', '' ) );
				$item['icon']  = 'social-whatsapp';
				$item['label'] = $item['label'] ?: __( 'Chat on WhatsApp', 'webgram-core' );
				if ( '' === $item['url'] ) {
					continue;
				}
			} elseif ( 'link' === $type ) {
				$item['url']  = esc_url_raw( (string) $s->get( 'float_' . $i . '_url', '' ) );
				$item['icon'] = sanitize_key( (string) $s->get( 'float_' . $i . '_icon', 'message-circle' ) ) ?: 'message-circle';
				if ( '' === $item['url'] ) {
					continue;
				}
			} else {
				$item['block'] = (int) $s->get( 'float_' . $i . '_block', 0 );
				if ( $item['block'] <= 0 ) {
					continue;
				}
			}
			$out[] = $item;
		}
		return (array) apply_filters( 'webgram_core/floating_blocks/items', $out );
	}

	public function render(): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}
		$items = $this->items();
		if ( ! $items ) {
			return;
		}
		$s = $this->module->settings();
		\webgram_core()->assets()->enqueue_module( 'site_tools' );
		\webgram_core()->view(
			'site-tools/floating',
			[
				'items'    => $items,
				'position' => in_array( $s->get( 'float_position', 'bottom-right' ), [ 'bottom-right', 'bottom-left', 'middle-right', 'middle-left' ], true ) ? (string) $s->get( 'float_position' ) : 'bottom-right',
				'offset'   => max( 0, min( 120, (int) $s->get( 'float_offset', 20 ) ) ),
				'devices'  => array_values( array_intersect( [ 'desktop', 'tablet', 'mobile' ], (array) $s->get( 'float_devices', [ 'desktop', 'tablet', 'mobile' ] ) ) ),
				'scroll'   => max( 0, (int) $s->get( 'float_scroll', 0 ) ),
				'labels'   => Helpers::bool( $s->get( 'float_labels', false ) ),
			]
		);
	}
}
