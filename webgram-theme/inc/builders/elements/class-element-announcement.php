<?php
/**
 * Element: Announcement marquee (continuous scrolling messages with icons, static or one-at-a-time modes).
 * Also used by the homepage "marquee strip" section with different colors.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Announcement extends Webgram_Element {

	public function id(): string {
		return 'announcement';
	}

	public function label(): string {
		return __( 'Announcement marquee', 'webgram' );
	}

	public function icon(): string {
		return 'megaphone';
	}

	public function group(): string {
		return 'content';
	}

	public function settings_fields(): array {
		return [
			'messages'  => [
				'label'       => __( 'Messages', 'webgram' ),
				'type'        => 'repeater',
				'max'         => 12,
				'title_field' => 'text',
				'default'     => [
					[ 'icon' => 'tag', 'text' => __( 'Flat 10% OFF on prepaid orders', 'webgram' ), 'link' => '' ],
					[ 'icon' => 'truck', 'text' => __( 'Free shipping on orders above ₹499', 'webgram' ), 'link' => '' ],
					[ 'icon' => 'gift', 'text' => __( 'Use code WELCOME15 on your first order', 'webgram' ), 'link' => '' ],
				],
				'fields'      => [
					'icon' => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => 'tag' ],
					'text' => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'text', 'default' => '' ],
					'link' => [ 'label' => __( 'Link', 'webgram' ), 'type' => 'url', 'default' => '' ],
				],
			],
			'mode'      => [ 'label' => __( 'Mode', 'webgram' ), 'type' => 'radio', 'choices' => [ 'marquee' => __( 'Continuous marquee', 'webgram' ), 'static' => __( 'Static, centered', 'webgram' ), 'slide' => __( 'One message at a time', 'webgram' ) ], 'default' => 'marquee' ],
			'speed'     => [ 'label' => __( 'Speed', 'webgram' ), 'type' => 'number', 'min' => 10, 'max' => 200, 'unit' => 'px/s', 'default' => 50 ],
			'direction' => [ 'label' => __( 'Direction', 'webgram' ), 'type' => 'radio', 'choices' => [ 'left' => __( 'Right to left', 'webgram' ), 'right' => __( 'Left to right', 'webgram' ) ], 'default' => 'left' ],
			'pause'     => [ 'label' => __( 'Pause on hover', 'webgram' ), 'type' => 'switch', 'default' => true ],
			'gap'       => [ 'label' => __( 'Gap between messages', 'webgram' ), 'type' => 'number', 'min' => 16, 'max' => 160, 'unit' => 'px', 'default' => 48 ],
			'separator' => [ 'label' => __( 'Separator', 'webgram' ), 'type' => 'radio', 'choices' => [ 'dot' => __( 'Dot', 'webgram' ), 'line' => __( 'Line', 'webgram' ), 'none' => __( 'None', 'webgram' ) ], 'default' => 'dot' ],
			'interval'  => [ 'label' => __( 'Slide interval', 'webgram' ), 'type' => 'number', 'min' => 1000, 'max' => 15000, 'step' => 250, 'unit' => 'ms', 'default' => 4000, 'show_if' => [ 'mode', '==', 'slide' ] ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		webgram_part( 'header/marquee', [ 'settings' => $settings, 'device' => $device ] );
	}
}
