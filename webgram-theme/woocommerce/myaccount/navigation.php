<?php
/**
 * My Account navigation with icons.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Webgram
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );

$webgram_icons = Webgram_WC_Account::nav_icons();
$webgram_show  = (bool) webgram_option( 'account_nav_icons' );
?>
<nav class="woocommerce-MyAccount-navigation wg-account-nav" aria-label="<?php esc_attr_e( 'Account pages', 'webgram' ); ?>">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>">
					<?php
					if ( $webgram_show ) {
webgram_icon( (string) ( $webgram_icons[ $endpoint ] ?? 'circle' ) ); }
?>
					<span><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
<?php do_action( 'woocommerce_after_account_navigation' ); ?>
