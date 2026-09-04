<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** Contact cards on the product page: Call, Buy on chat (WhatsApp wa.me link), Ask for bulk quote (opens the inquiry modal). */
final class ContactSeller {

	public const META = '_wg_contact';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/product/summary/contact_seller', [ $this, 'render' ] );
		add_shortcode( 'webgram_contact_seller', [ $this, 'shortcode' ] );
	}

	/** Effective values: per-product override, then global settings. */
	public function values( \WC_Product $product ): array {
		$s        = $this->module->settings();
		$override = (array) get_post_meta( $product->get_id(), self::META, true );
		$phone    = (string) ( $override['phone'] ?? '' ) ?: (string) $s->get( 'contact_phone', '' );
		$whatsapp = (string) ( $override['whatsapp'] ?? '' ) ?: (string) $s->get( 'contact_whatsapp', '' );
		$chat     = (string) ( $override['chat_url'] ?? '' ) ?: (string) $s->get( 'contact_chat_url', '' );
		return (array) apply_filters(
			'webgram_core/contact_seller/values',
			[
				'phone'      => $phone,
				'whatsapp'   => $whatsapp,
				'chat_url'   => $chat,
				'show_call'  => Helpers::bool( $s->get( 'contact_show_call', true ) ) && '' !== $phone,
				'show_chat'  => Helpers::bool( $s->get( 'contact_show_chat', true ) ) && ( '' !== $whatsapp || '' !== $chat ),
				'show_bulk'  => Helpers::bool( $s->get( 'contact_show_bulk', true ) ),
				'call_label' => (string) $s->get( 'contact_call_label', __( 'Call us at', 'webgram-core' ) ),
				'chat_label' => (string) $s->get( 'contact_chat_label', __( 'Buy on Chat', 'webgram-core' ) ),
				'bulk_label' => (string) $s->get( 'contact_bulk_label', __( 'Ask for Bulk Qty Quote', 'webgram-core' ) ),
			],
			$product
		);
	}

	/** Pure: wa.me link with a prefilled message. */
	public static function whatsapp_link( string $number, string $message ): string {
		$digits = preg_replace( '/[^0-9]/', '', $number ) ?? '';
		if ( '' === $digits ) {
			return '';
		}
		return 'https://wa.me/' . $digits . '?text=' . rawurlencode( $message );
	}

	public function render( ?\WC_Product $product = null ): void {
		$product = $product ?: ( $GLOBALS['product'] ?? null );
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$v = $this->values( $product );
		if ( ! $v['show_call'] && ! $v['show_chat'] && ! $v['show_bulk'] ) {
			return;
		}
		$message = sprintf( /* translators: 1: product name, 2: product URL */ __( 'Hi, I am interested in %1$s (%2$s)', 'webgram-core' ), $product->get_name(), $product->get_permalink() );
		$chat    = $v['whatsapp'] ? self::whatsapp_link( $v['whatsapp'], $message ) : $v['chat_url'];
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		\webgram_core()->view(
			'woo-enhancements/contact-cards',
			[
				'values'     => $v,
				'chat_url'   => $chat,
				'tel'        => 'tel:' . preg_replace( '/[^0-9+]/', '', $v['phone'] ),
				'product_id' => $product->get_id(),
			]
		);
	}

	public function shortcode( array|string $atts ): string {
		$atts    = shortcode_atts( [ 'product_id' => 0 ], (array) $atts, 'webgram_contact_seller' );
		$product = wc_get_product( (int) $atts['product_id'] ?: get_the_ID() );
		if ( ! $product ) {
			return '';
		}
		ob_start();
		$this->render( $product );
		return (string) ob_get_clean();
	}
}
