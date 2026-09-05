<?php
/**
 * Wishlist toggle button. $args: product, active, variant (card|product|gallery), icon.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_labels = [ 'add' => __( 'Add to wishlist', 'webgram-core' ), 'remove' => __( 'Remove from wishlist', 'webgram-core' ) ];
$wgc_extra  = 'card' === $args['variant'] ? 'wg-card__action' : ( 'product' === $args['variant'] ? 'wg-btn wg-btn--outline wg-btn--icon' : 'wg-gallery__action' );
?>
<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'wishlist-btn', $wgc_extra . ( $args['active'] ? ' is-active' : '' ) ) ); ?>" data-wgc-wishlist="<?php echo (int) $args['product']->get_id(); ?>" aria-pressed="<?php echo $args['active'] ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $args['active'] ? $wgc_labels['remove'] : $wgc_labels['add'] ); ?>" title="<?php echo esc_attr( $args['active'] ? $wgc_labels['remove'] : $wgc_labels['add'] ); ?>">
	<?php echo $args['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG. ?>
	<?php if ( 'product' === $args['variant'] ) : ?>
		<span class="wgc-wishlist-btn__text"><?php echo esc_html( $args['active'] ? __( 'Saved', 'webgram-core' ) : __( 'Wishlist', 'webgram-core' ) ); ?></span>
	<?php endif; ?>
</button>
