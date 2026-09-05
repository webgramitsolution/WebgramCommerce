<?php
/**
 * Nav menu walker producing dropdowns and mega menu panels (columns, images, badges, icons, descriptions, promo
 * blocks). Markup is keyboard friendly: top-level links with children carry aria-haspopup and aria-expanded.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Mega_Menu_Walker extends Walker_Nav_Menu {

	private array $mega_stack = [];

	public function __construct( private bool $mega_enabled = true ) {}

	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$in_mega = ! empty( $this->mega_stack[0] );
		if ( 0 === $depth && $in_mega ) {
			$m       = $this->mega_stack[0];
			$output .= sprintf( '<div class="wg-mega wg-mega--%s" style="--wg-mega-cols:%d;--wg-mega-width:%dpx"><ul class="wg-mega__cols">', esc_attr( (string) $m['width'] ), (int) $m['columns'], (int) $m['width_px'] );
			return;
		}
		if ( 1 === $depth && $in_mega ) {
			$output .= '<ul class="wg-mega__links">';
			return;
		}
		$output .= '<ul class="wg-nav__sub">';
	}

	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$in_mega = ! empty( $this->mega_stack[0] );
		if ( 0 === $depth && $in_mega ) {
			$output .= '</ul>' . $this->promo_html() . '</div>';
			return;
		}
		$output .= '</ul>';
	}

	private string $promos = '';

	private function promo_html(): string {
		$html         = $this->promos;
		$this->promos = '';
		return $html;
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$meta    = Webgram_Mega_Menu_Admin::get( (int) $item->ID, $depth );
		$has_sub = in_array( 'menu-item-has-children', (array) $item->classes, true );
		$in_mega = ! empty( $this->mega_stack[0] );

		if ( 0 === $depth ) {
			$this->mega_stack = [];
			if ( $this->mega_enabled && ! empty( $meta['mega'] ) && $has_sub ) {
				$this->mega_stack[0] = $meta;
			}
			$in_mega = ! empty( $this->mega_stack[0] );
		}

		$classes = array_filter( (array) $item->classes, static fn( $c ) => '' !== $c && ! str_starts_with( $c, 'current' ) );
		$classes[] = 0 === $depth ? 'wg-nav__item' : ( $in_mega && 1 === $depth ? 'wg-mega__col' : 'wg-nav__subitem' );
		if ( $has_sub ) {
			$classes[] = 'has-children';
		}
		if ( 0 === $depth && $in_mega ) {
			$classes[] = 'wg-nav__item--mega';
		}
		$classes = (array) apply_filters( 'nav_menu_css_class', array_values( $classes ), $item, $args, $depth );
		$output .= '<li class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ) . '">';

		$atts = [
			'href'  => (string) $item->url,
			'class' => 0 === $depth ? 'wg-nav__link' : ( $in_mega && 1 === $depth ? 'wg-mega__heading' . ( 'bold' === ( $meta['heading_style'] ?? '' ) ? ' wg-mega__heading--bold' : '' ) : 'wg-nav__sublink' ),
		];
		if ( ! empty( $item->target ) ) {
			$atts['target'] = $item->target;
		}
		if ( ! empty( $item->attr_title ) ) {
			$atts['title'] = $item->attr_title;
		}
		if ( $has_sub && $depth < 2 ) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
		}
		$atts      = (array) apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
		$attr_html = '';
		foreach ( $atts as $k => $v ) {
			if ( '' !== (string) $v ) {
				$attr_html .= ' ' . esc_attr( $k ) . '="' . ( 'href' === $k ? esc_url( (string) $v ) : esc_attr( (string) $v ) ) . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = (string) apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$inner = '';
		if ( $in_mega && 1 === $depth && ! empty( $meta['image'] ) ) {
			$inner .= wp_get_attachment_image( (int) $meta['image'], 'webgram-card', false, [ 'class' => 'wg-mega__image', 'loading' => 'lazy' ] );
		}
		if ( ! empty( $meta['icon'] ) ) {
			$inner .= webgram_icon( (string) $meta['icon'], 'wg-nav__icon', false );
		}
		if ( empty( $meta['hide_label'] ) && 'hidden' !== ( $meta['heading_style'] ?? '' ) ) {
			$inner .= '<span class="wg-nav__label">' . esc_html( $title ) . '</span>';
		} else {
			$inner .= '<span class="wg-sr-only">' . esc_html( $title ) . '</span>';
		}
		if ( ! empty( $meta['badge_text'] ) ) {
			$color  = Webgram_Settings_Sanitizer::color( (string) ( $meta['badge_color'] ?? '' ) );
			$inner .= '<span class="wg-nav__badge"' . ( $color ? ' style="background:' . esc_attr( $color ) . '"' : '' ) . '>' . esc_html( (string) $meta['badge_text'] ) . '</span>';
		}
		if ( ! empty( $meta['description'] ) ) {
			$inner .= '<span class="wg-nav__desc">' . esc_html( (string) $meta['description'] ) . '</span>';
		}
		if ( $has_sub && 0 === $depth ) {
			$inner .= webgram_icon( 'chevron-down', 'wg-nav__chevron', false );
		}

		$item_output  = (string) ( $args->before ?? '' );
		$item_output .= '<a' . $attr_html . '>' . ( $args->link_before ?? '' ) . $inner . ( $args->link_after ?? '' ) . '</a>';
		if ( $has_sub && $depth < 2 && ! ( $in_mega && 1 === $depth ) ) {
			$item_output .= '<button class="wg-nav__toggle" type="button" aria-label="' . esc_attr( sprintf( /* translators: %s: menu item */ __( 'Open %s submenu', 'webgram' ), wp_strip_all_tags( $title ) ) ) . '" aria-expanded="false" tabindex="-1">' . webgram_icon( 'chevron-down', '', false ) . '</button>';
		}
		$item_output .= (string) ( $args->after ?? '' );

		if ( $in_mega && $depth <= 1 && ! empty( $meta['html_block'] ) ) {
			ob_start();
			webgram_render_block( (int) $meta['html_block'] );
			$this->promos .= '<div class="wg-mega__block">' . (string) ob_get_clean() . '</div>';
		}
		if ( $in_mega && 1 === $depth && ! empty( $meta['promo_image'] ) ) {
			$this->promos .= sprintf(
				'<a class="wg-mega__promo" href="%s">%s%s</a>',
				esc_url( (string) ( $meta['promo_link'] ?: $item->url ) ),
				wp_get_attachment_image( (int) $meta['promo_image'], 'webgram-card', false, [ 'loading' => 'lazy' ] ),
				! empty( $meta['promo_heading'] ) ? '<span class="wg-mega__promo-heading">' . esc_html( (string) $meta['promo_heading'] ) . '</span>' : ''
			);
		}

		$output .= (string) apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= '</li>';
	}
}
