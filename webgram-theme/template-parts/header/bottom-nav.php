<?php
/**
 * Mobile bottom navbar (fixed, under 992px).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_items = array_slice( (array) webgram_option( 'mobile_nav_items' ), 0, 6 );
if ( ! $webgram_items ) {
	return;
}
$webgram_wc = class_exists( 'WooCommerce' );
?>
<nav class="wg-bottom-nav wg-bottom-nav--<?php echo esc_attr( (string) webgram_option( 'mobile_nav_style' ) ); ?><?php echo webgram_option( 'mobile_nav_labels' ) ? '' : ' wg-bottom-nav--no-labels'; ?>" aria-label="<?php esc_attr_e( 'Mobile navigation', 'webgram' ); ?>" data-wg-component="bottom-nav" data-hide-on-scroll="<?php echo webgram_option( 'mobile_nav_hide_scroll' ) ? '1' : '0'; ?>">
	<?php
	foreach ( $webgram_items as $webgram_item ) :
		$webgram_action = (string) ( $webgram_item['action'] ?? 'custom' );
		$webgram_href   = '';
		$webgram_attrs  = '';
		$webgram_badge  = '';
		switch ( $webgram_action ) {
			case 'home':
				$webgram_href = home_url( '/' );
				break;
			case 'shop':
				$webgram_href = $webgram_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
				break;
			case 'search':
				$webgram_attrs = ' data-wg-toggle="search-overlay" aria-controls="wg-search-overlay"';
				break;
			case 'menu':
				$webgram_attrs = ' data-wg-toggle="mobile-menu" aria-controls="wg-mobile-menu"';
				break;
			case 'cart':
				if ( ! $webgram_wc ) {
					continue 2;
				}
				$webgram_href  = wc_get_cart_url();
				$webgram_attrs = ' data-wg-toggle="slide-cart"';
				$webgram_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
				$webgram_badge = '<span class="wg-bottom-nav__badge wg-cart-count" data-count="' . esc_attr( (string) $webgram_count ) . '">' . esc_html( (string) $webgram_count ) . '</span>';
				break;
			case 'account':
				$webgram_href = $webgram_wc ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
				break;
			case 'wishlist':
			case 'compare':
				$webgram_href = (string) apply_filters( 'webgram/header/link_url', '', $webgram_action );
				if ( '' === $webgram_href ) {
					continue 2;
				}
				$webgram_count = (int) apply_filters( 'webgram/header/link_count', 0, $webgram_action );
				$webgram_badge = '<span class="wg-bottom-nav__badge wg-' . esc_attr( $webgram_action ) . '-count" data-count="' . esc_attr( (string) $webgram_count ) . '">' . esc_html( (string) $webgram_count ) . '</span>';
				break;
			default:
				$webgram_href = (string) ( $webgram_item['link'] ?? '' );
		}
		$webgram_tag = $webgram_href ? 'a' : 'button';
		?>
		<<?php echo esc_attr( $webgram_tag ); ?> class="wg-bottom-nav__item wg-bottom-nav__item--<?php echo esc_attr( $webgram_action ); ?>"<?php echo $webgram_href ? ' href="' . esc_url( $webgram_href ) . '"' : ' type="button"'; ?><?php echo $webgram_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute strings. ?>>
			<span class="wg-bottom-nav__icon"><?php webgram_icon( (string) ( $webgram_item['icon'] ?? 'circle' ) ); ?><?php echo $webgram_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></span>
			<span class="wg-bottom-nav__label"><?php echo esc_html( (string) ( $webgram_item['label'] ?? '' ) ); ?></span>
		</<?php echo esc_attr( $webgram_tag ); ?>>
	<?php endforeach; ?>
</nav>
