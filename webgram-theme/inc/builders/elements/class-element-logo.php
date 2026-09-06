<?php
/**
 * Element: Logo (uses the Customizer custom logo; optional alternative image and per-device height).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Logo extends Webgram_Element {

	public function id(): string {
		return 'logo';
	}

	public function label(): string {
		return __( 'Logo', 'webgram' );
	}

	public function icon(): string {
		return 'image';
	}

	public function group(): string {
		return 'brand';
	}

	public function settings_fields(): array {
		return [
			'image'         => [ 'label' => __( 'Alternative logo', 'webgram' ), 'type' => 'image', 'default' => 0, 'description' => __( 'Leave empty to use the logo from Site Identity. In the footer, upload a light version.', 'webgram' ) ],
			'height'        => [ 'label' => __( 'Max height (desktop)', 'webgram' ), 'type' => 'number', 'min' => 20, 'max' => 160, 'unit' => 'px', 'default' => 56 ],
			'height_mobile' => [ 'label' => __( 'Max height (mobile)', 'webgram' ), 'type' => 'number', 'min' => 20, 'max' => 120, 'unit' => 'px', 'default' => 40 ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$height = (int) ( 'mobile' === $device ? $settings['height_mobile'] : $settings['height'] );
		$style  = '--wg-logo-height:' . $height . 'px';
		$image  = (int) $settings['image'];

		echo '<div class="wg-logo" style="' . esc_attr( $style ) . '">';
		if ( $image ) {
			printf(
				'<a class="wg-logo__link" href="%s" rel="home">%s</a>',
				esc_url( home_url( '/' ) ),
				wp_get_attachment_image( $image, 'full', false, [ 'class' => 'wg-logo__img', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager' ] )
			);
		} elseif ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			printf( '<a class="wg-logo__text" href="%s" rel="home">%s</a>', esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
		}
		echo '</div>';
	}
}
