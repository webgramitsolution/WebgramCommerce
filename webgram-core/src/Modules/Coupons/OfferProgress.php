<?php
namespace Webgram\Core\Modules\Coupons;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Cart offer progress: milestones from settings (amount or quantity thresholds mapped to coupon codes) plus the
 * free shipping threshold from shipping zones when available. Computes progress server-side from cart totals.
 */
final class OfferProgress {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/cart/before_items', [ $this, 'render' ] );
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'fragment' ] );
		if ( Helpers::bool( $this->module->settings()->get( 'progress_auto_apply', false ) ) ) {
			add_action( 'woocommerce_before_calculate_totals', [ $this, 'auto_apply' ], 20 );
		}
	}

	/**
	 * Pure parser for the milestones setting.
	 *
	 * @return array<int, array{type: string, threshold: float, label: string, code: string}>
	 */
	public static function parse( string $text ): array {
		$out = [];
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) ?: [] as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) < 3 ) {
				continue;
			}
			$type = 'qty' === strtolower( $parts[0] ) ? 'qty' : 'amount';
			$threshold = (float) $parts[1];
			if ( $threshold <= 0 || '' === $parts[2] ) {
				continue;
			}
			$out[] = [ 'type' => $type, 'threshold' => $threshold, 'label' => sanitize_text_field( $parts[2] ), 'code' => sanitize_text_field( $parts[3] ?? '' ) ];
		}
		usort( $out, static fn( $a, $b ) => $a['threshold'] <=> $b['threshold'] );
		return $out;
	}

	/**
	 * Pure progress computation.
	 *
	 * @return array{milestones: array, percent: int, next: array|null, achieved: array|null, message: string}
	 */
	public static function compute( array $milestones, float $subtotal, int $quantity ): array {
		$achieved = null;
		$next     = null;
		$max_amt  = 0.0;
		foreach ( $milestones as &$m ) {
			$value       = 'qty' === $m['type'] ? (float) $quantity : $subtotal;
			$m['done']   = $value >= $m['threshold'];
			$m['remain'] = max( 0, $m['threshold'] - $value );
			if ( $m['done'] ) {
				$achieved = $m;
			} elseif ( null === $next ) {
				$next = $m;
			}
			if ( 'amount' === $m['type'] ) {
				$max_amt = max( $max_amt, $m['threshold'] );
			}
		}
		unset( $m );
		$percent = 0;
		if ( $milestones ) {
			$done_count = count( array_filter( $milestones, static fn( $m ) => $m['done'] ) );
			$total      = count( $milestones );
			if ( $next ) {
				$value   = 'qty' === $next['type'] ? (float) $quantity : $subtotal;
				$prev    = $achieved ? ( 'qty' === $achieved['type'] ? $achieved['threshold'] : $achieved['threshold'] ) : 0.0;
				$span    = max( 0.01, $next['threshold'] - ( $achieved && $achieved['type'] === $next['type'] ? $prev : 0 ) );
				$within  = max( 0, min( 1, ( $value - ( $achieved && $achieved['type'] === $next['type'] ? $prev : 0 ) ) / $span ) );
				$percent = (int) round( ( $done_count + $within ) / $total * 100 );
			} else {
				$percent = 100;
			}
		}
		$message = '';
		if ( $achieved ) {
			$message = sprintf( /* translators: %s: milestone label */ __( 'Unlocked %s', 'webgram-core' ), $achieved['label'] ) . ( $achieved['code'] ? ' | ' . sprintf( /* translators: %s: coupon code */ __( 'Code: %s', 'webgram-core' ), $achieved['code'] ) : '' );
		}
		if ( $next ) {
			$more     = 'qty' === $next['type'] ? sprintf( /* translators: 1: items, 2: label */ _n( 'Add %1$d more to get %2$s', 'Add %1$d more to get %2$s', (int) ceil( $next['remain'] ), 'webgram-core' ), (int) ceil( $next['remain'] ), $next['label'] ) : sprintf( /* translators: 1: amount, 2: label */ __( 'Add %1$s more to get %2$s', 'webgram-core' ), wp_strip_all_tags( wc_price( $next['remain'] ) ), $next['label'] );
			$message .= ( $message ? ' ' . __( 'Or', 'webgram-core' ) . ' ' : '' ) . $more;
		}
		return [ 'milestones' => $milestones, 'percent' => min( 100, $percent ), 'next' => $next, 'achieved' => $achieved, 'message' => $message ];
	}

	/** Free shipping minimum from the first zone method that has one (best effort, cached). */
	public static function free_shipping_threshold(): float {
		return (float) \webgram_core()->cache()->remember(
			'free_shipping_min',
			HOUR_IN_SECONDS,
			static function () {
				if ( ! class_exists( '\WC_Shipping_Zones' ) ) {
					return 0.0;
				}
				$zones   = \WC_Shipping_Zones::get_zones();
				$zones[] = [ 'shipping_methods' => ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods( true ) ];
				foreach ( $zones as $zone ) {
					foreach ( (array) ( $zone['shipping_methods'] ?? [] ) as $method ) {
						if ( 'free_shipping' === $method->id && in_array( $method->get_option( 'requires' ), [ 'min_amount', 'either' ], true ) ) {
							return (float) $method->get_option( 'min_amount' );
						}
					}
				}
				return 0.0;
			},
			'coupons'
		);
	}

	public function milestones(): array {
		$list = self::parse( (string) $this->module->settings()->get( 'milestones', '' ) );
		if ( Helpers::bool( $this->module->settings()->get( 'progress_free_shipping', true ) ) ) {
			$min = self::free_shipping_threshold();
			if ( $min > 0 ) {
				$list[] = [ 'type' => 'amount', 'threshold' => $min, 'label' => __( 'Free shipping', 'webgram-core' ), 'code' => '' ];
				usort( $list, static fn( $a, $b ) => $a['threshold'] <=> $b['threshold'] );
			}
		}
		return (array) apply_filters( 'webgram_core/coupons/milestones', $list );
	}

	public function data(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || ! Helpers::bool( $this->module->settings()->get( 'progress_enabled', true ) ) ) {
			return [ 'milestones' => [], 'percent' => 0, 'next' => null, 'achieved' => null, 'message' => '' ];
		}
		return self::compute( $this->milestones(), (float) WC()->cart->get_subtotal(), (int) WC()->cart->get_cart_contents_count() );
	}

	public function render(): void {
		$data = $this->data();
		if ( ! $data['milestones'] ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'coupons' );
		\webgram_core()->view( 'coupons/progress', $data );
	}

	public function fragment( array $fragments ): array {
		$data = $this->data();
		if ( $data['milestones'] ) {
			$fragments['.wgc-progress'] = \webgram_core()->view( 'coupons/progress', $data, false );
		}
		return $fragments;
	}

	public function auto_apply( \WC_Cart $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		$data = self::compute( $this->milestones(), (float) $cart->get_subtotal(), (int) $cart->get_cart_contents_count() );
		foreach ( $data['milestones'] as $m ) {
			if ( ! $m['code'] ) {
				continue;
			}
			$applied = in_array( strtolower( $m['code'] ), array_map( 'strtolower', $cart->get_applied_coupons() ), true );
			if ( $m['done'] && ! $applied && $m === $data['achieved'] ) {
				$cart->apply_coupon( $m['code'] );
			} elseif ( ! $m['done'] && $applied ) {
				$cart->remove_coupon( $m['code'] );
			}
		}
	}
}
