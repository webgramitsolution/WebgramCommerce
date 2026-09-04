<?php
/**
 * Badge list. $args: badges (type, text, color).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

foreach ( $args['badges'] as $wgc_badge ) {
	$wgc_type  = sanitize_html_class( $wgc_badge['type'] );
	$wgc_class = Helpers::css_class( 'badge', 'wgc-badge--' . $wgc_type . ' wg-badge--' . ( 'sale' === $wgc_type ? 'sale' : ( 'new' === $wgc_type ? 'new' : ( 'best' === $wgc_type ? 'accent' : ( 'out' === $wgc_type ? 'outline' : 'success' ) ) ) ) );
	printf( '<span class="%s"%s>%s</span>', esc_attr( $wgc_class ), $wgc_badge['color'] ? ' style="background:' . esc_attr( $wgc_badge['color'] ) . '"' : '', esc_html( $wgc_badge['text'] ) );
}
