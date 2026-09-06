<?php
/**
 * Review list wrapper. $args: page (see Module::page_data).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'reviews__list' ) ); ?>" data-wgc-reviews-list aria-live="polite">
	<?php if ( '' === $args['page']['html'] ) : ?>
		<p class="<?php echo esc_attr( Helpers::css_class( 'reviews__none' ) ); ?>" data-wgc-reviews-empty><?php esc_html_e( 'No reviews match this filter yet.', 'webgram-core' ); ?></p>
	<?php else : ?>
		<?php echo $args['page']['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered item templates. ?>
	<?php endif; ?>
</div>
