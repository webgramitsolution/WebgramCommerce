<?php
/**
 * Compare toggle button. $args: product, active, variant (card|product), icon.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_labels = [ 'add' => __( 'Add to compare', 'webgram-core' ), 'remove' => __( 'Remove from compare', 'webgram-core' ) ];
$wgc_extra  = 'card' === $args['variant'] ? 'wg-card__action' : 'wg-btn wg-btn--outline wg-btn--icon';
?>
<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'compare-btn', $wgc_extra . ( $args['active'] ? ' is-active' : '' ) ) ); ?>" data-wgc-compare="<?php echo (int) $args['product']->get_id(); ?>" aria-pressed="<?php echo $args['active'] ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $args['active'] ? $wgc_labels['remove'] : $wgc_labels['add'] ); ?>" title="<?php echo esc_attr( $args['active'] ? $wgc_labels['remove'] : $wgc_labels['add'] ); ?>">
	<?php echo $args['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG. ?>
	<?php if ( 'product' === $args['variant'] ) : ?>
		<span class="wgc-compare-btn__text"><?php echo esc_html( $args['active'] ? __( 'Comparing', 'webgram-core' ) : __( 'Compare', 'webgram-core' ) ); ?></span>
	<?php endif; ?>
</button>
