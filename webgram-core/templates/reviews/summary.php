<?php
/**
 * Rating summary panel (spec 4.7). $args: summary {average,total,rows}, product, show_button (bool).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_s = $args['summary'];
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__summary' ) ); ?>">
	<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__average' ) ); ?>">
		<strong class="<?php echo esc_attr( Helpers::css_class( 'reviews__score' ) ); ?>"><?php echo esc_html( number_format_i18n( (float) $wgc_s['average'], 1 ) ); ?></strong>
		<?php webgram_core()->view( 'reviews/stars', [ 'rating' => (float) $wgc_s['average'], 'size' => 22 ] ); ?>
		<span class="<?php echo esc_attr( Helpers::css_class( 'reviews__outof' ) ); ?>"><?php esc_html_e( 'out of 5', 'webgram-core' ); ?></span>
		<span class="<?php echo esc_attr( Helpers::css_class( 'reviews__total' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: count */ _n( '(%s Review)', '(%s Reviews)', (int) $wgc_s['total'], 'webgram-core' ), number_format_i18n( (int) $wgc_s['total'] ) ) ); ?></span>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__bars' ) ); ?>">
		<?php foreach ( $wgc_s['rows'] as $wgc_row ) : ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reviews__bar' ) ); ?>" data-wgc-reviews-filter="stars" data-value="<?php echo (int) $wgc_row['stars']; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: stars, 2: count */ __( 'Show %1$d star reviews (%2$d)', 'webgram-core' ), (int) $wgc_row['stars'], (int) $wgc_row['count'] ) ); ?>">
				<span class="<?php echo esc_attr( Helpers::css_class( 'reviews__bar-label' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: stars */ __( '%d Star', 'webgram-core' ), (int) $wgc_row['stars'] ) ); ?></span>
				<span class="<?php echo esc_attr( Helpers::css_class( 'reviews__bar-track' ) ); ?>"><span class="<?php echo esc_attr( Helpers::css_class( 'reviews__bar-fill' ) ); ?>" style="width:<?php echo (int) $wgc_row['percent']; ?>%"></span></span>
				<span class="<?php echo esc_attr( Helpers::css_class( 'reviews__bar-count' ) ); ?>"><?php echo esc_html( number_format_i18n( (int) $wgc_row['count'] ) ); ?></span>
			</button>
		<?php endforeach; ?>
	</div>
	<?php if ( ! empty( $args['show_button'] ) ) : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__write' ) ); ?>">
			<button type="button" class="wg-btn wg-btn--secondary" data-wgc-reviews-write><?php esc_html_e( 'Write a review', 'webgram-core' ); ?></button>
		</div>
	<?php endif; ?>
</div>
