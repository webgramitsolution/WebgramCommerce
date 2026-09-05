<?php
/**
 * Consumes Webgram Core Layouts for whole page types (shop, blog archive, single post, cart, checkout,
 * thank you, my account). Header, footer, single product and 404 are consumed by their own templates.
 * Without Core, webgram_layout_id() returns 0 and nothing here changes the normal templates.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Layouts {

	/** @var array{type: string, id: int}|null */
	private static ?array $current = null;

	public static function init(): void {
		add_filter( 'template_include', [ self::class, 'template' ], 99 );
	}

	/** Layout type of the current request, or an empty string when none applies. */
	public static function type(): string {
		$wc = class_exists( 'WooCommerce' );
		if ( $wc && is_checkout() && is_wc_endpoint_url( 'order-received' ) ) {
			return 'thankyou';
		}
		if ( $wc && is_checkout() ) {
			return 'checkout';
		}
		if ( $wc && is_cart() ) {
			return 'cart';
		}
		if ( $wc && is_account_page() ) {
			return 'myaccount';
		}
		if ( $wc && ( is_shop() || is_product_taxonomy() ) ) {
			return 'shop';
		}
		if ( is_singular( 'post' ) ) {
			return 'single_post';
		}
		if ( is_home() || is_category() || is_tag() || is_author() || is_date() || is_tax() ) {
			return 'blog_archive';
		}
		return '';
	}

	public static function template( string $template ): string {
		$type = self::type();
		if ( '' === $type ) {
			return $template;
		}
		$id = webgram_layout_id( $type );
		if ( $id <= 0 ) {
			return $template;
		}
		self::$current = [ 'type' => $type, 'id' => $id ];
		return WEBGRAM_DIR . '/template-parts/layout.php';
	}

	/** @return array{type: string, id: int} */
	public static function current(): array {
		return self::$current ?? [ 'type' => '', 'id' => 0 ];
	}
}

Webgram_Layouts::init();
