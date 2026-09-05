<?php
namespace Webgram\Core\Modules\Notifications;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** WhatsApp transactional consent: checkout checkbox, order meta, user meta, My Account toggle, admin display. */
final class OptIn {

	public const ORDER_META = '_wg_whatsapp_optin';
	public const USER_META  = '_wg_whatsapp_optin';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'woocommerce_checkout_fields', [ $this, 'checkout_field' ] );
		add_action( 'woocommerce_checkout_create_order', [ $this, 'save_order' ], 10, 2 );
		add_action( 'woocommerce_edit_account_form', [ $this, 'account_field' ] );
		add_action( 'woocommerce_save_account_details', [ $this, 'save_account' ] );
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'admin_display' ] );
	}

	private function active(): bool {
		return $this->module->whatsapp_connected() && Helpers::bool( $this->module->settings()->get( 'optin_enabled', true ) );
	}

	public function checkout_field( array $fields ): array {
		if ( ! $this->active() ) {
			return $fields;
		}
		$user_default = is_user_logged_in() ? get_user_meta( get_current_user_id(), self::USER_META, true ) : '';
		$fields['order']['wg_whatsapp_optin'] = [
			'type'     => 'checkbox',
			'label'    => (string) $this->module->settings()->get( 'optin_label', __( 'Send order updates on WhatsApp', 'webgram-core' ) ),
			'required' => false,
			'class'    => [ 'form-row-wide', 'wg-whatsapp-optin' ],
			'priority' => 90,
			'default'  => '' !== $user_default ? ( 'yes' === $user_default ? 1 : 0 ) : ( Helpers::bool( $this->module->settings()->get( 'optin_default', false ) ) ? 1 : 0 ),
		];
		return $fields;
	}

	public function save_order( \WC_Order $order, array $data ): void {
		if ( ! $this->active() ) {
			return;
		}
		$yes = ! empty( $data['wg_whatsapp_optin'] ) || ! empty( $_POST['wg_whatsapp_optin'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
		$order->update_meta_data( self::ORDER_META, $yes ? 'yes' : 'no' );
		$order->update_meta_data( self::ORDER_META . '_at', current_time( 'mysql', true ) );
		if ( $order->get_customer_id() ) {
			update_user_meta( $order->get_customer_id(), self::USER_META, $yes ? 'yes' : 'no' );
		}
	}

	/** Pure: consent for an order (order meta first, then the customer's account preference). */
	public static function consented( string $order_meta, string $user_meta ): bool {
		if ( 'yes' === $order_meta ) {
			return true;
		}
		if ( 'no' === $order_meta ) {
			return false;
		}
		return 'yes' === $user_meta;
	}

	public function has_consent( \WC_Order $order ): bool {
		$user_meta = $order->get_customer_id() ? (string) get_user_meta( $order->get_customer_id(), self::USER_META, true ) : '';
		return self::consented( (string) $order->get_meta( self::ORDER_META, true ), $user_meta );
	}

	public function account_field(): void {
		if ( ! $this->active() ) {
			return;
		}
		$value = get_user_meta( get_current_user_id(), self::USER_META, true );
		printf(
			'<p class="woocommerce-form-row form-row form-row-wide wg-whatsapp-optin"><label><input type="checkbox" name="wg_whatsapp_optin" value="1" %s> %s</label></p>',
			checked( 'yes', $value, false ),
			esc_html( (string) $this->module->settings()->get( 'optin_label', __( 'Send order updates on WhatsApp', 'webgram-core' ) ) )
		);
	}

	public function save_account( int $user_id ): void {
		if ( $this->active() ) {
			update_user_meta( $user_id, self::USER_META, ! empty( $_POST['wg_whatsapp_optin'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the form nonce.
		}
	}

	public function admin_display( $order ): void {
		if ( $order instanceof \WC_Order && $this->module->whatsapp_connected() ) {
			$meta = (string) $order->get_meta( self::ORDER_META, true );
			printf( '<p><strong>%s:</strong> %s</p>', esc_html__( 'WhatsApp updates', 'webgram-core' ), esc_html( 'yes' === $meta ? __( 'Consented', 'webgram-core' ) : ( 'no' === $meta ? __( 'Declined', 'webgram-core' ) : __( 'Not asked', 'webgram-core' ) ) ) );
		}
	}
}
