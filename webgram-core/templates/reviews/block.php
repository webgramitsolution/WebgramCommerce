<?php
/**
 * Full reviews block (spec 4.7). $args: product, summary, page, per_page, show_summary, show_filters, show_sort,
 * sorts, can_review, form_args, ratings_on.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_page = $args['page'];
?>
<section class="<?php echo esc_attr( Helpers::css_class( 'reviews' ) ); ?>" id="reviews" data-wgc-reviews="<?php echo (int) $args['product']->get_id(); ?>" data-per-page="<?php echo (int) $args['per_page']; ?>" data-sort="<?php echo esc_attr( $wgc_page['params']['sort'] ); ?>" data-total="<?php echo (int) $wgc_page['total']; ?>" data-pages="<?php echo (int) $wgc_page['pages']; ?>">
	<?php if ( $args['show_summary'] && $args['ratings_on'] ) : ?>
		<?php webgram_core()->view( 'reviews/summary', [ 'summary' => $args['summary'], 'product' => $args['product'], 'show_button' => true ] ); ?>
	<?php else : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__write' ) ); ?>"><button type="button" class="wg-btn wg-btn--secondary" data-wgc-reviews-write><?php esc_html_e( 'Write a review', 'webgram-core' ); ?></button></div>
	<?php endif; ?>

	<?php webgram_core()->view( 'reviews/form', [ 'product' => $args['product'], 'can_review' => $args['can_review'], 'form_args' => $args['form_args'] ] ); ?>

	<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__head' ) ); ?>">
		<div>
			<h3 class="<?php echo esc_attr( Helpers::css_class( 'reviews__heading' ) ); ?>"><?php esc_html_e( 'Customer Reviews', 'webgram-core' ); ?></h3>
			<p class="<?php echo esc_attr( Helpers::css_class( 'reviews__showing' ) ); ?>" data-wgc-reviews-showing><?php echo esc_html( $wgc_page['showing'] ); ?></p>
		</div>
		<?php if ( $args['show_sort'] ) : ?>
			<label class="<?php echo esc_attr( Helpers::css_class( 'reviews__sort' ) ); ?>">
				<span><?php esc_html_e( 'Sort by:', 'webgram-core' ); ?></span>
				<select data-wgc-reviews-sort>
					<?php foreach ( $args['sorts'] as $wgc_key => $wgc_label ) : ?>
						<option value="<?php echo esc_attr( $wgc_key ); ?>" <?php selected( $wgc_page['params']['sort'], $wgc_key ); ?>><?php echo esc_html( $wgc_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>
	</div>

	<?php if ( $args['show_filters'] ) : ?>
		<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__chips' ) ); ?>" role="group" aria-label="<?php esc_attr_e( 'Filter reviews', 'webgram-core' ); ?>">
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'chip', 'is-active' ) ); ?>" data-wgc-reviews-filter="all"><?php esc_html_e( 'All', 'webgram-core' ); ?></button>
			<?php if ( $args['ratings_on'] ) : ?>
				<?php for ( $wgc_i = 5; $wgc_i >= 1; $wgc_i-- ) : ?>
					<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'chip' ) ); ?>" data-wgc-reviews-filter="stars" data-value="<?php echo (int) $wgc_i; ?>"><?php echo (int) $wgc_i; ?> <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.95 6.1 6.7.9-4.9 4.7 1.2 6.7L12 17.7l-5.95 3.2 1.2-6.7-4.9-4.7 6.7-.9z"/></svg></button>
				<?php endfor; ?>
			<?php endif; ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'chip' ) ); ?>" data-wgc-reviews-filter="media"><?php esc_html_e( 'With media', 'webgram-core' ); ?></button>
		</div>
	<?php endif; ?>

	<?php webgram_core()->view( 'reviews/list', [ 'page' => $wgc_page ] ); ?>

	<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__more' ) ); ?>" data-wgc-reviews-more-wrap <?php echo $wgc_page['has_more'] ? '' : 'hidden'; ?>>
		<button type="button" class="wg-btn wg-btn--primary" data-wgc-reviews-more data-label-more="<?php esc_attr_e( 'Load more reviews', 'webgram-core' ); ?>"><?php esc_html_e( 'View All Reviews', 'webgram-core' ); ?></button>
	</div>

	<div class="<?php echo esc_attr( Helpers::css_class( 'modal', 'wgc-modal--lightbox' ) ); ?>" data-wgc-lightbox-modal hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Review media', 'webgram-core' ); ?>">
		<div class="<?php echo esc_attr( Helpers::css_class( 'modal__backdrop' ) ); ?>" data-wgc-lightbox-close></div>
		<div class="<?php echo esc_attr( Helpers::css_class( 'modal__dialog' ) ); ?>">
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'modal__close' ) ); ?>" data-wgc-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
			<div class="<?php echo esc_attr( Helpers::css_class( 'lightbox__stage' ) ); ?>" data-wgc-lightbox-stage></div>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'lightbox__nav', 'is-prev' ) ); ?>" data-wgc-lightbox-prev aria-label="<?php esc_attr_e( 'Previous', 'webgram-core' ); ?>">&lsaquo;</button>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'lightbox__nav', 'is-next' ) ); ?>" data-wgc-lightbox-next aria-label="<?php esc_attr_e( 'Next', 'webgram-core' ); ?>">&rsaquo;</button>
		</div>
	</div>
	<?php do_action( 'webgram_core/reviews/after_block', $args['product'] ); ?>
</section>
