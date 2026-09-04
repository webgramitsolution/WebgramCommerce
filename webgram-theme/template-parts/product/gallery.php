<?php
/**
 * Product gallery: main image (zoom, arrows, badge, wishlist slot), thumbnail strip (horizontal or vertical),
 * auto slide, video slide (meta _webgram_video_url set by Core), lightbox. $args: product.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
$ids     = array_values( array_filter( array_merge( [ (int) $product->get_image_id() ], array_map( 'intval', (array) $product->get_gallery_image_ids() ) ) ) );
$video   = (string) get_post_meta( $product->get_id(), '_webgram_video_url', true );
$save    = Webgram_WC_Product_Card::savings( $product );
$size    = (string) apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
?>
<div class="wg-gallery woocommerce-product-gallery" data-wg-component="gallery" data-thumbs="<?php echo (int) webgram_option( 'product_thumbs_visible' ); ?>" style="--wg-thumbs-visible:<?php echo (int) webgram_option( 'product_thumbs_visible' ); ?>">
	<div class="wg-gallery__main">
		<div class="wg-gallery__badges">
			<?php if ( $save ) : ?>
				<span class="wg-badge wg-badge--sale wg-badge--wave"><?php printf( esc_html__( 'Save %s', 'webgram' ), wp_kses_post( $save['amount'] ) ); ?></span>
			<?php endif; ?>
			<?php do_action( 'webgram/product/gallery_badges', $product ); ?>
		</div>
		<div class="wg-gallery__actions"><?php do_action( 'webgram/product/gallery_actions', $product ); ?></div>
		<div class="wg-gallery__track" data-wg-gallery-track>
			<?php if ( ! $ids ) : ?>
				<div class="wg-gallery__slide is-active"><?php echo wc_placeholder_img( $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<?php foreach ( $ids as $webgram_i => $webgram_id ) : ?>
				<?php $webgram_full = wp_get_attachment_image_url( $webgram_id, 'full' ); ?>
				<div class="wg-gallery__slide<?php echo 0 === $webgram_i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $webgram_i; ?>" data-full="<?php echo esc_url( (string) $webgram_full ); ?>">
					<a href="<?php echo esc_url( (string) $webgram_full ); ?>" class="wg-gallery__zoom" data-wg-lightbox aria-label="<?php esc_attr_e( 'Open image in full size', 'webgram' ); ?>">
						<?php echo wp_get_attachment_image( $webgram_id, $size, false, [ 'class' => 'wg-gallery__img', 'loading' => 0 === $webgram_i ? 'eager' : 'lazy', 'fetchpriority' => 0 === $webgram_i ? 'high' : 'auto', 'data-zoom' => (string) $webgram_full ] ); ?>
					</a>
				</div>
			<?php endforeach; ?>
			<?php if ( $video ) : ?>
				<div class="wg-gallery__slide wg-gallery__slide--video" data-index="<?php echo count( $ids ); ?>" data-video="<?php echo esc_url( $video ); ?>">
					<?php if ( preg_match( '/(youtube\.com|youtu\.be|vimeo\.com)/', $video ) ) : ?>
						<div class="wg-gallery__embed" data-wg-embed="<?php echo esc_url( $video ); ?>"></div>
					<?php else : ?>
						<video class="wg-gallery__video" src="<?php echo esc_url( $video ); ?>" controls playsinline preload="none"></video>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( count( $ids ) + ( $video ? 1 : 0 ) > 1 ) : ?>
			<button type="button" class="wg-gallery__arrow wg-gallery__arrow--prev" data-wg-gallery-prev aria-label="<?php esc_attr_e( 'Previous image', 'webgram' ); ?>"><?php webgram_icon( 'chevron-left' ); ?></button>
			<button type="button" class="wg-gallery__arrow wg-gallery__arrow--next" data-wg-gallery-next aria-label="<?php esc_attr_e( 'Next image', 'webgram' ); ?>"><?php webgram_icon( 'chevron-right' ); ?></button>
		<?php endif; ?>
	</div>
	<?php if ( count( $ids ) + ( $video ? 1 : 0 ) > 1 ) : ?>
		<div class="wg-gallery__thumbs-wrap">
			<button type="button" class="wg-gallery__thumbs-arrow wg-gallery__thumbs-arrow--prev" data-wg-thumbs-prev aria-label="<?php esc_attr_e( 'Scroll thumbnails back', 'webgram' ); ?>"><?php webgram_icon( 'chevron-left' ); ?></button>
			<div class="wg-gallery__thumbs" data-wg-gallery-thumbs role="tablist">
				<?php foreach ( $ids as $webgram_i => $webgram_id ) : ?>
					<button type="button" class="wg-gallery__thumb<?php echo 0 === $webgram_i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $webgram_i; ?>" role="tab" aria-selected="<?php echo 0 === $webgram_i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Image %d', 'webgram' ), $webgram_i + 1 ) ); ?>">
						<?php echo wp_get_attachment_image( $webgram_id, 'webgram-thumb', false, [ 'loading' => 'lazy' ] ); ?>
					</button>
				<?php endforeach; ?>
				<?php if ( $video ) : ?>
					<button type="button" class="wg-gallery__thumb wg-gallery__thumb--video" data-index="<?php echo count( $ids ); ?>" role="tab" aria-selected="false" aria-label="<?php esc_attr_e( 'Product video', 'webgram' ); ?>">
						<?php echo $ids ? wp_get_attachment_image( $ids[0], 'webgram-thumb', false, [ 'loading' => 'lazy' ] ) : ''; ?>
						<span class="wg-gallery__play"><?php webgram_icon( 'play' ); ?></span>
					</button>
				<?php endif; ?>
			</div>
			<button type="button" class="wg-gallery__thumbs-arrow wg-gallery__thumbs-arrow--next" data-wg-thumbs-next aria-label="<?php esc_attr_e( 'Scroll thumbnails forward', 'webgram' ); ?>"><?php webgram_icon( 'chevron-right' ); ?></button>
		</div>
	<?php endif; ?>
	<div class="wg-gallery__dots" aria-hidden="true"></div>
</div>
