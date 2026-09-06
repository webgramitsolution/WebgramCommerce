<?php
/**
 * Instagram feed (spec 4.3 item 11). $args: items, title, columns, layout, show_caption, follow_url, follow_text.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_ig_icon = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>';
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'instagram', 'wgc-instagram--' . $args['layout'] ) ); ?>" style="--wgc-ig-columns:<?php echo (int) $args['columns']; ?>">
	<?php if ( $args['title'] || $args['follow_url'] ) : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'section-heading', 'wg-section-heading--between' ) ); ?>">
			<?php if ( $args['title'] ) : ?>
				<span class="wg-section-heading__ornament" aria-hidden="true"></span>
				<h2 class="<?php echo esc_attr( Helpers::css_class( 'section-heading__title' ) ); ?>"><?php echo esc_html( $args['title'] ); ?></h2>
				<span class="wg-section-heading__ornament wg-section-heading__ornament--end" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( $args['follow_url'] ) : ?>
				<a class="wg-btn wg-btn--outline wg-btn--pill <?php echo esc_attr( Helpers::css_class( 'instagram__follow', 'wg-section-heading__link' ) ); ?>" href="<?php echo esc_url( $args['follow_url'] ); ?>" target="_blank" rel="noopener"><?php echo $wgc_ig_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $args['follow_text'] ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'instagram__grid' ) ); ?>" <?php echo 'slider' === $args['layout'] ? 'data-wg-component="carousel" data-wg-carousel="snap"' : ''; ?>>
		<?php foreach ( $args['items'] as $wgc_item ) : ?>
			<a class="<?php echo esc_attr( Helpers::css_class( 'instagram__tile', 'video' === $wgc_item['type'] ? 'is-video' : '' ) ); ?>" href="<?php echo esc_url( $wgc_item['url'] ?: '#' ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $wgc_item['caption'] ? wp_trim_words( $wgc_item['caption'], 12 ) : __( 'View on Instagram', 'webgram-core' ) ); ?>">
				<img src="<?php echo esc_url( $wgc_item['image'] ); ?>" alt="" loading="lazy" decoding="async" width="400" height="400">
				<span class="<?php echo esc_attr( Helpers::css_class( 'instagram__hover' ) ); ?>" aria-hidden="true">
					<?php echo $wgc_ig_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $args['show_caption'] && $wgc_item['caption'] ) : ?>
						<span class="<?php echo esc_attr( Helpers::css_class( 'instagram__caption' ) ); ?>"><?php echo esc_html( wp_trim_words( $wgc_item['caption'], 14 ) ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
