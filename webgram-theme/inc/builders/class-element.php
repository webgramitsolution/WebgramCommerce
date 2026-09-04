<?php
/**
 * Base class for header and footer builder elements, plus an adapter for array-defined elements registered by
 * Webgram Core through the webgram/header/elements and webgram/footer/elements filters.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

abstract class Webgram_Element {

	abstract public function id(): string;

	abstract public function label(): string;

	/** Icon name from assets/images/icons for the builder palette. */
	public function icon(): string {
		return 'box';
	}

	/** Palette group: brand, navigation, search, actions, content. */
	public function group(): string {
		return 'content';
	}

	/** Whether the element can be placed (WooCommerce or Core dependencies). */
	public function is_available(): bool {
		return true;
	}

	/** Field definitions in the same schema as Theme Settings fields (id => field). */
	public function settings_fields(): array {
		return [];
	}

	/** Default values derived from settings_fields(). */
	public function defaults(): array {
		$out = [];
		foreach ( $this->settings_fields() as $id => $field ) {
			$out[ $id ] = $field['default'] ?? '';
		}
		return $out;
	}

	/**
	 * Print the element markup.
	 *
	 * @param array  $settings merged settings
	 * @param string $device   desktop|mobile
	 * @param string $context  header|footer
	 */
	abstract public function render( array $settings, string $device, string $context ): void;

	/** Sanitized settings for this element merged over defaults. */
	public function prepare( array $raw ): array {
		$fields = $this->settings_fields();
		foreach ( $fields as $id => &$field ) {
			$field['id'] = $id;
		}
		unset( $field );
		return array_merge( $this->defaults(), Webgram_Settings_Sanitizer::sanitize_all( $fields, $raw ) );
	}

	/** Wrapper classes for the element container. */
	public function classes( array $settings, string $context ): array {
		return [ 'wg-' . $context . '__el', 'wg-' . $context . '__el--' . $this->id() ];
	}

	/** Icon with a label beneath, the "icon with label" pattern used across the header. */
	protected function icon_link( string $url, string $icon, string $label, array $settings, array $attrs = [], string $extra_html = '' ): void {
		$show_label = ! empty( $settings['show_label'] );
		$icon_name  = ! empty( $settings['icon'] ) ? (string) $settings['icon'] : $icon;
		$attr_html  = '';
		foreach ( $attrs as $k => $v ) {
			$attr_html .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		printf(
			'<a class="wg-icon-btn%s" href="%s"%s>%s%s%s</a>',
			$show_label ? '' : ' wg-icon-btn--no-label',
			esc_url( $url ),
			$attr_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			webgram_icon( $icon_name, '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$show_label ? '<span class="wg-icon-btn__label">' . esc_html( $label ) . '</span>' : '<span class="wg-sr-only">' . esc_html( $label ) . '</span>',
			$extra_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller escapes.
		);
	}

	/** Shared fields for icon-with-label elements. */
	protected function icon_fields( string $default_label, string $default_icon ): array {
		return [
			'label'      => [ 'label' => __( 'Label', 'webgram' ), 'type' => 'text', 'default' => $default_label ],
			'show_label' => [ 'label' => __( 'Show label under icon', 'webgram' ), 'type' => 'switch', 'default' => true ],
			'icon'       => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => $default_icon ],
		];
	}
}

/**
 * Adapter for elements declared as arrays:
 * [ 'id', 'label', 'icon', 'group', 'available' => callable, 'fields' => [], 'render' => callable(array $settings, string $device, string $context) ]
 */
final class Webgram_Element_Callback extends Webgram_Element {

	public function __construct( private array $def ) {}

	public function id(): string {
		return sanitize_key( (string) ( $this->def['id'] ?? '' ) );
	}

	public function label(): string {
		return (string) ( $this->def['label'] ?? $this->id() );
	}

	public function icon(): string {
		return (string) ( $this->def['icon'] ?? 'box' );
	}

	public function group(): string {
		return (string) ( $this->def['group'] ?? 'content' );
	}

	public function is_available(): bool {
		return empty( $this->def['available'] ) || ! is_callable( $this->def['available'] ) || (bool) call_user_func( $this->def['available'] );
	}

	public function settings_fields(): array {
		return (array) ( $this->def['fields'] ?? [] );
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( ! empty( $this->def['render'] ) && is_callable( $this->def['render'] ) ) {
			call_user_func( $this->def['render'], $settings, $device, $context );
		}
	}
}

foreach ( glob( WEBGRAM_DIR . '/inc/builders/elements/*.php' ) ?: [] as $webgram_element_file ) {
	require_once $webgram_element_file;
}
unset( $webgram_element_file );
