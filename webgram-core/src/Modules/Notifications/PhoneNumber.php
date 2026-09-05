<?php
namespace Webgram\Core\Modules\Notifications;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** E.164 from the order billing phone using the billing country, then the default country. */
final class PhoneNumber {

	/** Pure. */
	public static function normalize( string $phone, string $country, string $default_country = 'IN' ): string {
		$code = Helpers::calling_code( $country ?: $default_country );
		$e164 = Helpers::to_e164( $phone, $code );
		if ( '' === $e164 && '' !== $default_country && $country !== $default_country ) {
			$e164 = Helpers::to_e164( $phone, Helpers::calling_code( $default_country ) );
		}
		return $e164;
	}

	public static function for_order( \WC_Order $order, string $default_country = 'IN' ): string {
		$phone = (string) ( $order->get_billing_phone() ?: $order->get_shipping_phone() );
		return self::normalize( $phone, (string) $order->get_billing_country(), $default_country ?: (string) ( wc_get_base_location()['country'] ?? 'IN' ) );
	}
}
