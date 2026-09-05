<?php
namespace Webgram\Core\Modules\Compare;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;
use Webgram\Core\Support\Lists\ListModuleTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Compare: up to 4 products in user meta or a signed cookie, AJAX toggle, header icon, card buttons, floating
 * compare bar, [webgram_compare] page with a sticky first column and difference highlighting.
 */
final class Module extends BaseModule {

	use ListModuleTrait;

	public const LIST_KEY = 'compare';
	public const LIST_MAX = 4;

	protected function list_icon(): string {
		return self::ICON;
	}

	private const ICON = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>';

	public function id(): string {
		return 'compare';
	}

	public function name(): string {
		return __( 'Compare', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Side by side product comparison, up to 4 products, differences highlighted.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 4;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'wp_login', [ $this, 'merge_on_login' ], 10, 2 );
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_filter( 'webgram/header/elements', [ $this, 'header_element' ] );
		add_filter( 'webgram/header/link_url', [ $this, 'link_url' ], 10, 2 );
		add_filter( 'webgram/header/link_count', [ $this, 'link_count' ], 10, 2 );
		add_action( 'webgram/mobile_menu/account_links', [ $this, 'drawer_link' ] );
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'count_fragment' ] );
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'bar_fragment' ] );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
		add_filter( 'webgram_core/page_setup/pages', [ $this, 'page_request' ] );
		add_shortcode( 'webgram_compare', [ $this, 'shortcode' ] );
		add_action( 'webgram/product_card/actions', [ $this, 'card_button' ], 20 );
		add_action( 'wp_footer', [ $this, 'bar' ], 35 );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		if ( Helpers::bool( $this->settings()->get( 'product_button', true ) ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'product_button' ], 6 );
		}

		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'compare_toggle';
			}
			protected function fields(): array {
				return [ 'product_id' => 'int', 'op' => 'key' ];
			}
			protected function handle( array $input ): void {
				$this->success( $this->module->toggle( (int) $input['product_id'], (string) $input['op'] ) );
			}
		} )->register();
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-lists', 'css/lists.css' );
		$assets->script( 'webgram-core-lists', 'js/lists.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'page_id', 'label' => __( 'Compare page', 'webgram-core' ), 'type' => 'page', 'default' => 0, 'description' => __( 'Page containing the [webgram_compare] shortcode.', 'webgram-core' ) ],
			[ 'id' => 'card_button', 'label' => __( 'Button on product cards', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'product_button', 'label' => __( 'Button on the product page', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_bar', 'label' => __( 'Floating compare bar', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Small bar at the bottom of the screen while products are selected.', 'webgram-core' ) ],
			[ 'id' => 'highlight', 'label' => __( 'Highlight differences by default', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'rows', 'label' => __( 'Rows', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'row_price', 'label' => __( 'Price', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'row_rating', 'label' => __( 'Rating', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'row_stock', 'label' => __( 'Availability', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'row_sku', 'label' => __( 'SKU', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'row_description', 'label' => __( 'Short description', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'row_dimensions', 'label' => __( 'Weight and dimensions', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'row_attributes', 'label' => __( 'Product attributes', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
		];
	}

	public function page_request( array $pages ): array {
		$pages['compare'] = [ 'module' => 'compare', 'setting' => 'page_id', 'title' => __( 'Compare', 'webgram-core' ), 'shortcode' => '[webgram_compare]', 'label' => __( 'Compare', 'webgram-core' ) ];
		return $pages;
	}

	public function widget_definition( array $widgets ): array {
		$widgets['compare'] = [ 'title' => __( 'Webgram Compare', 'webgram-core' ), 'icon' => 'eicon-exchange', 'shortcode' => 'webgram_compare', 'controls' => [] ];
		return $widgets;
	}

	public function frontend_data( array $data ): array {
		$data['compare'] = [
			'ids'  => $this->list()->ids(),
			'max'  => self::LIST_MAX,
			'url'  => $this->page_url(),
			'i18n' => [
				'added'   => __( 'Added to compare', 'webgram-core' ),
				'removed' => __( 'Removed from compare', 'webgram-core' ),
				'full'    => sprintf( /* translators: %d: maximum products */ __( 'You can compare up to %d products.', 'webgram-core' ), self::LIST_MAX ),
				'add'     => __( 'Add to compare', 'webgram-core' ),
				'remove'  => __( 'Remove from compare', 'webgram-core' ),
				'compare' => __( 'Compare', 'webgram-core' ),
			],
		];
		return $data;
	}

	public function toggle( int $product_id, string $op = '' ): array {
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			return [ 'added' => false, 'count' => $this->list()->count(), 'ids' => $this->list()->ids(), 'message' => __( 'Product not found.', 'webgram-core' ) ];
		}
		if ( 'add' === $op ) {
			$result = $this->list()->add( $product_id ) ? 'added' : 'full';
		} elseif ( 'remove' === $op ) {
			$this->list()->remove( $product_id );
			$result = 'removed';
		} else {
			$result = $this->list()->toggle( $product_id );
		}
		if ( 'added' === $result ) {
			$this->track( 'compare_add', $product_id );
		}
		$messages = [ 'added' => __( 'Added to compare', 'webgram-core' ), 'removed' => __( 'Removed from compare', 'webgram-core' ), 'full' => sprintf( /* translators: %d: maximum products */ __( 'You can compare up to %d products.', 'webgram-core' ), self::LIST_MAX ) ];
		return [ 'added' => 'added' === $result, 'full' => 'full' === $result, 'count' => $this->list()->count(), 'ids' => $this->list()->ids(), 'message' => $messages[ $result ], 'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ) ];
	}

	public function header_element( array $elements ): array {
		$elements[] = [
			'id'        => 'compare',
			'label'     => __( 'Compare', 'webgram-core' ),
			'icon'      => 'compare',
			'group'     => 'actions',
			'available' => static fn() => true,
			'fields'    => [
				'label'      => [ 'label' => __( 'Label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Compare', 'webgram-core' ) ],
				'show_label' => [ 'label' => __( 'Show label under icon', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
				'show_count' => [ 'label' => __( 'Item count badge', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
			],
			'render'    => function ( array $settings ) {
				$this->header_icon( $settings );
			},
		];
		return $elements;
	}

	public function header_icon( array $settings = [] ): void {
		$this->enqueue();
		$count = $this->list()->count();
		$label = (string) ( $settings['label'] ?? __( 'Compare', 'webgram-core' ) );
		printf(
			'<a class="wg-icon-btn%s wgc-list-link" href="%s" data-wgc-list-link="compare">%s%s%s</a>',
			! empty( $settings['show_label'] ) ? '' : ' wg-icon-btn--no-label',
			esc_url( $this->page_url() ?: '#' ),
			$this->icon_html( 'compare', self::ICON ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG.
			! empty( $settings['show_label'] ) ? '<span class="wg-icon-btn__label">' . esc_html( $label ) . '</span>' : '<span class="wg-sr-only">' . esc_html( $label ) . '</span>',
			! isset( $settings['show_count'] ) || ! empty( $settings['show_count'] ) ? '<span class="wg-icon-btn__count wg-compare-count" data-count="' . esc_attr( (string) $count ) . '">' . esc_html( (string) $count ) . '</span>' : ''
		);
	}

	private bool $needed = false;

	private function enqueue(): void {
		$this->needed = true;
		\webgram_core()->assets()->enqueue_module( 'lists' );
	}

	public function button( \WC_Product $product, string $variant ): void {
		$this->enqueue();
		$this->view( 'button', [ 'product' => $product, 'active' => $this->list()->has( $product->get_id() ), 'variant' => $variant, 'icon' => $this->icon_html( 'compare', self::ICON ) ] );
	}

	public function card_button( \WC_Product $product ): void {
		if ( Helpers::bool( $this->settings()->get( 'card_button', true ) ) ) {
			$this->button( $product, 'card' );
		}
	}

	public function product_button(): void {
		global $product;
		if ( $product instanceof \WC_Product ) {
			$this->button( $product, 'product' );
		}
	}

	public function bar_html(): string {
		return $this->view( 'bar', [ 'products' => $this->products(), 'url' => $this->page_url(), 'max' => self::LIST_MAX ], false );
	}

	public function bar(): void {
		if ( is_admin() || ! $this->needed || ! Helpers::bool( $this->settings()->get( 'show_bar', true ) ) ) {
			return;
		}
		echo $this->bar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output.
	}

	public function bar_fragment( array $fragments ): array {
		if ( Helpers::bool( $this->settings()->get( 'show_bar', true ) ) ) {
			$fragments['[data-wgc-compare-bar]'] = $this->bar_html();
		}
		return $fragments;
	}

	public function shortcode( $atts = [] ): string {
		$this->enqueue();
		$products = $this->products();
		$show     = [];
		foreach ( [ 'price', 'rating', 'stock', 'sku', 'description', 'dimensions', 'attributes' ] as $row ) {
			$show[ $row ] = Helpers::bool( $this->settings()->get( 'row_' . $row, 'sku' !== $row ) );
		}
		return $this->view(
			'table',
			[
				'products'  => $products,
				'rows'      => $products ? Table::rows( $products, $show ) : [],
				'highlight' => Helpers::bool( $this->settings()->get( 'highlight', true ) ),
				'shop_url'  => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'max'       => self::LIST_MAX,
				'icon'      => $this->icon_html( 'compare', self::ICON ),
			],
			false
		);
	}
}
