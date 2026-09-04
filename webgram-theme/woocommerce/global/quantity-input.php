<?php
/**
 * Quantity stepper: minus, value, plus (44px). Keeps the standard input name and classes so WooCommerce and
 * third-party scripts keep working.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'webgram' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'webgram' );

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" class="qty" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $min_value ); ?>">
	</div>
	<?php
} else {
	?>
	<div class="quantity wg-qty" data-wg-component="qty">
		<?php do_action( 'woocommerce_before_quantity_input_field' ); ?>
		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $label ); ?></label>
		<button type="button" class="wg-qty__btn wg-qty__btn--minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'webgram' ); ?>"><?php webgram_icon( 'minus' ); ?></button>
		<input
			type="number"
			id="<?php echo esc_attr( $input_id ); ?>"
			class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
			name="<?php echo esc_attr( $input_name ); ?>"
			value="<?php echo esc_attr( $input_value ); ?>"
			aria-label="<?php esc_attr_e( 'Product quantity', 'webgram' ); ?>"
			<?php echo $min_value ? 'min="' . esc_attr( $min_value ) . '"' : ''; ?>
			<?php echo $max_value ? 'max="' . esc_attr( $max_value ) . '"' : ''; ?>
			<?php if ( ! empty( $step ) ) : ?>step="<?php echo esc_attr( $step ); ?>"<?php endif; ?>
			placeholder="<?php echo esc_attr( $placeholder ?? '' ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ?? 'numeric' ); ?>"
			autocomplete="off">
		<button type="button" class="wg-qty__btn wg-qty__btn--plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'webgram' ); ?>"><?php webgram_icon( 'plus' ); ?></button>
		<?php do_action( 'woocommerce_after_quantity_input_field' ); ?>
	</div>
	<?php
}
