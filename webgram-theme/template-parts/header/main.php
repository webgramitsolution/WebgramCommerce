<?php
/**
 * Main header row: logo, search, icons, mobile toggle. Preset layout; the builder in Phase 1 replaces the fixed
 * order with a JSON layout rendered through the same element templates.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wg-header__main">
	<div class="wg-container wg-header__inner">
		<button class="wg-header__toggle wg-icon-btn" type="button" aria-expanded="false" aria-controls="wg-mobile-menu" data-wg-toggle="mobile-menu">
			<?php webgram_icon( 'menu' ); ?>
			<span class="wg-sr-only"><?php esc_html_e( 'Menu', 'webgram' ); ?></span>
		</button>

		<div class="wg-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="wg-header__title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( webgram_option( 'header_deliver_to' ) ) : ?>
			<div class="wg-header__deliver">
				<?php do_action( 'webgram/header/deliver_to' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( webgram_option( 'header_search' ) ) : ?>
			<div class="wg-header__search">
				<form role="search" method="get" class="wg-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="wg-sr-only" for="wg-search-input"><?php esc_html_e( 'Search products', 'webgram' ); ?></label>
					<?php webgram_icon( 'search', 'wg-search__icon' ); ?>
					<input id="wg-search-input" class="wg-search__input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search products...', 'webgram' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off">
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<input type="hidden" name="post_type" value="product">
					<?php endif; ?>
					<?php do_action( 'webgram/search/input' ); ?>
					<button class="wg-search__submit wg-sr-only" type="submit"><?php esc_html_e( 'Search', 'webgram' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<nav class="wg-header__icons" aria-label="<?php esc_attr_e( 'Account and cart', 'webgram' ); ?>">
			<?php
			$webgram_icons = (array) apply_filters( 'webgram/header/icons', [] );
			foreach ( $webgram_icons as $webgram_icon ) {
				echo wp_kses_post( $webgram_icon );
			}
			?>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="wg-icon-btn wg-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-wg-toggle="slide-cart">
					<?php webgram_icon( 'cart' ); ?>
					<span class="wg-icon-btn__label"><?php esc_html_e( 'Cart', 'webgram' ); ?></span>
					<span class="wg-icon-btn__count wg-cart-count"><?php echo esc_html( (string) ( WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ) ); ?></span>
				</a>
				<a class="wg-icon-btn wg-header__account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
					<?php webgram_icon( 'user' ); ?>
					<span class="wg-icon-btn__label"><?php esc_html_e( 'Account', 'webgram' ); ?></span>
				</a>
			<?php endif; ?>
		</nav>
	</div>
</div>

<div id="wg-mobile-menu" class="wg-drawer wg-drawer--left" data-wg-component="drawer" hidden>
	<div class="wg-drawer__head">
		<span class="wg-drawer__title"><?php esc_html_e( 'Menu', 'webgram' ); ?></span>
		<button class="wg-icon-btn" type="button" data-wg-close="drawer"><?php webgram_icon( 'close' ); ?><span class="wg-sr-only"><?php esc_html_e( 'Close', 'webgram' ); ?></span></button>
	</div>
	<div class="wg-drawer__body">
		<?php
		wp_nav_menu(
			[
				'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
				'container'      => false,
				'menu_class'     => 'wg-mobile-nav',
				'fallback_cb'    => false,
				'depth'          => 3,
			]
		);
		?>
	</div>
</div>
<div class="wg-overlay" data-wg-close="drawer" hidden></div>
