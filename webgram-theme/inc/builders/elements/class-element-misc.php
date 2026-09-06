<?php
/**
 * Elements: social icons, button, text, HTML block, phone, divider, spacer, mobile menu toggle, currency and
 * language slots.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Social extends Webgram_Element {

	public function id(): string {
		return 'social';
	}

	public function label(): string {
		return __( 'Social icons', 'webgram' );
	}

	public function icon(): string {
		return 'share';
	}

	public function settings_fields(): array {
		return [
			'heading'     => [ 'label' => __( 'Heading (footer)', 'webgram' ), 'type' => 'text', 'default' => __( 'Connect', 'webgram' ) ],
			'style'       => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'plain' => __( 'Icons', 'webgram' ), 'circle' => __( 'Circle outline', 'webgram' ), 'filled' => __( 'Brand colors', 'webgram' ) ], 'default' => 'circle' ],
			'show_labels' => [ 'label' => __( 'Show network names', 'webgram' ), 'type' => 'switch', 'default' => false ],
			'direction'   => [ 'label' => __( 'Direction', 'webgram' ), 'type' => 'radio', 'choices' => [ 'row' => __( 'Row', 'webgram' ), 'column' => __( 'Column', 'webgram' ) ], 'default' => 'row' ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$links = (array) webgram_option( 'social_links' );
		$links = array_filter( $links, static fn( $l ) => ! empty( $l['url'] ) && ! empty( $l['network'] ) );
		if ( ! $links ) {
			return;
		}
		$names = webgram_social_networks();
		if ( 'footer' === $context && ! empty( $settings['heading'] ) ) {
			printf( '<h3 class="wg-footer__heading">%s</h3>', esc_html( (string) $settings['heading'] ) );
		}
		printf( '<ul class="wg-social wg-social--%s wg-social--%s%s">', esc_attr( (string) $settings['style'] ), esc_attr( (string) $settings['direction'] ), ! empty( $settings['show_labels'] ) ? ' wg-social--labels' : '' );
		foreach ( $links as $link ) {
			$net = sanitize_key( (string) $link['network'] );
			printf(
				'<li><a class="wg-social__link wg-social__link--%1$s" href="%2$s" target="_blank" rel="noopener" style="--wg-brand:%5$s">%3$s<span class="%6$s">%4$s</span></a></li>',
				esc_attr( $net ),
				esc_url( (string) $link['url'] ),
				webgram_icon( 'social-' . $net, '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( (string) ( $names[ $net ] ?? $net ) ),
				esc_attr( webgram_social_color( $net ) ),
				! empty( $settings['show_labels'] ) ? 'wg-social__label' : 'wg-sr-only'
			);
		}
		echo '</ul>';
	}
}

final class Webgram_Element_Button extends Webgram_Element {

	public function id(): string {
		return 'button';
	}

	public function label(): string {
		return __( 'Button', 'webgram' );
	}

	public function icon(): string {
		return 'square';
	}

	public function settings_fields(): array {
		return [
			'label'  => [ 'label' => __( 'Label', 'webgram' ), 'type' => 'text', 'default' => __( 'Shop now', 'webgram' ) ],
			'url'    => [ 'label' => __( 'URL', 'webgram' ), 'type' => 'url', 'default' => '' ],
			'style'  => [ 'label' => __( 'Style', 'webgram' ), 'type' => 'radio', 'choices' => [ 'primary' => __( 'Primary', 'webgram' ), 'outline' => __( 'Outline', 'webgram' ), 'secondary' => __( 'Dark', 'webgram' ) ], 'default' => 'primary' ],
			'icon'   => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => '' ],
			'target' => [ 'label' => __( 'Open in new tab', 'webgram' ), 'type' => 'switch', 'default' => false ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( '' === $settings['label'] ) {
			return;
		}
		printf(
			'<a class="wg-btn wg-btn--sm wg-btn--%s" href="%s"%s>%s<span>%s</span></a>',
			esc_attr( (string) $settings['style'] ),
			esc_url( (string) ( $settings['url'] ?: home_url( '/' ) ) ),
			! empty( $settings['target'] ) ? ' target="_blank" rel="noopener"' : '',
			$settings['icon'] ? webgram_icon( (string) $settings['icon'], '', false ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( (string) $settings['label'] )
		);
	}
}

final class Webgram_Element_Text extends Webgram_Element {

	public function id(): string {
		return 'text';
	}

	public function label(): string {
		return __( 'Text', 'webgram' );
	}

	public function icon(): string {
		return 'type';
	}

	public function settings_fields(): array {
		return [
			'text' => [ 'label' => __( 'Text (basic HTML allowed)', 'webgram' ), 'type' => 'html', 'rows' => 3, 'default' => '' ],
			'icon' => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => '' ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( '' === trim( (string) $settings['text'] ) ) {
			return;
		}
		echo '<div class="wg-text-el">' . ( $settings['icon'] ? webgram_icon( (string) $settings['icon'], '', false ) : '' ) . wp_kses_post( (string) $settings['text'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

final class Webgram_Element_Html_Block extends Webgram_Element {

	public function id(): string {
		return 'html_block';
	}

	public function label(): string {
		return __( 'HTML Block', 'webgram' );
	}

	public function icon(): string {
		return 'code';
	}

	public function is_available(): bool {
		return webgram_has_core( 'site_tools' );
	}

	public function settings_fields(): array {
		return [
			'block' => [ 'label' => __( 'Block', 'webgram' ), 'type' => 'html_block', 'default' => 0 ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		webgram_render_block( (int) $settings['block'] );
	}
}

final class Webgram_Element_Phone extends Webgram_Element {

	public function id(): string {
		return 'phone';
	}

	public function label(): string {
		return __( 'Phone', 'webgram' );
	}

	public function icon(): string {
		return 'phone';
	}

	public function settings_fields(): array {
		return [
			'number' => [ 'label' => __( 'Phone number', 'webgram' ), 'type' => 'text', 'default' => '' ],
			'label'  => [ 'label' => __( 'Small label', 'webgram' ), 'type' => 'text', 'default' => __( 'Call us', 'webgram' ) ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$number = trim( (string) $settings['number'] );
		if ( '' === $number ) {
			return;
		}
		printf(
			'<a class="wg-phone" href="tel:%s">%s<span class="wg-phone__text"><small>%s</small><strong>%s</strong></span></a>',
			esc_attr( preg_replace( '/[^0-9+]/', '', $number ) ),
			webgram_icon( 'phone', '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( (string) $settings['label'] ),
			esc_html( $number )
		);
	}
}

final class Webgram_Element_Divider extends Webgram_Element {

	public function id(): string {
		return 'divider';
	}

	public function label(): string {
		return __( 'Divider', 'webgram' );
	}

	public function icon(): string {
		return 'minus';
	}

	public function settings_fields(): array {
		return [ 'height' => [ 'label' => __( 'Height', 'webgram' ), 'type' => 'number', 'min' => 8, 'max' => 80, 'unit' => 'px', 'default' => 28 ] ];
	}

	public function render( array $settings, string $device, string $context ): void {
		printf( '<span class="wg-divider" style="height:%dpx" aria-hidden="true"></span>', (int) $settings['height'] );
	}
}

final class Webgram_Element_Spacer extends Webgram_Element {

	public function id(): string {
		return 'spacer';
	}

	public function label(): string {
		return __( 'Spacer', 'webgram' );
	}

	public function icon(): string {
		return 'move-horizontal';
	}

	public function settings_fields(): array {
		return [ 'width' => [ 'label' => __( 'Width', 'webgram' ), 'type' => 'number', 'min' => 4, 'max' => 200, 'unit' => 'px', 'default' => 24 ] ];
	}

	public function render( array $settings, string $device, string $context ): void {
		printf( '<span class="wg-spacer" style="width:%dpx" aria-hidden="true"></span>', (int) $settings['width'] );
	}
}

final class Webgram_Element_Menu_Toggle extends Webgram_Element {

	public function id(): string {
		return 'menu_toggle';
	}

	public function label(): string {
		return __( 'Mobile menu toggle', 'webgram' );
	}

	public function icon(): string {
		return 'menu';
	}

	public function group(): string {
		return 'navigation';
	}

	public function settings_fields(): array {
		$fields                       = $this->icon_fields( __( 'Menu', 'webgram' ), 'menu' );
		$fields['show_label']['default'] = false;
		return $fields;
	}

	public function render( array $settings, string $device, string $context ): void {
		printf(
			'<button class="wg-icon-btn wg-header__toggle%s" type="button" aria-expanded="false" aria-controls="wg-mobile-menu" data-wg-toggle="mobile-menu">%s%s</button>',
			empty( $settings['show_label'] ) ? ' wg-icon-btn--no-label' : '',
			webgram_icon( (string) $settings['icon'], '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			! empty( $settings['show_label'] ) ? '<span class="wg-icon-btn__label">' . esc_html( (string) $settings['label'] ) . '</span>' : '<span class="wg-sr-only">' . esc_html( (string) $settings['label'] ) . '</span>'
		);
	}
}

/** Renders whatever a currency switcher plugin prints on webgram/header/currency; nothing otherwise. */
final class Webgram_Element_Currency extends Webgram_Element {

