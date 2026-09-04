<?php
namespace Webgram\Core\Compat;

defined( 'ABSPATH' ) || exit;

final class Hpos {

	public static function declare(): void {
		add_action(
			'before_woocommerce_init',
			static function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WEBGRAM_CORE_FILE, true );
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WEBGRAM_CORE_FILE, true );
				}
			}
		);
	}
}
