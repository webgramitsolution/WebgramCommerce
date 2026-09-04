<?php
/**
 * Copyright row.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wg-footer__bottom">
	<div class="wg-container wg-footer__bottom-inner">
		<p class="wg-footer__copyright"><?php echo wp_kses_post( (string) webgram_option( 'footer_copyright' ) ); ?></p>
		<?php do_action( 'webgram/footer/before_copyright_end' ); ?>
		<?php
		if ( has_nav_menu( 'footer' ) ) {
			wp_nav_menu(
				[
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'wg-footer__nav',
					'fallback_cb'    => false,
					'depth'          => 1,
				]
			);
		}
		?>
	</div>
</div>
