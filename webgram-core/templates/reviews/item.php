<?php
/**
 * One review (spec 4.7). $args: review (see Module::item_data).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_r = $args['review'];
?>
<article class="<?php echo esc_attr( Helpers::css_class( 'review' ) ); ?>" id="wgc-review-<?php echo (int) $wgc_r['id']; ?>" data-wgc-review="<?php echo (int) $wgc_r['id']; ?>">
	<div class="<?php echo esc_attr( Helpers::css_class( 'review__avatar-wrap' ) ); ?>"><?php echo $wgc_r['avatar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar output. ?></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'review__body' ) ); ?>">
		<header class="<?php echo esc_attr( Helpers::css_class( 'review__head' ) ); ?>">
			<div class="<?php echo esc_attr( Helpers::css_class( 'review__who' ) ); ?>">
				<strong class="<?php echo esc_attr( Helpers::css_class( 'review__author' ) ); ?>"><?php echo esc_html( $wgc_r['author'] ); ?></strong>
				<?php if ( $wgc_r['verified'] ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'review__verified' ) ); ?>"><?php esc_html_e( 'Verified Buyer', 'webgram-core' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $wgc_r['rating'] > 0 ) : ?>
				<div class="<?php echo esc_attr( Helpers::css_class( 'review__rating' ) ); ?>">
					<?php webgram_core()->view( 'reviews/stars', [ 'rating' => (float) $wgc_r['rating'], 'size' => 16 ] ); ?>
					<span><?php echo esc_html( number_format_i18n( (float) $wgc_r['rating'], 1 ) ); ?></span>
				</div>
			<?php endif; ?>
		</header>
		<?php if ( '' !== $wgc_r['title'] ) : ?>
			<h4 class="<?php echo esc_attr( Helpers::css_class( 'review__title' ) ); ?>"><?php echo esc_html( $wgc_r['title'] ); ?></h4>
		<?php endif; ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'review__text' ) ); ?>"><?php echo wp_kses_post( $wgc_r['body'] ); ?></div>
		<?php webgram_core()->view( 'reviews/item-extras', [ 'review' => $wgc_r ] ); ?>
		<time class="<?php echo esc_attr( Helpers::css_class( 'review__date' ) ); ?>" datetime="<?php echo esc_attr( $wgc_r['datetime'] ); ?>" title="<?php echo esc_attr( $wgc_r['date'] ); ?>"><?php echo esc_html( $wgc_r['relative'] ); ?></time>
	</div>
</article>
