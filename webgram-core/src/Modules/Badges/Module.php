<?php
namespace Webgram\Core\Modules\Badges;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Product badges: rule engine (new arrival days, sale percent, best seller threshold, low stock, out of stock) plus a
 * per-product custom badge (text and color). Rendered on product cards (webgram/product_card/badges) and the PDP
 * gallery (webgram/product/gallery_badges). Style follows the theme badge shape.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'badges';
	}

	public function name(): string {
		return __( 'Product Badges', 'webgram-core' );
	}

	public function description(): string {
		return __( 'New, sale percent, best seller, low stock and custom badges on cards and product pages.', 'webgram-core' );
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
		add_action( 'webgram/product_card/badges', [ $this, 'render' ] );
		add_action( 'webgram/product/gallery_badges', [ $this, 'render' ] );
		add_action( 'webgram_core/product_panel/fields', [ $this, 'panel_fields' ] );
		add_action( 'webgram_core/product_panel/save', [ $this, 'panel_save' ] );
		add_action( 'add_meta_boxes', [ $this, 'metabox_fallback' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'panel_save' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'new_days', 'label' => __( '"New" badge for products newer than (days)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 365, 'default' => 14, 'description' => __( '0 disables the badge.', 'webgram-core' ) ],
			[ 'id' => 'new_text', 'label' => __( '"New" text', 'webgram-core' ), 'type' => 'text', 'default' => __( 'New', 'webgram-core' ) ],
			[ 'id' => 'sale_mode', 'label' => __( 'Sale badge', 'webgram-core' ), 'type' => 'select', 'options' => [ 'theme' => __( 'Theme default ("Save amount")', 'webgram-core' ), 'percent' => __( 'Percent off', 'webgram-core' ), 'text' => __( 'Custom text', 'webgram-core' ), 'none' => __( 'Hidden', 'webgram-core' ) ], 'default' => 'theme', 'description' => __( 'The Webgram theme prints the "Save ₹X" wave badge itself; choose Percent to replace it.', 'webgram-core' ) ],
			[ 'id' => 'sale_text', 'label' => __( 'Sale custom text', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Sale', 'webgram-core' ) ],
			[ 'id' => 'best_threshold', 'label' => __( '"Best seller" when total sales reach', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 100000, 'default' => 50, 'description' => __( '0 disables the badge.', 'webgram-core' ) ],
			[ 'id' => 'best_text', 'label' => __( '"Best seller" text', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Best seller', 'webgram-core' ) ],
			[ 'id' => 'low_stock', 'label' => __( '"Only N left" when stock is at or below', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 100, 'default' => 3 ],
			[ 'id' => 'out_of_stock', 'label' => __( 'Show "Sold out" badge', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'max_badges', 'label' => __( 'Maximum badges per product', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 4, 'default' => 2 ],
		];
	}

	/**
	 * Pure rule evaluation (harness tested).
	 *
	 * @param array $facts    created_ts, is_on_sale, sale_percent, total_sales, stock (int|null), managing_stock, in_stock, custom_text, custom_color
	 * @param array $settings module settings
	 * @return array<int, array{type: string, text: string, color: string}>
	 */
	public static function evaluate( array $facts, array $settings, int $now = 0 ): array {
		$now    = $now ?: time();
		$badges = [];
		if ( ! empty( $facts['custom_text'] ) ) {
			$badges[] = [ 'type' => 'custom', 'text' => (string) $facts['custom_text'], 'color' => (string) ( $facts['custom_color'] ?? '' ) ];
		}
		if ( empty( $facts['in_stock'] ) ) {
			if ( Helpers::bool( $settings['out_of_stock'] ?? true ) ) {
				$badges[] = [ 'type' => 'out', 'text' => __( 'Sold out', 'webgram-core' ), 'color' => '' ];
			}
			return array_slice( $badges, 0, max( 1, (int) ( $settings['max_badges'] ?? 2 ) ) );
		}
		if ( ! empty( $facts['is_on_sale'] ) ) {
			$mode = (string) ( $settings['sale_mode'] ?? 'theme' );
			if ( 'percent' === $mode && (int) ( $facts['sale_percent'] ?? 0 ) > 0 ) {
				$badges[] = [ 'type' => 'sale', 'text' => sprintf( /* translators: %d: percent */ __( '%d%% off', 'webgram-core' ), (int) $facts['sale_percent'] ), 'color' => '' ];
			} elseif ( 'text' === $mode ) {
				$badges[] = [ 'type' => 'sale', 'text' => (string) ( $settings['sale_text'] ?? __( 'Sale', 'webgram-core' ) ), 'color' => '' ];
			}
		}
		$best = (int) ( $settings['best_threshold'] ?? 50 );
		if ( $best > 0 && (int) ( $facts['total_sales'] ?? 0 ) >= $best ) {
			$badges[] = [ 'type' => 'best', 'text' => (string) ( $settings['best_text'] ?? __( 'Best seller', 'webgram-core' ) ), 'color' => '' ];
		}
		$days = (int) ( $settings['new_days'] ?? 14 );
		if ( $days > 0 && ! empty( $facts['created_ts'] ) && ( $now - (int) $facts['created_ts'] ) <= $days * DAY_IN_SECONDS ) {
			$badges[] = [ 'type' => 'new', 'text' => (string) ( $settings['new_text'] ?? __( 'New', 'webgram-core' ) ), 'color' => '' ];
		}
		$low = (int) ( $settings['low_stock'] ?? 3 );
		if ( $low > 0 && ! empty( $facts['managing_stock'] ) && null !== ( $facts['stock'] ?? null ) && (int) $facts['stock'] <= $low && (int) $facts['stock'] > 0 ) {
			$badges[] = [ 'type' => 'low', 'text' => sprintf( /* translators: %d: units left */ __( 'Only %d left', 'webgram-core' ), (int) $facts['stock'] ), 'color' => '' ];
		}
		return array_slice( $badges, 0, max( 1, (int) ( $settings['max_badges'] ?? 2 ) ) );
	}

	public function badges_for( \WC_Product $product ): array {
		$created = $product->get_date_created();
		$regular = (float) ( $product->is_type( 'variable' ) ? $product->get_variation_regular_price( 'max' ) : $product->get_regular_price() );
		$sale    = (float) ( $product->is_type( 'variable' ) ? $product->get_variation_sale_price( 'min' ) : $product->get_sale_price() );
		$facts   = [
			'created_ts'     => $created ? $created->getTimestamp() : 0,
			'is_on_sale'     => $product->is_on_sale(),
			'sale_percent'   => $regular > 0 && $sale > 0 && $sale < $regular ? (int) round( ( $regular - $sale ) / $regular * 100 ) : 0,
			'total_sales'    => (int) $product->get_total_sales(),
			'stock'          => $product->get_stock_quantity(),
			'managing_stock' => $product->managing_stock(),
			'in_stock'       => $product->is_in_stock(),
			'custom_text'    => (string) get_post_meta( $product->get_id(), '_wg_badge_text', true ),
			'custom_color'   => (string) get_post_meta( $product->get_id(), '_wg_badge_color', true ),
		];
		$badges = self::evaluate( (array) apply_filters( 'webgram_core/badges/facts', $facts, $product ), $this->settings()->all() + $this->defaults() );
		return (array) apply_filters( 'webgram_core/badges/list', $badges, $product );
	}

	private function defaults(): array {
		$d = [];
		foreach ( $this->settings_fields() as $f ) {
			$d[ $f['id'] ] = $f['default'] ?? '';
		}
		return $d;
	}

	public function render( \WC_Product $product ): void {
		$badges = $this->badges_for( $product );
		if ( ! $badges ) {
			return;
		}
		$this->view( 'list', [ 'badges' => $badges ] );
	}

	public function panel_fields( int $post_id ): void {
		echo '<div class="options_group">';
		woocommerce_wp_text_input( [ 'id' => '_wg_badge_text', 'label' => __( 'Custom badge text', 'webgram-core' ), 'desc_tip' => true, 'description' => __( 'Shown first on cards and the product page.', 'webgram-core' ) ] );
		woocommerce_wp_text_input( [ 'id' => '_wg_badge_color', 'label' => __( 'Custom badge color', 'webgram-core' ), 'placeholder' => '#a0181f', 'class' => 'wgc-color-field' ] );
		echo '</div>';
	}

	/** If WooEnhancements (which owns the panel) is off, show the fields in a small metabox instead. */
	public function metabox_fallback(): void {
		if ( ! $this->plugin->modules()->is_active( 'woo_enhancements' ) ) {
			add_meta_box( 'wg_badge', __( 'Webgram badge', 'webgram-core' ), function ( \WP_Post $post ) {
 wp_nonce_field( 'wg_badge_save', 'wg_badge_nonce' );
$this->panel_fields( $post->ID );
}, 'product', 'side' );
		}
	}

	public function panel_save( int $post_id ): void {
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		$ok = ( isset( $_POST['webgram_product_panel_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['webgram_product_panel_nonce'] ) ), 'webgram_product_panel' ) )
			|| ( isset( $_POST['wg_badge_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wg_badge_nonce'] ) ), 'wg_badge_save' ) );
		if ( ! $ok || ! array_key_exists( '_wg_badge_text', $_POST ) ) {
			return;
		}
		update_post_meta( $post_id, '_wg_badge_text', sanitize_text_field( wp_unslash( $_POST['_wg_badge_text'] ) ) );
		$color = isset( $_POST['_wg_badge_color'] ) ? sanitize_text_field( wp_unslash( $_POST['_wg_badge_color'] ) ) : '';
		update_post_meta( $post_id, '_wg_badge_color', (string) sanitize_hex_color( $color ) );
	}
}
