<?php
/**
 * The Customizer keeps only WordPress Site Identity (logo, site icon, tagline) plus a link to the Webgram Theme
 * Settings panel, which is the primary options UI. Nothing else is registered here.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Customizer {

	public static function init(): void {
		add_action( 'customize_register', [ self::class, 'register' ] );
		add_action( 'customize_save_after', [ self::class, 'flush_css' ] );
	}

	public static function register( WP_Customize_Manager $wp_customize ): void {
		$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

		$wp_customize->add_section(
			'webgram_link',
			[
				'title'       => __( 'Webgram Theme Settings', 'webgram' ),
				'priority'    => 5,
				'description' => sprintf(
					'<p>%s</p><p><a class="button button-primary" href="%s" target="_blank" rel="noopener">%s</a></p>',
					esc_html__( 'Colors, typography, header and footer builders, shop and product options live in the Webgram Theme Settings panel.', 'webgram' ),
					esc_url( admin_url( 'admin.php?page=webgram' ) ),
					esc_html__( 'Open Webgram Theme Settings', 'webgram' )
				),
			]
		);
		// A section needs at least one control to be listed; this hidden setting only carries the description.
		$wp_customize->add_setting( 'webgram_link_placeholder', [ 'sanitize_callback' => 'sanitize_text_field', 'type' => 'option', 'capability' => 'edit_theme_options' ] );
		$wp_customize->add_control( 'webgram_link_placeholder', [ 'section' => 'webgram_link', 'type' => 'hidden', 'label' => '' ] );

		do_action( 'webgram/customizer_register', $wp_customize );
	}

	public static function flush_css(): void {
		Webgram_CSS_Generator::instance()->flush();
	}
}

Webgram_Customizer::init();
