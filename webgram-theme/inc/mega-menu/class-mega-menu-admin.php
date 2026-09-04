<?php
/**
 * Mega menu fields on Appearance > Menus items. Stored as one post meta array _wg_mega per item.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Mega_Menu_Admin {

	public const META = '_wg_mega';

	public static function init(): void {
		add_action( 'wp_nav_menu_item_custom_fields', [ self::class, 'fields' ], 10, 4 );
		add_action( 'wp_update_nav_menu_item', [ self::class, 'save' ], 10, 2 );
		add_action( 'wp_update_nav_menu', [ self::class, 'flush' ] );
		add_action( 'wp_update_nav_menu_item', [ self::class, 'flush' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
	}

	public static function assets( string $hook ): void {
		if ( 'nav-menus.php' === $hook ) {
			wp_enqueue_media();
			wp_enqueue_style( 'webgram-admin', WEBGRAM_URI . '/assets/admin/settings.css', [], webgram_asset_version( 'admin/settings.css' ) );
			wp_enqueue_script( 'webgram-admin', WEBGRAM_URI . '/assets/admin/settings.js', [ 'jquery', 'wp-color-picker' ], webgram_asset_version( 'admin/settings.js' ), true );
			wp_enqueue_style( 'wp-color-picker' );
			wp_localize_script( 'webgram-admin', 'webgramAdmin', [ 'codeEditor' => [], 'i18n' => [ 'unsaved' => '', 'confirm' => '', 'remove' => '', 'choose' => __( 'Choose image', 'webgram' ) ] ] );
		}
	}

	/** Field definitions per depth. Depth 0: panel options. Depth 1: column options. Any depth: badge, icon. */
	public static function fields_for( int $depth ): array {
		$common = [
			'badge_text'  => [ 'label' => __( 'Badge text', 'webgram' ), 'type' => 'text', 'default' => '', 'placeholder' => __( 'New, Sale, Hot', 'webgram' ) ],
			'badge_color' => [ 'label' => __( 'Badge color', 'webgram' ), 'type' => 'color', 'default' => '' ],
			'icon'        => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => '' ],
		];
		if ( 0 === $depth ) {
			return [
				'mega'     => [ 'label' => __( 'Mega menu panel', 'webgram' ), 'type' => 'switch', 'default' => false ],
				'width'    => [ 'label' => __( 'Panel width', 'webgram' ), 'type' => 'select', 'choices' => [ 'container' => __( 'Container', 'webgram' ), 'full' => __( 'Full width', 'webgram' ), 'custom' => __( 'Custom', 'webgram' ) ], 'default' => 'container' ],
				'width_px' => [ 'label' => __( 'Custom width', 'webgram' ), 'type' => 'number', 'min' => 300, 'max' => 1600, 'unit' => 'px', 'default' => 900 ],
				'columns'  => [ 'label' => __( 'Columns', 'webgram' ), 'type' => 'select', 'choices' => [ 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6' ], 'default' => 4 ],
			] + $common;
		}
		if ( 1 === $depth ) {
			return [
				'heading_style' => [ 'label' => __( 'Column heading', 'webgram' ), 'type' => 'select', 'choices' => [ 'default' => __( 'Default', 'webgram' ), 'bold' => __( 'Bold uppercase', 'webgram' ), 'hidden' => __( 'Hide label', 'webgram' ) ], 'default' => 'default' ],
				'image'         => [ 'label' => __( 'Column image', 'webgram' ), 'type' => 'image', 'default' => 0 ],
				'description'   => [ 'label' => __( 'Description', 'webgram' ), 'type' => 'text', 'default' => '' ],
				'promo_image'   => [ 'label' => __( 'Promo block image', 'webgram' ), 'type' => 'image', 'default' => 0 ],
				'promo_heading' => [ 'label' => __( 'Promo heading', 'webgram' ), 'type' => 'text', 'default' => '' ],
				'promo_link'    => [ 'label' => __( 'Promo link', 'webgram' ), 'type' => 'url', 'default' => '' ],
			] + $common;
		}
		return [
			'description' => [ 'label' => __( 'Description', 'webgram' ), 'type' => 'text', 'default' => '' ],
			'hide_label'  => [ 'label' => __( 'Hide label (icon only)', 'webgram' ), 'type' => 'switch', 'default' => false ],
		] + $common;
	}

	/** @return array<string, mixed> item settings merged with depth defaults */
	public static function get( int $item_id, int $depth = 0 ): array {
		$stored   = get_post_meta( $item_id, self::META, true );
		$defaults = [];
		foreach ( self::fields_for( $depth ) as $id => $field ) {
			$defaults[ $id ] = $field['default'];
		}
		return array_merge( $defaults, is_array( $stored ) ? $stored : [] );
	}

	public static function fields( int $item_id, object $item, int $depth, mixed $args ): void {
		$values = self::get( $item_id, $depth );
		echo '<div class="wg-mega-fields" data-wg-mega-fields>';
		echo '<p class="description wg-mega-fields__title">' . esc_html__( 'Webgram mega menu', 'webgram' ) . '</p>';
		foreach ( self::fields_for( $depth ) as $id => $field ) {
			$field['id'] = $id;
			Webgram_Settings_Fields::render( $field, $values[ $id ] ?? $field['default'], 'wg_mega[' . $item_id . ']' );
		}
		echo '</div>';
	}

	public static function save( int $menu_id, int $item_id ): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['update-nav-menu-nonce'] ) ), 'update-nav_menu' ) ) {
			return;
		}
		if ( ! isset( $_POST['wg_mega'][ $item_id ] ) || ! is_array( $_POST['wg_mega'][ $item_id ] ) ) {
			return;
		}
		$raw   = Webgram_Settings_Page::strip_none_markers( wp_unslash( $_POST['wg_mega'][ $item_id ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$clean = [];
		// Depth is unknown here; sanitize against the union of all depth schemas.
		$fields = self::fields_for( 0 ) + self::fields_for( 1 ) + self::fields_for( 2 );
		foreach ( $fields as $id => $field ) {
			$field['id'] = $id;
			if ( array_key_exists( $id, $raw ) ) {
				$clean[ $id ] = Webgram_Settings_Sanitizer::sanitize( $field, $raw[ $id ] );
			}
		}
		if ( $clean ) {
			update_post_meta( $item_id, self::META, $clean );
		} else {
			delete_post_meta( $item_id, self::META );
		}
	}

	public static function flush(): void {
		delete_transient( 'webgram_mega_menu' );
	}
}

Webgram_Mega_Menu_Admin::init();
