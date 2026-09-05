<?php
/**
 * Slider container. $args: id, slides, settings, config, style, classes.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Slider\Renderer;
use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( $args['classes'] ); ?>" id="wgc-slider-<?php echo (int) $args['id']; ?>" data-wgc-slider='<?php echo esc_attr( (string) wp_json_encode( $args['config'] ) ); ?>' style="<?php echo esc_attr( $args['style'] ); ?>" aria-roledescription="carousel">
	<div class="swiper-wrapper">
		<?php foreach ( $args['slides'] as $wgc_i => $wgc_slide ) : ?>
			<?php
			$wgc_src   = Renderer::sources( $wgc_slide );
			$wgc_first = 0 === $wgc_i;
			$wgc_text  = [];
			if ( $wgc_slide['text_color'] ) {
				$wgc_text[] = 'color:' . $wgc_slide['text_color'];
			}
			?>
			<div class="swiper-slide <?php echo esc_attr( Helpers::css_class( 'slide', 'wgc-slide--' . $wgc_slide['align'] . ' wgc-slide--v' . $wgc_slide['valign'] . ' wgc-slide--anim-' . $wgc_slide['animation'] ) ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: index, 2: total */ __( 'Slide %1$d of %2$d', 'webgram-core' ), $wgc_i + 1, count( $args['slides'] ) ) ); ?>">
				<?php if ( $wgc_src['desktop'] ) : ?>
					<picture class="<?php echo esc_attr( Helpers::css_class( 'slide__media' ) ); ?>">
						<?php if ( $wgc_src['mobile'] !== $wgc_src['desktop'] ) : ?>
							<source media="(max-width: 767.98px)" srcset="<?php echo esc_url( (string) wp_get_attachment_image_url( $wgc_src['mobile'], 'large' ) ); ?>">
						<?php endif; ?>
						<?php if ( $wgc_src['tablet'] !== $wgc_src['desktop'] ) : ?>
							<source media="(max-width: 991.98px)" srcset="<?php echo esc_url( (string) wp_get_attachment_image_url( $wgc_src['tablet'], 'large' ) ); ?>">
						<?php endif; ?>
						<?php
						echo wp_get_attachment_image(
							$wgc_src['desktop'],
							'full',
							false,
							$wgc_first
								? [ 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async', 'class' => 'wgc-slide__img' ]
								: [ 'loading' => $args['settings']['lazy'] ? 'lazy' : 'eager', 'decoding' => 'async', 'class' => 'wgc-slide__img' ]
						);
						?>
					</picture>
				<?php endif; ?>
				<?php if ( $wgc_slide['overlay_color'] && $wgc_slide['overlay_opacity'] > 0 ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'slide__overlay' ) ); ?>" style="background:<?php echo esc_attr( $wgc_slide['overlay_color'] ); ?>;opacity:<?php echo esc_attr( (string) ( $wgc_slide['overlay_opacity'] / 100 ) ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<?php if ( $wgc_slide['heading'] || $wgc_slide['subheading'] || $wgc_slide['description'] || $wgc_slide['cta_text'] ) : ?>
					<div class="<?php echo esc_attr( Helpers::css_class( 'slide__content' ) ); ?>"<?php echo $wgc_text ? ' style="' . esc_attr( implode( ';', $wgc_text ) ) . '"' : ''; ?>>
						<?php if ( $wgc_slide['subheading'] ) : ?>
							<p class="<?php echo esc_attr( Helpers::css_class( 'slide__subheading' ) ); ?>"><?php echo esc_html( $wgc_slide['subheading'] ); ?></p>
						<?php endif; ?>
						<?php if ( $wgc_slide['heading'] ) : ?>
							<?php $wgc_tag = $wgc_first ? 'h1' : 'h2'; ?>
							<<?php echo esc_attr( $wgc_tag ); ?> class="<?php echo esc_attr( Helpers::css_class( 'slide__heading' ) ); ?>"><?php echo esc_html( $wgc_slide['heading'] ); ?></<?php echo esc_attr( $wgc_tag ); ?>>
						<?php endif; ?>
						<?php if ( $wgc_slide['description'] ) : ?>
							<div class="<?php echo esc_attr( Helpers::css_class( 'slide__text' ) ); ?>"><?php echo wp_kses_post( wpautop( $wgc_slide['description'] ) ); ?></div>
						<?php endif; ?>
						<?php if ( $wgc_slide['benefits'] ) : ?>
							<ul class="<?php echo esc_attr( Helpers::css_class( 'slide__benefits' ) ); ?>">
								<?php foreach ( $wgc_slide['benefits'] as $wgc_b ) : ?>
									<li><?php echo function_exists( 'webgram_icon' ) ? webgram_icon( $wgc_b['icon'], '', false ) : '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $wgc_b['text'] ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( $wgc_slide['cta_text'] || $wgc_slide['cta2_text'] ) : ?>
							<div class="<?php echo esc_attr( Helpers::css_class( 'slide__actions' ) ); ?>">
								<?php if ( $wgc_slide['cta_text'] ) : ?>
									<a class="wg-btn wg-btn--primary <?php echo esc_attr( Helpers::css_class( 'slide__cta' ) ); ?>" href="<?php echo esc_url( $wgc_slide['cta_url'] ?: '#' ); ?>"><?php echo esc_html( $wgc_slide['cta_text'] ); ?></a>
								<?php endif; ?>
								<?php if ( $wgc_slide['cta2_text'] ) : ?>
									<a class="wg-btn wg-btn--outline <?php echo esc_attr( Helpers::css_class( 'slide__cta', 'wgc-slide__cta--secondary' ) ); ?>" href="<?php echo esc_url( $wgc_slide['cta2_url'] ?: '#' ); ?>"><?php echo esc_html( $wgc_slide['cta2_text'] ); ?></a>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( ! empty( $args['config']['pagination'] ) && count( $args['slides'] ) > 1 ) : ?>
		<div class="swiper-pagination"></div>
	<?php endif; ?>
	<?php if ( ! empty( $args['config']['navigation'] ) && count( $args['slides'] ) > 1 ) : ?>
		<button type="button" class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Previous slide', 'webgram-core' ); ?>"></button>
		<button type="button" class="swiper-button-next" aria-label="<?php esc_attr_e( 'Next slide', 'webgram-core' ); ?>"></button>
	<?php endif; ?>
</div>
