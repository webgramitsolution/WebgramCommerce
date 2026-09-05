<?php
/**
 * Testimonials (spec 4.3 item 10): dark band, 3-card slider, photo left, product name, text, name and label,
 * 5 gold stars, dots. $args: items, a, heading.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_a = $args['a'];
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'testimonials', 'wgc-testimonials--' . $wgc_a['style'] ) ); ?>" style="--wgc-cols:<?php echo (int) $wgc_a['columns']; ?>">
	<?php webgram_core()->view( 'sections/heading', $args['heading'] ); ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'testimonials__row' ) ); ?>" data-wg-component="carousel" data-wg-carousel="dots">
		<?php foreach ( $args['items'] as $wgc_t ) : ?>
			<article class="<?php echo esc_attr( Helpers::css_class( 'testimonial' ) ); ?>">
				<?php if ( $wgc_t['photo'] ) : ?>
					<div class="<?php echo esc_attr( Helpers::css_class( 'testimonial__photo' ) ); ?>"><?php echo $wgc_t['photo']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>
				<div class="<?php echo esc_attr( Helpers::css_class( 'testimonial__body' ) ); ?>">
					<?php if ( $wgc_t['product_name'] ) : ?>
						<a class="<?php echo esc_attr( Helpers::css_class( 'testimonial__product' ) ); ?>" href="<?php echo esc_url( $wgc_t['product_url'] ); ?>"><?php echo esc_html( $wgc_t['product_name'] ); ?></a>
					<?php endif; ?>
					<div class="<?php echo esc_attr( Helpers::css_class( 'testimonial__text' ) ); ?>"><?php echo wp_kses_post( $wgc_t['text'] ); ?></div>
					<div class="<?php echo esc_attr( Helpers::css_class( 'testimonial__footer' ) ); ?>">
						<span class="<?php echo esc_attr( Helpers::css_class( 'testimonial__name' ) ); ?>"><?php echo esc_html( $wgc_t['name'] ); ?><?php echo $wgc_t['label'] ? '<small>' . esc_html( $wgc_t['label'] ) . '</small>' : ''; ?></span>
						<?php if ( $wgc_a['show_rating'] ) : ?>
							<span class="<?php echo esc_attr( Helpers::css_class( 'testimonial__stars' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: stars */ __( '%d out of 5 stars', 'webgram-core' ), $wgc_t['rating'] ) ); ?>">
								<?php for ( $wgc_i = 1; $wgc_i <= 5; $wgc_i++ ) : ?>
									<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true" class="<?php echo $wgc_i <= $wgc_t['rating'] ? 'is-on' : ''; ?>"><path d="M12 2.5l2.95 6.1 6.7.9-4.9 4.7 1.2 6.7L12 17.7l-5.95 3.2 1.2-6.7-4.9-4.7 6.7-.9z"/></svg>
								<?php endfor; ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