	public function id(): string {
		return 'currency';
	}

	public function label(): string {
		return __( 'Currency switcher slot', 'webgram' );
	}

	public function icon(): string {
		return 'dollar';
	}

	public function render( array $settings, string $device, string $context ): void {
		do_action( 'webgram/header/currency', $device );
	}
}

/** Language switcher: Polylang or WPML when present, otherwise the webgram/header/language hook. */
final class Webgram_Element_Language extends Webgram_Element {

	public function id(): string {
		return 'language';
	}

	public function label(): string {
		return __( 'Language switcher', 'webgram' );
	}

	public function icon(): string {
		return 'globe';
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( function_exists( 'pll_the_languages' ) ) {
			echo '<ul class="wg-lang">';
			pll_the_languages( [ 'dropdown' => 0, 'show_flags' => 1, 'show_names' => 1, 'hide_current' => 0 ] );
			echo '</ul>';
			return;
		}
		if ( function_exists( 'icl_get_languages' ) ) {
			$langs = icl_get_languages( 'skip_missing=0' );
			if ( is_array( $langs ) && $langs ) {
				echo '<ul class="wg-lang">';
				foreach ( $langs as $lang ) {
					printf( '<li%s><a href="%s">%s</a></li>', ! empty( $lang['active'] ) ? ' class="is-active"' : '', esc_url( (string) $lang['url'] ), esc_html( (string) $lang['native_name'] ) );
				}
				echo '</ul>';
				return;
			}
		}
		do_action( 'webgram/header/language', $device );
	}
}
