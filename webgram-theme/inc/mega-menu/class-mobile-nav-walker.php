<?php
/**
 * Mobile drawer walker: accordion submenus with toggle buttons, mega menu columns flattened to headings.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Mobile_Nav_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '<ul class="wg-nav__sub">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= '</ul>';
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$meta    = Webgram_Mega_Menu_Admin::get( (int) $item->ID, $depth );
		$has_sub = in_array( 'menu-item-has-children', (array) $item->classes, true );
		$classes = array_filter( (array) $item->classes, static fn( $c ) => '' !== $c && ! str_starts_with( $c, 'current' ) );
		if ( $has_sub ) {
			$classes[] = 'has-children';
		}
		$output .= '<li class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ) . '">';
		$title   = (string) apply_filters( 'nav_menu_item_title', apply_filters( 'the_title', $item->title, $item->ID ), $item, $args, $depth );
		$inner   = ! empty( $meta['icon'] ) ? webgram_icon( (string) $meta['icon'], 'wg-nav__icon', false ) : '';
		$inner  .= '<span class="wg-nav__label">' . esc_html( $title ) . '</span>';
		if ( ! empty( $meta['badge_text'] ) ) {
			$color  = Webgram_Settings_Sanitizer::color( (string) ( $meta['badge_color'] ?? '' ) );
			$inner .= '<span class="wg-nav__badge"' . ( $color ? ' style="background:' . esc_attr( $color ) . '"' : '' ) . '>' . esc_html( (string) $meta['badge_text'] ) . '</span>';
		}
		$output .= '<a href="' . esc_url( (string) $item->url ) . '"' . ( ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '' ) . '>' . $inner . '</a>';
		if ( $has_sub ) {
			$output .= '<button class="wg-nav__toggle" type="button" aria-expanded="false" aria-label="' . esc_attr( sprintf( /* translators: %s: menu item */ __( 'Toggle %s submenu', 'webgram' ), wp_strip_all_tags( $title ) ) ) . '">' . webgram_icon( 'chevron-down', '', false ) . '</button>';
		}
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= '</li>';
	}
}
