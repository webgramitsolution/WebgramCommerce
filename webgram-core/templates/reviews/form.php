<?php
/**
 * Review form wrapper for the Webgram block. $args: product, can_review, form_args.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__form' ) ); ?>" id="review_form_wrapper" data-wgc-reviews-form hidden>
	<div id="review_form">
		<?php if ( $args['can_review'] ) : ?>
			<?php comment_form( $args['form_args'], $args['product']->get_id() ); ?>
		<?php else : ?>
			<p class="<?php echo esc_attr( Helpers::css_class( 'reviews__closed', 'woocommerce-verification-required' ) ); ?>"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'webgram-core' ); ?></p>
		<?php endif; ?>
	</div>
</div>
