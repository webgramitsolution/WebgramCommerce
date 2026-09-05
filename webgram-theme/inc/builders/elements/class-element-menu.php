<?php
/**
 * Elements: primary menu, secondary menu (red bar), vertical categories menu.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

class Webgram_Element_Menu extends Webgram_Element {

	protected string $location = 'primary';

	public function id(): string {
		return 'menu_primary';
	}

	public function label(): string {
		return __( 'Primary menu', 'webgram' );
	}

	public function icon(): string {
		return 'menu';
	}

	public function group(): string {
		return 'navigation';
	}

	public function settings_fields(): array {
		return [
			'menu'      => [ 'label' => __( 'Menu', 'webgram' ), 'type' => 'menu', 'default' => 0, 'description' => sprintf( /* translators: %s: placeholder value. */ __( 'Leave empty to use the "%s" menu location.', 'webgram' ), $this->location ) ],
			'mega'      => [ 'label' => __( 'Mega menu panels', 'webgram' ), 'type' => 'switch', 'default' => true, 'description' => __( 'Configure panels per item under Appearance > Menus.', 'webgram' ) ],
			'align'     => [ 'label' => __( 'Alignment', 'webgram' ), 'type' => 'radio', 'choices' => [ 'start' => __( 'Start', 'webgram' ), 'center' => __( 'Center', 'webgram' ), 'end' => __( 'End', 'webgram' ) ], 'default' => 'center' ],
			'gap'       => [ 'label' => __( 'Gap between items', 'webgram' ), 'type' => 'number', 'min' => 8, 'max' => 64, 'unit' => 'px', 'default' => 32 ],
			'uppercase' => [ 'label' => __( 'Uppercase', 'webgram' ), 'type' => 'switch', 'default' => false ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$args = [
			'theme_location' => $this->location,
			'container'      => false,
			'menu_class'     => 'wg-nav wg-nav--' . $this->location . ' wg-nav--' . $settings['align'] . ( $settings['uppercase'] ? ' wg-nav--uppercase' : '' ),
			'fallback_cb'    => false,
			'depth'          => 4,
			'menu_id'        => 'wg-nav-' . $this->location . '-' . $device,
		];
		if ( (int) $settings['menu'] > 0 ) {
			$args['menu'] = (int) $settings['menu'];
			unset( $args['theme_location'] );
		} elseif ( ! has_nav_menu( $this->location ) ) {
			return;
		}
		printf( '<nav class="wg-menu wg-menu--%s" aria-label="%s" style="--wg-nav-gap:%dpx" data-wg-component="menu">', esc_attr( $this->location ), esc_attr( $this->label() ), (int) $settings['gap'] );
		Webgram_Mega_Menu_Frontend::menu( $args, ! empty( $settings['mega'] ) );
		echo '</nav>';
	}
}

final class Webgram_Element_Menu_Secondary extends Webgram_Element_Menu {

	protected string $location = 'secondary';

	public function id(): string {
		return 'menu_secondary';
	}

	public function label(): string {
		return __( 'Secondary menu', 'webgram' );
	}
}

final class Webgram_Element_Menu_Vertical extends Webgram_Element {

	public function id(): string {
		return 'menu_vertical';
	}

	public function label(): string {
		return __( 'Categories dropdown', 'webgram' );
	}

	public function icon(): string {
		return 'grid';
	}

	public function group(): string {
		return 'navigation';
	}

	public function settings_fields(): array {
		return [
			'label' => [ 'label' => __( 'Button label', 'webgram' ), 'type' => 'text', 'default' => __( 'All categories', 'webgram' ) ],
			'menu'  => [ 'label' => __( 'Menu', 'webgram' ), 'type' => 'menu', 'default' => 0, 'description' => __( 'Leave empty to list top-level product categories.', 'webgram' ) ],
			'icon'  => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => 'grid' ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$id = 'wg-vertical-' . $device;
		printf(
			'<div class="wg-vmenu" data-wg-component="vmenu"><button class="wg-vmenu__toggle" type="button" aria-expanded="false" aria-controls="%s">%s<span>%s</span>%s</button><div class="wg-vmenu__panel" id="%s" hidden>',
			esc_attr( $id ),
			webgram_icon( (string) $settings['icon'], '', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( (string) $settings['label'] ),
			webgram_icon( 'chevron-down', 'wg-vmenu__chevron', false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_attr( $id )
		);
		if ( (int) $settings['menu'] > 0 ) {
			wp_nav_menu( [ 'menu' => (int) $settings['menu'], 'container' => false, 'menu_class' => 'wg-vmenu__list', 'fallback_cb' => false, 'depth' => 2 ] );
		} elseif ( taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( [ 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 20 ] );
			if ( ! is_wp_error( $terms ) && $terms ) {
				echo '<ul class="wg-vmenu__list">';
				foreach ( $terms as $term ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( (string) get_term_link( $term ) ), esc_html( $term->name ) );
				}
				echo '</ul>';
			}
		}
		echo '</div></div>';
	}
}
