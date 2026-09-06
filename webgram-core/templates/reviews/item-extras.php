<?php
/**
 * Media thumbnails, recommendation and helpful vote for one review. $args: review (see Module::item_data).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_r = $args['review'];
?>
<?php if ( '' !== (string) $wgc_r['recommend'] ) : ?>
	<p class="<?php echo esc_attr( Helpers::css_class( 'review__recommend', $wgc_r['recommend'] ? 'is-yes' : 'is-no' ) ); ?>">
		<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $wgc_r['recommend'] ? '<polyline points="20 6 9 17 4 12"/>' : '<path d="M18 6L6 18M6 6l12 12"/>'; ?></svg>
		<?php echo esc_html( $wgc_r['recommend'] ? __( 'Recommends this product', 'webgram-core' ) : __( 'Does not recommend this product', 'webgram-core' ) ); ?>
	</p>
<?php endif; ?>
<?php if ( ! empty( $wgc_r['media'] ) ) : ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'review__media' ) ); ?>">
		<?php foreach ( $wgc_r['media'] as $wgc_m ) : ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'review__thumb', 'video' === $wgc_m['type'] ? 'is-video' : '' ) ); ?>" data-wgc-lightbox="<?php echo esc_url( $wgc_m['url'] ); ?>" data-type="<?php echo esc_attr( $wgc_m['type'] ); ?>" aria-label="<?php echo esc_attr( 'video' === $wgc_m['type'] ? __( 'Play review video', 'webgram-core' ) : __( 'Open review photo', 'webgram-core' ) ); ?>">
				<?php if ( 'video' === $wgc_m['type'] ) : ?>
					<video src="<?php echo esc_url( $wgc_m['url'] ); ?>" muted playsinline preload="metadata"></video>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
				<?php else : ?>
					<img src="<?php echo esc_url( $wgc_m['thumb'] ); ?>" alt="" loading="lazy" width="64" height="64">
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
<?php if ( ! empty( $wgc_r['show_helpful'] ) ) : ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'review__helpful' ) ); ?>">
		<span><?php esc_html_e( 'Was this helpful?', 'webgram-core' ); ?></span>
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'review__vote', $wgc_r['voted'] ? 'is-voted' : '' ) ); ?>" data-wgc-helpful="<?php echo (int) $wgc_r['id']; ?>" <?php disabled( (bool) $wgc_r['voted'] ); ?> aria-label="<?php esc_attr_e( 'Mark as helpful', 'webgram-core' ); ?>">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
			<span data-wgc-helpful-count><?php echo esc_html( number_format_i18n( (int) $wgc_r['helpful'] ) ); ?></span>
		</button>
	</div>
<?php endif; ?>
