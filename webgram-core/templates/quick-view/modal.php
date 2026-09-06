<?php
/**
 * Quick view content. $args: product, images, show_description, show_meta.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

/** @var WC_Product $product */
$product = $args['product'];
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view' ) ); ?>">
	<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__gallery' ) ); ?>" data-wgc-qv-gallery>
		<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__main' ) ); ?>">
			<?php echo $args['images'] ? wp_get_attachment_image( $args['images'][0], 'woocommerce_single', false, [ 'data-wgc-qv-main' => '1' ] ) : wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( count( $args['images'] ) > 1 ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__thumbs' ) ); ?>">
				<?php foreach ( $args['images'] as $wgc_i => $wgc_id ) : ?>
					<button type="button" class="<?php echo 0 === $wgc_i ? 'is-active' : ''; ?>" data-wgc-qv-thumb="<?php echo esc_url( (string) wp_get_attachment_image_url( $wgc_id, 'woocommerce_single' ) ); ?>"><?php echo wp_get_attachment_image( $wgc_id, 'thumbnail' ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__summary', 'summary entry-summary' ) ); ?>">
		<h2 class="<?php echo esc_attr( Helpers::css_class( 'quick-view__title' ) ); ?>"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h2>
		<?php if ( wc_review_ratings_enabled() && $product->get_average_rating() > 0 ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__rating' ) ); ?>"><?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__price', 'price wg-price' ) ); ?>"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<?php if ( $args['show_description'] && $product->get_short_description() ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__description' ) ); ?>"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
		<?php endif; ?>
		<?php do_action( 'webgram_core/quick_view/before_cart', $product ); ?>
		<?php woocommerce_template_single_add_to_cart(); ?>
		<?php if ( $args['show_meta'] ) : ?>
			<?php woocommerce_template_single_meta(); ?>
		<?php endif; ?>
		<a class="<?php echo esc_attr( Helpers::css_class( 'quick-view__more' ) ); ?>" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'View full details', 'webgram-core' ); ?></a>
	</div>
</div>
