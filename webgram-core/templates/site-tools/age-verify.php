<?php
/**
 * Age verification gate. $args: title, text, min, mode, yes, no, redirect, days, bg.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_bg = $args['bg'] ? (string) wp_get_attachment_image_url( (int) $args['bg'], 'full' ) : '';
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'age' ) ); ?>" data-wgc-age data-min="<?php echo (int) $args['min']; ?>" data-days="<?php echo (int) $args['days']; ?>" data-redirect="<?php echo esc_url( $args['redirect'] ); ?>" role="dialog" aria-modal="true" aria-labelledby="wgc-age-title" <?php echo $wgc_bg ? 'style="background-image:url(' . esc_url( $wgc_bg ) . ')"' : ''; ?>>
	<div class="<?php echo esc_attr( Helpers::css_class( 'age__dialog' ) ); ?>">
		<?php if ( has_custom_logo() ) : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'age__logo' ) ); ?>"><?php the_custom_logo(); ?></div>
		<?php endif; ?>
		<h2 id="wgc-age-title"><?php echo esc_html( $args['title'] ); ?></h2>
		<p><?php echo esc_html( $args['text'] ); ?></p>
		<?php if ( 'date' === $args['mode'] ) : ?>
			<form data-wgc-age-form class="<?php echo esc_attr( Helpers::css_class( 'age__form' ) ); ?>">
				<label><span class="screen-reader-text"><?php esc_html_e( 'Date of birth', 'webgram-core' ); ?></span><input type="date" name="dob" required max="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"></label>
				<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn--primary' ) ); ?>"><?php echo esc_html( $args['yes'] ); ?></button>
			</form>
			<p class="<?php echo esc_attr( Helpers::css_class( 'age__error' ) ); ?>" data-wgc-age-error hidden><?php echo esc_html( sprintf( /* translators: %d: minimum age */ __( 'You must be at least %d years old.', 'webgram-core' ), (int) $args['min'] ) ); ?></p>
		<?php else : ?>
			<div class="<?php echo esc_attr( Helpers::css_class( 'age__actions' ) ); ?>">
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--ghost wg-btn--ghost' ) ); ?>" data-wgc-age-no><?php echo esc_html( $args['no'] ); ?></button>
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'btn', 'wgc-btn--primary wg-btn--primary' ) ); ?>" data-wgc-age-yes><?php echo esc_html( $args['yes'] ); ?></button>
			</div>
		<?php endif; ?>
	</div>
</div>
