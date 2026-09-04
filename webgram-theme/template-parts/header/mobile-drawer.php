<?php
/**
 * Off-canvas mobile menu with Menu | Categories tabs, optional search, account links and currency/language slots.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_tabs  = (bool) webgram_option( 'mobile_menu_tabs' ) && taxonomy_exists( 'product_cat' );
$webgram_side  = 'right' === webgram_option( 'mobile_menu_position' ) ? 'right' : 'left';
$webgram_width = (int) webgram_option( 'mobile_menu_width' );
$webgram_loc   = has_nav_menu( 'mobile' ) ? 'mobile' : ( has_nav_menu( 'primary' ) ? 'primary' : ( has_nav_menu( 'secondary' ) ? 'secondary' : '' ) );
?>
<div id="wg-mobile-menu" class="wg-drawer wg-drawer--<?php echo esc_attr( $webgram_side ); ?> wg-drawer--menu" data-wg-component="drawer" style="--wg-drawer-width:<?php echo esc_attr( (string) $webgram_width ); ?>px" hidden>
	<div class="wg-drawer__head">
		<?php if ( $webgram_tabs ) : ?>
			<div class="wg-drawer__tabs" role="tablist">
				<button type="button" class="wg-drawer__tab is-active" role="tab" aria-selected="true" aria-controls="wg-drawer-menu" data-wg-tab="menu"><?php esc_html_e( 'Menu', 'webgram' ); ?></button>
				<button type="button" class="wg-drawer__tab" role="tab" aria-selected="false" aria-controls="wg-drawer-categories" data-wg-tab="categories"><?php esc_html_e( 'Categories', 'webgram' ); ?></button>
			</div>
		<?php else : ?>
			<span class="wg-drawer__title"><?php esc_html_e( 'Menu', 'webgram' ); ?></span>
		<?php endif; ?>
		<button class="wg-icon-btn wg-icon-btn--no-label" type="button" data-wg-close="drawer"><?php webgram_icon( 'close' ); ?><span class="wg-sr-only"><?php esc_html_e( 'Close', 'webgram' ); ?></span></button>
	</div>

	<?php if ( webgram_option( 'mobile_menu_search' ) ) : ?>
		<div class="wg-drawer__search">
			<?php webgram_part( 'header/search-form', [ 'id' => 'wg-search-drawer', 'style' => 'rounded', 'min_width' => 0 ] ); ?>
		</div>
	<?php endif; ?>

	<div class="wg-drawer__body">
		<div class="wg-drawer__pane is-active" id="wg-drawer-menu" role="tabpanel">
			<?php
			if ( $webgram_loc ) {
				wp_nav_menu(
					[
						'theme_location' => $webgram_loc,
						'container'      => false,
						'menu_class'     => 'wg-mobile-nav',
						'fallback_cb'    => false,
						'depth'          => 3,
						'walker'         => new Webgram_Mobile_Nav_Walker(),
					]
				);
			}
			?>
		</div>
		<?php if ( $webgram_tabs ) : ?>
			<div class="wg-drawer__pane" id="wg-drawer-categories" role="tabpanel" hidden>
				<?php
				$webgram_terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 40 ] );
				if ( ! is_wp_error( $webgram_terms ) && $webgram_terms ) :
					$webgram_all = 'all' === webgram_option( 'mobile_menu_categories' );
					?>
					<ul class="wg-mobile-cats">
						<?php foreach ( $webgram_terms as $webgram_term ) : ?>
							<?php
							$webgram_thumb    = (int) get_term_meta( $webgram_term->term_id, 'thumbnail_id', true );
							$webgram_children = $webgram_all ? get_terms( [ 'taxonomy' => 'product_cat', 'parent' => $webgram_term->term_id, 'hide_empty' => true ] ) : [];
							$webgram_has_kids = ! is_wp_error( $webgram_children ) && $webgram_children;
							?>
							<li class="<?php echo $webgram_has_kids ? 'has-children' : ''; ?>">
								<a href="<?php echo esc_url( (string) get_term_link( $webgram_term ) ); ?>">
									<?php if ( $webgram_thumb ) : ?>
										<?php echo wp_get_attachment_image( $webgram_thumb, 'webgram-thumb', false, [ 'class' => 'wg-mobile-cats__thumb', 'loading' => 'lazy' ] ); ?>
									<?php else : ?>
										<span class="wg-mobile-cats__thumb wg-mobile-cats__thumb--empty"><?php webgram_icon( 'grid' ); ?></span>
									<?php endif; ?>
									<span><?php echo esc_html( $webgram_term->name ); ?></span>
								</a>
								<?php if ( $webgram_has_kids ) : ?>
									<button class="wg-nav__toggle" type="button" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle subcategories', 'webgram' ); ?>"><?php webgram_icon( 'chevron-down' ); ?></button>
									<ul class="wg-nav__sub">
										<?php foreach ( $webgram_children as $webgram_child ) : ?>
											<li><a href="<?php echo esc_url( (string) get_term_link( $webgram_child ) ); ?>"><?php echo esc_html( $webgram_child->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="wg-drawer__foot">
		<?php do_action( 'webgram/mobile_menu/before_account' ); ?>
		<?php if ( webgram_option( 'mobile_menu_account' ) ) : ?>
			<?php $webgram_account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url(); ?>
			<ul class="wg-drawer__links">
				<?php if ( is_user_logged_in() ) : ?>
					<li><a href="<?php echo esc_url( $webgram_account ); ?>"><?php webgram_icon( 'user' ); ?><?php esc_html_e( 'My account', 'webgram' ); ?></a></li>
					<?php if ( function_exists( 'wc_get_account_endpoint_url' ) ) : ?>
						<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><?php webgram_icon( 'package' ); ?><?php esc_html_e( 'Orders', 'webgram' ); ?></a></li>
					<?php endif; ?>
					<li><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php webgram_icon( 'log-out' ); ?><?php esc_html_e( 'Logout', 'webgram' ); ?></a></li>
				<?php else : ?>
					<li><a href="<?php echo esc_url( $webgram_account ); ?>"><?php webgram_icon( 'user' ); ?><?php esc_html_e( 'Login / Register', 'webgram' ); ?></a></li>
				<?php endif; ?>
				<?php do_action( 'webgram/mobile_menu/account_links' ); ?>
			</ul>
		<?php endif; ?>
		<div class="wg-drawer__slots">
			<?php do_action( 'webgram/header/currency', 'mobile' ); ?>
			<?php do_action( 'webgram/header/language', 'mobile' ); ?>
		</div>
		<?php do_action( 'webgram/mobile_menu/after' ); ?>
	</div>
</div>
