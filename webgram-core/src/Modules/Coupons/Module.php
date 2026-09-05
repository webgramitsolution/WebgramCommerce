<?php
namespace Webgram\Core\Modules\Coupons;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Coupons: per-product coupon box ("FLAT 20% OFF Use code SAVE20 [Copy code]") and the cart offer progress with
 * milestones (rendered by the cart drawer in Phase 3, computed here).
 */
final class Module extends BaseModule {

	public const META = '_wg_coupon';

	public function id(): string {
		return 'coupons';
	}

	public function name(): string {
		return __( 'Coupons', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Coupon box on product pages with copy to clipboard, and the cart offer progress with milestones.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 2;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'webgram/product/summary/coupon', [ $this, 'product_box' ] );
		add_shortcode( 'webgram_coupon', [ $this, 'shortcode' ] );
		add_action( 'webgram_core/product_panel/fields', [ $this, 'panel_fields' ] );
		add_action( 'webgram_core/product_panel/save', [ $this, 'panel_save' ] );
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		( new OfferProgress( $this ) )->register();
		add_action( 'woocommerce_add_to_cart', [ $this, 'apply_pending' ], 20 );
		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'coupon_progress';
			}
			protected function fields(): array {
				return [];
			}
			/** Offer progress data plus rendered markup; the cart page JS refreshes the bar after WooCommerce updates the cart. */
			protected function handle( array $input ): void {
				$progress = new OfferProgress( $this->module );
				$data     = $progress->data();
				$this->success( $data + [ 'html' => $data['milestones'] ? \webgram_core()->view( 'coupons/progress', $data, false ) : '' ] );
			}
		} )->register();
		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'coupon_apply';
			}
			protected function fields(): array {
				return [ 'code' => 'text' ];
			}
			protected function handle( array $input ): void {
				$result = $this->module->apply( (string) ( $input['code'] ?? '' ) );
				if ( ! $result['ok'] ) {
					$this->error( $result['message'] );
				}
				$this->success( $result + [ 'fragments' => function_exists( 'WC' ) && WC()->cart ? apply_filters( 'woocommerce_add_to_cart_fragments', [] ) : [] ] );
			}
		} )->register();
	}

	/**
	 * Applies a coupon from the product box. With items in the cart it is applied at once; with an empty cart it is
	 * remembered in the session and applied on the next add to cart. Returns ok, message, state (applied|pending).
	 *
	 * @return array{ok: bool, message: string, state: string}
	 */
	public function apply( string $code ): array {
		$code = wc_format_coupon_code( $code );
		if ( '' === $code || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return [ 'ok' => false, 'message' => __( 'Enter a coupon code.', 'webgram-core' ), 'state' => 'error' ];
		}
		$coupon = new \WC_Coupon( $code );
		if ( ! $coupon->get_id() || ! self::is_live( $coupon ) ) {
			return [ 'ok' => false, 'message' => __( 'This coupon is not valid right now.', 'webgram-core' ), 'state' => 'error' ];
		}
		if ( WC()->cart->has_discount( $code ) ) {
			return [ 'ok' => true, 'message' => __( 'Coupon already applied.', 'webgram-core' ), 'state' => 'applied' ];
		}
		if ( WC()->cart->is_empty() ) {
			if ( WC()->session ) {
				WC()->session->set( 'webgram_pending_coupon', $code );
			}
			return [ 'ok' => true, 'message' => __( 'Coupon saved. It applies when you add this product to the cart.', 'webgram-core' ), 'state' => 'pending' ];
		}
		wc_clear_notices();
		$applied = WC()->cart->apply_coupon( $code );
		$errors  = wc_get_notices( 'error' );
		wc_clear_notices();
		if ( ! $applied ) {
			return [ 'ok' => false, 'message' => $errors ? wp_strip_all_tags( (string) ( $errors[0]['notice'] ?? '' ) ) : __( 'This coupon cannot be applied to your cart.', 'webgram-core' ), 'state' => 'error' ];
		}
		WC()->cart->calculate_totals();
		return [ 'ok' => true, 'message' => __( 'Coupon applied to your cart.', 'webgram-core' ), 'state' => 'applied' ];
	}

	/** Applies the coupon remembered by apply() once the first product lands in the cart. */
	public function apply_pending(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart ) {
			return;
		}
		$code = (string) WC()->session->get( 'webgram_pending_coupon', '' );
		if ( '' === $code ) {
			return;
		}
		WC()->session->set( 'webgram_pending_coupon', '' );
		if ( ! WC()->cart->has_discount( $code ) ) {
			WC()->cart->apply_coupon( $code );
		}
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-coupons', 'css/coupons.css' );
		$assets->script( 'webgram-core-coupons', 'js/coupons.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'h_box', 'label' => __( 'Product coupon box', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Choose the coupon per product in the Webgram tab of the product data box. A default coupon applies to products without one.', 'webgram-core' ) ],
			[ 'id' => 'default_coupon', 'label' => __( 'Default coupon code', 'webgram-core' ), 'type' => 'text', 'default' => '', 'description' => __( 'Leave empty for none.', 'webgram-core' ) ],
			[ 'id' => 'copy_label', 'label' => __( 'Copy button label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Copy code', 'webgram-core' ) ],
			[ 'id' => 'show_apply', 'label' => __( 'Apply button (adds the coupon to the cart without leaving the page)', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'apply_label', 'label' => __( 'Apply button label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Apply', 'webgram-core' ) ],
			[ 'id' => 'box_style', 'label' => __( 'Box style', 'webgram-core' ), 'type' => 'select', 'options' => [ 'soft' => __( 'Soft (tinted background, dashed border)', 'webgram-core' ), 'outline' => __( 'Outline', 'webgram-core' ), 'solid' => __( 'Solid', 'webgram-core' ), 'ticket' => __( 'Ticket (cut corners)', 'webgram-core' ) ], 'default' => 'soft' ],
			[ 'id' => 'box_color', 'label' => __( 'Box accent color', 'webgram-core' ), 'type' => 'color', 'default' => '#15803d' ],
			[ 'id' => 'show_icon', 'label' => __( 'Show icon', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'h_progress', 'label' => __( 'Cart offer progress', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Milestones shown in the cart drawer. Amount thresholds use the cart subtotal; quantity thresholds use the item count.', 'webgram-core' ) ],
			[ 'id' => 'progress_enabled', 'label' => __( 'Show offer progress', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'progress_free_shipping', 'label' => __( 'Include free shipping threshold from shipping zones', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'milestones', 'label' => __( 'Manual milestones', 'webgram-core' ), 'type' => 'textarea', 'default' => "amount|799|15% OFF|HYP15\nqty|2|Buy 2 @ 799|BUY2\nqty|3|Buy 3 @ 1149|BUY3", 'description' => __( 'One per line: type (amount or qty) | threshold | label | coupon code (optional, auto applied when reached).', 'webgram-core' ) ],
			[ 'id' => 'progress_auto_apply', 'label' => __( 'Auto apply milestone coupons', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
		];
	}

	/** Coupon shown for a product: per-product meta, else the default. Returns a WC_Coupon or null. */
	public function coupon_for( \WC_Product $product ): ?\WC_Coupon {
		$code = (string) get_post_meta( $product->get_parent_id() ?: $product->get_id(), self::META, true );
		if ( '' === $code ) {
			$code = (string) $this->settings()->get( 'default_coupon', '' );
		}
		$code = (string) apply_filters( 'webgram_core/coupons/product_code', $code, $product );
		if ( '' === $code ) {
			return null;
		}
		$coupon = new \WC_Coupon( $code );
		if ( ! $coupon->get_id() || ! self::is_live( $coupon ) ) {
			return null;
		}
		return $coupon;
	}

	/** Pure-ish: published, not expired, usage not exhausted. */
	public static function is_live( \WC_Coupon $coupon ): bool {
		if ( 'publish' !== $coupon->get_status() ) {
			return false;
		}
		$expires = $coupon->get_date_expires();
		if ( $expires && $expires->getTimestamp() < time() ) {
			return false;
		}
		$limit = (int) $coupon->get_usage_limit();
		return ! ( $limit > 0 && (int) $coupon->get_usage_count() >= $limit );
	}

	/** Human headline for a coupon: "FLAT 20% OFF", "FLAT ₹100 OFF", "FREE SHIPPING". */
	public static function headline( string $type, float $amount, bool $free_shipping, string $description = '' ): string {
		if ( '' !== trim( $description ) ) {
			return $description;
		}
		return match ( $type ) {
			'percent'        => sprintf( /* translators: %s: percent */ __( 'FLAT %s%% OFF', 'webgram-core' ), rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) ),
			'fixed_cart', 'fixed_product' => sprintf( /* translators: %s: amount */ __( 'FLAT %s OFF', 'webgram-core' ), wp_strip_all_tags( wc_price( $amount ) ) ),
			default          => $free_shipping ? __( 'FREE SHIPPING', 'webgram-core' ) : __( 'SPECIAL OFFER', 'webgram-core' ),
		};
	}

	public function product_box(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		$coupon = $this->coupon_for( $product );
		if ( ! $coupon ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'coupons' );
		$this->view(
			'box',
			[
				'code'       => $coupon->get_code(),
				'headline'   => self::headline( $coupon->get_discount_type(), (float) $coupon->get_amount(), $coupon->get_free_shipping(), (string) $coupon->get_description() ),
				'copy_label' => (string) $this->settings()->get( 'copy_label', __( 'Copy code', 'webgram-core' ) ),
			] + $this->box_design()
		);
	}

	/** Design and apply settings shared by the product box and the shortcode. */
	public function box_design(): array {
		return [
			'style'       => (string) $this->settings()->get( 'box_style', 'soft' ),
			'color'       => (string) sanitize_hex_color( (string) $this->settings()->get( 'box_color', '#15803d' ) ) ?: '#15803d',
			'show_icon'   => Helpers::bool( $this->settings()->get( 'show_icon', true ) ),
			'show_apply'  => Helpers::bool( $this->settings()->get( 'show_apply', true ) ) && function_exists( 'WC' ),
			'apply_label' => (string) $this->settings()->get( 'apply_label', __( 'Apply', 'webgram-core' ) ),
		];
	}

	public function shortcode( array|string $atts ): string {
		$atts = shortcode_atts( [ 'code' => '', 'product_id' => 0 ], (array) $atts, 'webgram_coupon' );
		if ( $atts['code'] ) {
			$coupon = new \WC_Coupon( sanitize_text_field( (string) $atts['code'] ) );
			if ( ! $coupon->get_id() || ! self::is_live( $coupon ) ) {
				return '';
			}
			\webgram_core()->assets()->enqueue_module( 'coupons' );
			return $this->view( 'box', [ 'code' => $coupon->get_code(), 'headline' => self::headline( $coupon->get_discount_type(), (float) $coupon->get_amount(), $coupon->get_free_shipping(), (string) $coupon->get_description() ), 'copy_label' => (string) $this->settings()->get( 'copy_label', __( 'Copy code', 'webgram-core' ) ) ] + $this->box_design(), false );
		}
		$product = wc_get_product( (int) $atts['product_id'] ?: get_the_ID() );
		if ( ! $product ) {
			return '';
		}
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		ob_start();
		$this->product_box();
		return (string) ob_get_clean();
	}

	public function panel_fields( int $post_id ): void {
		$coupons = get_posts( [ 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ] );
		$options = [ '' => __( 'Use default coupon', 'webgram-core' ) ];
		foreach ( $coupons as $c ) {
			$options[ $c->post_title ] = $c->post_title;
		}
		echo '<div class="options_group">';
		woocommerce_wp_select( [ 'id' => '_wg_coupon', 'label' => __( 'Coupon shown on the product page', 'webgram-core' ), 'options' => $options, 'value' => (string) get_post_meta( $post_id, self::META, true ) ] );
		echo '</div>';
	}

	public function panel_save( int $post_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- ProductPanel verifies its nonce before firing this action.
		if ( isset( $_POST['_wg_coupon'] ) && current_user_can( 'edit_product', $post_id ) ) {
			update_post_meta( $post_id, self::META, sanitize_text_field( wp_unslash( $_POST['_wg_coupon'] ) ) );
		}
		// phpcs:enable
	}
}
