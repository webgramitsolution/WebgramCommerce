<?php
/**
 * Elements: search bar (with mic slot and live results) and search toggle (opens the overlay).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Search extends Webgram_Element {

	public function id(): string {
		return 'search';
	}

	public function label(): string {
		return __( 'Search bar', 'webgram' );
	}

	public function icon(): string {
		return 'search';
	}

	public function group(): string {
		return 'search';
	}

	public function settings_fields(): array {
		return [
			'placeholder' => [ 'label' => __( 'Placeholder', 'webgram' ), 'type' => 'text', 'default' => __( 'Search products...', 'webgram' ) ],
			'min_width'   => [ 'label' => __( 'Minimum width (desktop)', 'webgram' ), 'type' => 'number', 'min' => 200, 'max' => 900, 'unit' => 'px', 'default' => 420 ],
			'style'       => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'pill' => __( 'Pill', 'webgram' ), 'rounded' => __( 'Rounded', 'webgram' ), 'square' => __( 'Square', 'webgram' ) ], 'default' => 'pill' ],
			'button'      => [ 'label' => __( 'Visible submit button', 'webgram' ), 'type' => 'switch', 'default' => false ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		webgram_part(
			'header/search-form',
			[
				'id'          => 'wg-search-' . $device,
				'placeholder' => (string) $settings['placeholder'],
				'style'       => (string) $settings['style'],
				'min_width'   => (int) $settings['min_width'],
				'button'      => ! empty( $settings['button'] ),
			]
		);
	}
}

final class Webgram_Element_Search_Toggle extends Webgram_Element {

	public function id(): string {
		return 'search_toggle';
	}

	public function label(): string {
		return __( 'Search icon (overlay)', 'webgram' );
	}

	public function icon(): string {
		return 'search';
	}

	public function group(): string {
		return 'search';
	}

	public function settings_fields(): array {
		return $this->icon_fields( __( 'Search', 'webgram' ), 'search' );
	}

	public function render( array $settings, string $device, string $context ): void {
		printf(
			'<button class="wg-icon-btn%s" type="button" data-wg-toggle="search-overlay" aria-controls="wg-search-overlay" aria-expanded="false">%s%s</button>',
			empty( $settings['show_label'] ) ? ' wg-icon-btn--no-label' : '',
			webgram_icon( (string) $settings['icon'], '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $settings['show_label'] ) ? '<span class="wg-icon-btn__label">' . esc_html( (string) $settings['label'] ) . '</span>' : '<span class="wg-sr-only">' . esc_html( (string) $settings['label'] ) . '</span>'
		);
	}
}
