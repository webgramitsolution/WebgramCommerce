<?php
namespace Webgram\Core\Modules\WooEnhancements;

defined( 'ABSPATH' ) || exit;

/**
 * Cart drawer recommendations: cross-sells of the cart items, then best sellers, excluding what is already in the
 * cart. Provider list is filterable (webgram_core/cart/recommendations).
 */
final class CartRecommendations {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/cart/recommendations', [ $this, 'render' ] );
		add_filter( 'webgram/help/contacts', [ $this, 'help_contacts' ] );
	}

	/** Pure: merge candidate id lists, exclude cart ids, cap. */
	public static function pick( array $cross_sells, array $best_sellers, array $in_cart, int $limit ): array {
		$ids = [];
		foreach ( array_merge( $cross_sells, $best_sellers ) as $id ) {
			$id = (int) $id;
			if ( $id > 0 && ! in_array( $id, $in_cart, true ) && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
			if ( count( $ids ) >= $limit ) {
				break;
			}
		}
		return $ids;
	}

	/** @return \WC_Product[] */
	public function products( int $limit = 6 ): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return [];
		}
		$in_cart = [];
		foreach ( WC()->cart->get_cart() as $item ) {
			$in_cart[] = (int) $item['product_id'];
		}
		$cross = array_map( 'intval', WC()->cart->get_cross_sells() );
		$best  = [];
		if ( count( $cross ) < $limit ) {
			$best = wc_get_products( [ 'limit' => $limit + count( $in_cart ), 'status' => 'publish', 'orderby' => 'popularity', 'return' => 'ids', 'exclude' => $in_cart ] );
		}
		$ids = self::pick( $cross, array_map( 'intval', (array) $best ), $in_cart, $limit );
		$ids = (array) apply_filters( 'webgram_core/cart/recommendations', $ids, $in_cart, $limit );
		if ( ! $ids ) {
			return [];
		}
		$products = wc_get_products( [ 'include' => $ids, 'limit' => count( $ids ), 'status' => 'publish', 'orderby' => 'include' ] );
		return array_values( array_filter( $products, static fn( $p ) => $p->is_visible() && $p->is_purchasable() && $p->is_in_stock() ) );
	}

	public function render(): void {
		$products = $this->products( (int) apply_filters( 'webgram_core/cart/recommendations_count', 6 ) );
		if ( ! $products ) {
			return;
		}
		\webgram_core()->view( 'woo-enhancements/cart-recommendations', [ 'products' => $products, 'title' => __( 'You may also like', 'webgram-core' ) ] );
	}

	/** Help page contact cards from the Contact seller settings. */
	public function help_contacts( array $cards ): array {
		$s     = $this->module->settings();
		$phone = (string) $s->get( 'contact_phone', '' );
		$wa    = (string) $s->get( 'contact_whatsapp', '' );
		$email = (string) $s->get( 'contact_email', '' ) ?: (string) get_option( 'admin_email' );
		if ( $phone ) {
			$cards[] = [ 'icon' => 'phone', 'title' => $phone, 'text' => __( 'Call us', 'webgram-core' ), 'url' => 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ];
		}
		if ( $wa ) {
			$cards[] = [ 'icon' => 'social-whatsapp', 'title' => __( 'Chat on WhatsApp', 'webgram-core' ), 'text' => $wa, 'url' => ContactSeller::whatsapp_link( $wa, __( 'Hi, I need help with my order', 'webgram-core' ) ) ];
		}
		if ( $email ) {
			$cards[] = [ 'icon' => 'mail', 'title' => $email, 'text' => __( 'Email support', 'webgram-core' ), 'url' => 'mailto:' . $email ];
		}
		return $cards;
	}
}
