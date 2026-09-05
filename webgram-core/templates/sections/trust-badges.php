<?php
/**
 * Trust badges strip (certification marks, payment partners). $args: items, a, heading.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'trust-badges', $args['a']['grayscale'] ? 'is-grayscale' : '' ) ); ?>" style="--wgc-cols:<?php echo (int) $args['a']['columns']; ?>">
	<?php webgram_core()->view( 'sections/heading', $args['heading'] ); ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'trust-badges__grid' ) ); ?>">
		<?php foreach ( $args['items'] as $wgc_item ) : ?>
			<?php $wgc_tag = ! empty( $wgc_item['link'] ) ? 'a' : 'div'; ?>
			<<?php echo esc_attr( $wgc_tag ); ?> class="<?php echo esc_attr( Helpers::css_class( 'trust-badge' ) ); ?>"<?php echo 'a' === $wgc_tag ? ' href="' . esc_url( $wgc_item['link'] ) . '" target="_blank" rel="noopener"' : ''; ?>>
				<?php if ( ! empty( $wgc_item['image'] ) ) : ?>
					<?php echo wp_get_attachment_image( (int) $wgc_item['image'], 'medium', false, [ 'loading' => 'lazy', 'alt' => (string) ( $wgc_item['title'] ?? '' ) ] ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $wgc_item['title'] ) ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'trust-badge__title' ) ); ?>"><?php echo esc_html( $wgc_item['title'] ); ?></span>
				<?php endif; ?>
			</<?php echo esc_attr( $wgc_tag ); ?>>
		<?php endforeach; ?>
	</div>
</section>
