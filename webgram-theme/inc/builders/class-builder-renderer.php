<?php
/**
 * Renders header and footer layouts. Rows print as wg-header__row--{name}, areas as wg-header__col--{area}, every
 * element wrapped in wg-header__el wg-header__el--{id}. Desktop and mobile layouts render separately and CSS shows
 * one of them per breakpoint. Consecutive sticky rows are wrapped in one sticky group.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Builder_Renderer {

	public static function render_header(): void {
		$builder = Webgram_Header_Builder::instance();
		$layout  = $builder->layout();
		$sticky  = webgram_option( 'sticky_enabled' ) ? array_map( 'strval', (array) webgram_option( 'sticky_rows' ) ) : [];

		foreach ( Webgram_Header_Builder::DEVICES as $device ) {
			$rows = [];
			foreach ( Webgram_Header_Builder::ROWS as $row ) {
				$data = $layout[ $device ][ $row ];
				if ( empty( $data['settings']['enabled'] ) || ! self::row_has_content( $data, $builder ) ) {
					continue;
				}
				$rows[ $row ] = $data;
			}
			if ( ! $rows ) {
				continue;
			}
			$device_sticky = ( 'mobile' === $device && ! webgram_option( 'sticky_mobile' ) ) ? [] : $sticky;

			printf( '<div class="wg-header__device wg-header__device--%s">', esc_attr( $device ) );
			$open = false;
			foreach ( $rows as $row => $data ) {
				$is_sticky = in_array( $row, $device_sticky, true );
				if ( $is_sticky && ! $open ) {
					echo '<div class="wg-header__sticky" data-wg-component="sticky">';
					$open = true;
				} elseif ( ! $is_sticky && $open ) {
					echo '</div>';
					$open = false;
				}
				self::header_row( $row, $data, $device, $builder, $is_sticky );
			}
			if ( $open ) {
				echo '</div>';
			}
			echo '</div>';
		}
	}

	private static function row_has_content( array $data, Webgram_Header_Builder $builder ): bool {
		foreach ( Webgram_Header_Builder::AREAS as $area ) {
			foreach ( (array) ( $data[ $area ] ?? [] ) as $id ) {
				$el = $builder->element( $id );
				if ( $el && $el->is_available() ) {
					return true;
				}
			}
		}
		return false;
	}

	private static function header_row( string $row, array $data, string $device, Webgram_Header_Builder $builder, bool $sticky ): void {
		$s       = $data['settings'];
		$classes = [ 'wg-header__row', 'wg-header__row--' . $row ];
		if ( $sticky ) {
			$classes[] = 'wg-header__row--sticky';
		}
		if ( ! empty( $s['border'] ) ) {
			$classes[] = 'has-border';
		}
		if ( ! empty( $s['shadow'] ) ) {
			$classes[] = 'has-shadow';
		}
		$style = sprintf( '--wg-row-height:%dpx;', (int) $s['height'] );
		if ( ! empty( $s['bg'] ) ) {
			$style .= '--wg-row-bg:' . $s['bg'] . ';';
		}
		if ( ! empty( $s['color'] ) ) {
			$style .= '--wg-row-color:' . $s['color'] . ';';
		}
		$style = (string) apply_filters( 'webgram/header/row_style', $style, $row, $device, $s );

		printf(
			'<div class="%s" data-row="%s" style="%s"><div class="%s"><div class="wg-header__cols">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $row ),
			esc_attr( $style ),
			'full' === ( $s['container'] ?? 'boxed' ) ? 'wg-container wg-container--fluid' : 'wg-container'
		);
		foreach ( Webgram_Header_Builder::AREAS as $area ) {
			$ids = (array) ( $data[ $area ] ?? [] );
			printf( '<div class="wg-header__col wg-header__col--%s">', esc_attr( $area ) );
			foreach ( $ids as $id ) {
				self::element( $builder, $id, $device, 'header' );
			}
			echo '</div>';
		}
		echo '</div></div></div>';
	}

	/** @param Webgram_Header_Builder|Webgram_Footer_Builder $builder */
	public static function element( object $builder, string $id, string $device, string $context ): void {
		$el = $builder->element( $id );
		if ( ! $el || ! $el->is_available() ) {
			return;
		}
		$settings = $builder->element_settings( $id );
		$settings = (array) apply_filters( 'webgram/' . $context . '/element_settings', $settings, $id, $device );
		$classes  = array_map( 'sanitize_html_class', $el->classes( $settings, $context ) );

		ob_start();
		$el->render( $settings, $device, $context );
		$html = trim( (string) ob_get_clean() );
		if ( '' === $html ) {
			return;
		}
		printf( '<div class="%s" data-element="%s">%s</div>', esc_attr( implode( ' ', $classes ) ), esc_attr( $id ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- elements escape their output.
	}

	public static function render_footer(): void {
		$builder = Webgram_Footer_Builder::instance();
		$layout  = $builder->layout();

		$w = $layout['widgets'];
		if ( ! empty( $w['enabled'] ) ) {
			$columns = (int) $w['columns'];
			$has     = false;
			for ( $i = 1; $i <= $columns; $i++ ) {
				if ( ! empty( $w['areas'][ 'col_' . $i ] ) ) {
					$has = true;
					break;
				}
			}
			if ( $has ) {
				$style = sprintf( '--wg-footer-padding:%dpx', (int) ( $w['settings']['padding'] ?? 64 ) );
				printf(
					'<div class="wg-footer__widgets%s%s" style="%s"><div class="wg-container"><div class="wg-footer__grid wg-footer__grid--%d">',
					! empty( $w['settings']['first_wide'] ) ? ' wg-footer__widgets--first-wide' : '',
					'center' === ( $w['settings']['align'] ?? 'start' ) ? ' wg-footer__widgets--center' : '',
					esc_attr( $style ),
					(int) $columns
				);
				for ( $i = 1; $i <= $columns; $i++ ) {
					echo '<div class="wg-footer__col">';
					foreach ( (array) ( $w['areas'][ 'col_' . $i ] ?? [] ) as $id ) {
						self::element( $builder, $id, 'desktop', 'footer' );
					}
					echo '</div>';
				}
				echo '</div></div></div>';
			}
		}

		$b = $layout['bottom'];
		if ( ! empty( $b['enabled'] ) && webgram_option( 'footer_bottom_show' ) ) {
			$style = ! empty( $b['settings']['bg'] ) ? 'background:' . $b['settings']['bg'] : '';
			printf( '<div class="wg-footer__bottom%s" style="%s"><div class="wg-container wg-footer__bottom-inner">', ! empty( $b['settings']['border'] ) ? ' has-border' : '', esc_attr( $style ) );
			foreach ( Webgram_Footer_Builder::BOTTOM_AREAS as $area ) {
				printf( '<div class="wg-footer__area wg-footer__area--%s">', esc_attr( $area ) );
				foreach ( (array) ( $b[ $area ] ?? [] ) as $id ) {
					self::element( $builder, $id, 'desktop', 'footer' );
				}
				echo '</div>';
			}
			echo '</div></div>';
		}
	}
}
