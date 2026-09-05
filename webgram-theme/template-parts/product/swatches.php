<?php
/**
 * Image variant swatches (120px cards with image, name, price and strike). Presentation layer over the standard
 * variations form: clicking a card sets the matching select and fires "change". $args: product.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product_Variable $product */
$product    = $args['product'];
$attributes = $product->get_variation_attributes();
if ( ! $attributes ) {
	return;
}
$variations = $product->get_available_variations( 'objects' );
?>
<div class="wg-swatches" data-wg-component="swatches">
	<?php foreach ( $attributes as $webgram_name => $webgram_values ) : ?>
		<?php
		$webgram_key   = 'attribute_' . sanitize_title( $webgram_name );
		$webgram_label = wc_attribute_label( $webgram_name, $product );
		?>
		<div class="wg-swatches__group" data-attribute="<?php echo esc_attr( $webgram_key ); ?>">
			<div class="wg-swatches__label"><?php echo esc_html( sprintf( /* translators: %s: attribute label */ __( 'Select %s:', 'webgram' ), $webgram_label ) ); ?> <strong data-wg-swatch-current></strong></div>
			<div class="wg-swatches__list" role="radiogroup" aria-label="<?php echo esc_attr( $webgram_label ); ?>">
				<?php foreach ( $webgram_values as $webgram_value ) : ?>
					<?php
					$webgram_term  = taxonomy_exists( $webgram_name ) ? get_term_by( 'slug', $webgram_value, $webgram_name ) : null;
					$webgram_text  = $webgram_term ? $webgram_term->name : $webgram_value;
					$webgram_match = null;
					foreach ( $variations as $webgram_v ) {
						$webgram_attrs = $webgram_v->get_variation_attributes();
						if ( ( $webgram_attrs[ $webgram_key ] ?? null ) === $webgram_value || '' === ( $webgram_attrs[ $webgram_key ] ?? null ) ) {
							$webgram_match = $webgram_v;
							break;
						}
					}
					$webgram_img   = $webgram_match && $webgram_match->get_image_id() ? wp_get_attachment_image( (int) $webgram_match->get_image_id(), 'webgram-thumb', false, [ 'loading' => 'lazy' ] ) : '';
					$webgram_stock = $webgram_match ? $webgram_match->is_in_stock() : true;
					?>
					<button type="button" class="wg-swatch<?php echo $webgram_img ? ' wg-swatch--image' : ''; ?><?php echo $webgram_stock ? '' : ' is-out-of-stock'; ?>" data-value="<?php echo esc_attr( $webgram_value ); ?>" role="radio" aria-checked="false" title="<?php echo esc_attr( $webgram_text ); ?>">
						<?php
						if ( $webgram_img ) :
?>
<span class="wg-swatch__media"><?php echo $webgram_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php endif; ?>
						<span class="wg-swatch__name"><?php echo esc_html( $webgram_text ); ?></span>
						<?php if ( $webgram_match && count( $attributes ) === 1 ) : ?>
							<span class="wg-swatch__price"><?php echo wp_kses_post( $webgram_match->get_price_html() ); ?></span>
						<?php endif; ?>
						<span class="wg-swatch__check" aria-hidden="true"><?php webgram_icon( 'check' ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
