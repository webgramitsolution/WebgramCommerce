<?php
/**
 * Secondary bar below the header: highlighted categories or campaigns (e.g. festival collections).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="wg-header__secondary" aria-label="<?php esc_attr_e( 'Featured collections', 'webgram' ); ?>">
	<div class="wg-container">
		<?php
		wp_nav_menu(
			[
				'theme_location' => 'secondary',
				'container'      => false,
				'menu_class'     => 'wg-secondary-nav',
				'fallback_cb'    => false,
				'depth'          => 1,
			]
		);
		?>
	</div>
</nav>
