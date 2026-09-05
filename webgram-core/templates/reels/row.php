<?php
/**
 * Reels row (spec 4.6). $args: items, title, subtitle, layout, columns, show_product, show_mute, autoplay.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'reels', 'wgc-reels--' . $args['layout'] ) ); ?>" style="--wgc-cols:<?php echo (int) $args['columns']; ?>" data-wgc-reels data-autoplay="<?php echo $args['autoplay'] ? '1' : '0'; ?>">
	<?php if ( $args['title'] ) : ?>
		<?php webgram_core()->view( 'sections/heading', [ 'title' => $args['title'], 'subtitle' => $args['subtitle'], 'align' => 'center', 'link_url' => '', 'link_text' => '' ] ); ?>
	<?php endif; ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'reels__row' ) ); ?>" data-wg-component="carousel" data-wg-carousel="<?php echo 'grid' === $args['layout'] ? 'mobile' : 'snap'; ?>">
		<?php foreach ( $args['items'] as $wgc_i => $wgc_reel ) : ?>
			<?php $wgc_first = $wgc_reel['products'][0] ?? null; ?>
			<article class="<?php echo esc_attr( Helpers::css_class( 'reel' ) ); ?>" data-wgc-reel='<?php echo esc_attr( (string) wp_json_encode( $wgc_reel ) ); ?>' data-index="<?php echo (int) $wgc_i; ?>">
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel__media' ) ); ?>" data-wgc-reel-open aria-label="<?php echo esc_attr( sprintf( /* translators: %s: reel title */ __( 'Play reel: %s', 'webgram-core' ), $wgc_reel['title'] ) ); ?>">
					<img class="<?php echo esc_attr( Helpers::css_class( 'reel__poster' ) ); ?>" src="<?php echo esc_url( $wgc_reel['poster'] ); ?>" alt="" loading="lazy" width="360" height="640">
					<?php if ( 'video' === $wgc_reel['embed']['type'] ) : ?>
						<video class="<?php echo esc_attr( Helpers::css_class( 'reel__video' ) ); ?>" src="<?php echo esc_url( $wgc_reel['embed']['src'] ); ?>" poster="<?php echo esc_url( $wgc_reel['poster'] ); ?>" muted loop playsinline preload="none" tabindex="-1"></video>
					<?php endif; ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'reel__play' ) ); ?>" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
				</button>
				<?php if ( $args['show_mute'] && 'video' === $wgc_reel['embed']['type'] ) : ?>
					<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel__mute' ) ); ?>" data-wgc-reel-mute aria-label="<?php esc_attr_e( 'Unmute', 'webgram-core' ); ?>" aria-pressed="false">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path class="wgc-reel__mute-on" d="M23 9l-6 6M17 9l6 6"/><path class="wgc-reel__mute-off" d="M15.54 8.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
					</button>
				<?php endif; ?>
				<?php if ( $args['show_product'] && $wgc_first ) : ?>
					<div class="<?php echo esc_attr( Helpers::css_class( 'reel__product' ) ); ?>">
						<a class="<?php echo esc_attr( Helpers::css_class( 'reel__product-link' ) ); ?>" href="<?php echo esc_url( $wgc_first['url'] ); ?>" data-wgc-reel-product="<?php echo (int) $wgc_first['id']; ?>">
							<img src="<?php echo esc_url( $wgc_first['image'] ); ?>" alt="" loading="lazy" width="56" height="56">
							<span class="<?php echo esc_attr( Helpers::css_class( 'reel__product-text' ) ); ?>">
								<strong><?php echo esc_html( $wgc_first['name'] ); ?></strong>
								<span class="<?php echo esc_attr( Helpers::css_class( 'reel__price' ) ); ?>"><?php echo wp_kses_post( $wgc_first['price_html'] ); ?></span>
							</span>
						</a>
						<?php if ( $wgc_first['add'] ) : ?>
							<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel__add' ) ); ?>" data-wgc-reel-add="<?php echo (int) $wgc_first['id']; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product */ __( 'Add %s to cart', 'webgram-core' ), $wgc_first['name'] ) ); ?>">
								<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><path d="M12 8v6M9 11h6"/></svg>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
