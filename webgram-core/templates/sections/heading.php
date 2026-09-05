<?php
/**
 * Section heading with the dot-line ornament (CSS drawn, never text characters). $args: title, subtitle, align,
 * link_url, link_text.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

if ( '' === $args['title'] && '' === $args['link_url'] ) {
	return;
}
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'section-heading', 'start' === $args['align'] ? 'wg-section-heading--start' : '' ) ); ?>">
	<?php if ( '' !== $args['title'] ) : ?>
		<span class="wg-section-heading__ornament" aria-hidden="true"></span>
		<div class="<?php echo esc_attr( Helpers::css_class( 'section-heading__text' ) ); ?>">
			<h2 class="<?php echo esc_attr( Helpers::css_class( 'section-heading__title' ) ); ?>"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php if ( '' !== $args['subtitle'] ) : ?>
				<p class="<?php echo esc_attr( Helpers::css_class( 'section-heading__subtitle' ) ); ?>"><?php echo esc_html( $args['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
		<span class="wg-section-heading__ornament wg-section-heading__ornament--end" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( '' !== $args['link_url'] && '' !== $args['link_text'] ) : ?>
		<a class="<?php echo esc_attr( Helpers::css_class( 'section-heading__link', 'wg-section-heading__link' ) ); ?>" href="<?php echo esc_url( $args['link_url'] ); ?>"><?php echo esc_html( $args['link_text'] ); ?> <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
	<?php endif; ?>
</div>
