<?php
/**
 * Coupons row. $args: coupons, a, heading.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'coupons-row' ) ); ?>" style="--wgc-cols:<?php echo (int) $args['a']['columns']; ?>">
	<?php webgram_core()->view( 'sections/heading', $args['heading'] ); ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'coupons-row__grid' ) ); ?>">
		<?php foreach ( $args['coupons'] as $wgc_c ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-card' ) ); ?>">
				<div class="<?php echo esc_attr( Helpers::css_class( 'coupon-card__body' ) ); ?>">
					<strong class="<?php echo esc_attr( Helpers::css_class( 'coupon-card__headline' ) ); ?>"><?php echo esc_html( $wgc_c['headline'] ); ?></strong>
					<?php if ( $wgc_c['min'] > 0 ) : ?>
						<span class="<?php echo esc_attr( Helpers::css_class( 'coupon-card__meta' ) ); ?>"><?php echo wp_kses_post( sprintf( /* translators: %s: amount */ __( 'On orders above %s', 'webgram-core' ), wc_price( $wgc_c['min'] ) ) ); ?></span>
					<?php endif; ?>
					<?php if ( $wgc_c['expires'] ) : ?>
						<span class="<?php echo esc_attr( Helpers::css_class( 'coupon-card__meta' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: date */ __( 'Valid till %s', 'webgram-core' ), $wgc_c['expires'] ) ); ?></span>
					<?php endif; ?>
				</div>
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'coupon-card__code' ) ); ?>" data-wgc-copy="<?php echo esc_attr( $wgc_c['code'] ); ?>" data-copied="<?php esc_attr_e( 'Code copied', 'webgram-core' ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: code */ __( 'Copy code %s', 'webgram-core' ), strtoupper( $wgc_c['code'] ) ) ); ?>">
					<span><?php echo esc_html( strtoupper( $wgc_c['code'] ) ); ?></span>
					<small><?php esc_html_e( 'Copy', 'webgram-core' ); ?></small>
				</button>
			</div>
		<?php endforeach; ?>
	</div>
</section>
