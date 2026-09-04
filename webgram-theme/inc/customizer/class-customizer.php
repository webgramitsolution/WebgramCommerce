<?php
/**
 * Registers Customizer panels, sections, settings and controls from panel files.
 * Native controls only; custom controls (builder, sortable, typography) are added in inc/customizer/controls/.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Customizer {

	public static function init(): void {
		add_action( 'customize_register', [ self::class, 'register' ] );
		add_action( 'customize_preview_init', [ self::class, 'preview_script' ] );
		add_action( 'customize_save_after', [ self::class, 'flush_css' ] );
	}

	public static function register( WP_Customize_Manager $wp_customize ): void {
		$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

		$wp_customize->add_panel(
			'webgram',
			[
				'title'    => __( 'Webgram', 'webgram' ),
				'priority' => 10,
			]
		);

		foreach ( glob( WEBGRAM_DIR . '/inc/customizer/panels/*.php' ) ?: [] as $file ) {
			$sections = require $file;
			if ( is_array( $sections ) ) {
				self::add_sections( $wp_customize, $sections );
			}
		}

		do_action( 'webgram/customizer_register', $wp_customize );
	}

	/**
	 * @param array<string, array{title: string, priority?: int, fields: array<string, array>}> $sections
	 */
	private static function add_sections( WP_Customize_Manager $wp_customize, array $sections ): void {
		$defaults = webgram_defaults();

		foreach ( $sections as $section_id => $section ) {
			$wp_customize->add_section(
				'webgram_' . $section_id,
				[
					'title'    => $section['title'],
					'panel'    => 'webgram',
					'priority' => $section['priority'] ?? 10,
				]
			);

			foreach ( $section['fields'] as $id => $field ) {
				$type = $field['type'] ?? 'text';

				$wp_customize->add_setting(
					$id,
					[
						'default'           => $defaults[ $id ] ?? ( $field['default'] ?? '' ),
						'transport'         => $field['transport'] ?? 'refresh',
						'sanitize_callback' => $field['sanitize'] ?? self::sanitizer_for( $type, $field ),
					]
				);

				$args = [
					'label'       => $field['label'],
					'description' => $field['description'] ?? '',
					'section'     => 'webgram_' . $section_id,
					'type'        => $type,
					'choices'     => $field['choices'] ?? [],
					'input_attrs' => $field['input_attrs'] ?? [],
				];

				if ( 'color' === $type ) {
					$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
				} elseif ( 'image' === $type ) {
					$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $id, $args ) );
				} else {
					$wp_customize->add_control( $id, $args );
				}
			}
		}
	}

	private static function sanitizer_for( string $type, array $field ): callable {
		return match ( $type ) {
			'color'    => 'sanitize_hex_color',
			'checkbox' => static fn( $v ) => (bool) $v,
			'number', 'range' => static fn( $v ) => is_numeric( $v ) ? $v + 0 : 0,
			'select', 'radio' => static fn( $v ) => array_key_exists( $v, $field['choices'] ?? [] ) ? $v : ( $field['default'] ?? '' ),
			'textarea' => 'sanitize_textarea_field',
			'url'      => 'esc_url_raw',
			'image'    => 'esc_url_raw',
			default    => 'sanitize_text_field',
		};
	}

	public static function preview_script(): void {
		wp_enqueue_script( 'webgram-customizer-preview', WEBGRAM_URI . '/assets/js/customizer-preview.js', [ 'customize-preview' ], WEBGRAM_VERSION, true );
		wp_localize_script( 'webgram-customizer-preview', 'webgramTokenMap', Webgram_CSS_Generator::instance()->token_map() );
	}

	public static function flush_css(): void {
		Webgram_CSS_Generator::instance()->flush();
	}
}

Webgram_Customizer::init();
