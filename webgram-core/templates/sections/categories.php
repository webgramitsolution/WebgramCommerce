<?php
/**
 * Category circles row (spec 4.3 item 3). $args: items, a, heading.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_a = $args['a'];
?>
<?php
// The theme's Category circles setting decides whether tablets get one swipe row or a wrapped grid.
$wgc_tablet_grid = function_exists( 'webgram_option' ) && ! webgram_option( 'cat_tablet_row' ) ? ' wg-categories--tablet-grid' : '';
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'categories', 'wgc-categories--' . $wgc_a['shape'] . ' wgc-categories--label-' . $wgc_a['label_position'] . $wgc_tablet_grid ) ); ?>" style="--wgc-cols:<?php echo (int) $wgc_a['columns']; ?>">
	<?php webgram_core()->view( 'sections/heading', $args['heading'] ); ?>
	<div class="<?php echo esc_attr( Helpers::css_class( 'categories__row' ) ); ?>" data-wg-component="carousel" data-wg-carousel="overflow">
		<?php foreach ( $args['items'] as $wgc_item ) : ?>
			<a class="<?php echo esc_attr( Helpers::css_class( 'category-item' ) ); ?>" href="<?php echo esc_url( $wgc_item['url'] ); ?>">
				<span class="<?php echo esc_attr( Helpers::css_class( 'category-item__media' ) ); ?>">
					<?php if ( $wgc_item['image'] ) : ?>
						<img src="<?php echo esc_url( $wgc_item['image'] ); ?>" alt="" loading="lazy" width="240" height="240">
					<?php endif; ?>
				</span>
				<?php if ( 'ribbon' === $wgc_a['label_position'] ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'category-item__ribbon' ) ); ?>"><span class="<?php echo esc_attr( Helpers::css_class( 'category-item__ribbon-text' ) ); ?>"><?php echo esc_html( $wgc_item['name'] ); ?></span></span>
				<?php endif; ?>
				<?php if ( 'below' === $wgc_a['label_position'] ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'category-item__label' ) ); ?>"><?php echo esc_html( $wgc_item['name'] ); ?></span>
				<?php endif; ?>
				<?php if ( $wgc_a['show_count'] ) : ?>
					<span class="<?php echo esc_attr( Helpers::css_class( 'category-item__count' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: products */ _n( '%d product', '%d products', $wgc_item['count'], 'webgram-core' ), $wgc_item['count'] ) ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
