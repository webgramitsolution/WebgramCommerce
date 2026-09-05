<?php
namespace Webgram\Core\Modules\Wishlist;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;
use Webgram\Core\Support\Lists\ListModuleTrait;
use Webgram\Core\Support\Lists\ShareToken;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist: user meta for accounts, signed cookie for guests (merged on login), AJAX toggle, header icon with
 * count, card and product page buttons, [webgram_wishlist] page with share links.
 */
final class Module extends BaseModule {

	use ListModuleTrait;

	public const LIST_KEY = 'wishlist';
	public const LIST_MAX = 50;

	private bool $needed = false;

	public function id(): string {
		return 'wishlist';
	}

	public function name(): string {
		return __( 'Wishlist', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Save products for later, guest support with merge on login, share link.', 'webgram-core' );
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
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'count_fragment' ] );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
		add_filter( 'webgram_core/page_setup/pages', [ $this, 'page_request' ] );
		add_shortcode( 'webgram_wishlist', [ $this, 'shortcode' ] );
		add_action( 'webgram/product_card/actions', [ $this, 'card_button' ], 10 );
		add_action( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );

		$position = (string) $this->settings()->get( 'product_position', 'after_cart' );
		if ( 'after_cart' === $position ) {
			add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'product_button' ], 5 );
		} elseif ( 'gallery' === $position ) {
			add_action( 'webgram/product/gallery_actions', [ $this, 'gallery_button' ] );
		}

		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'wishlist_toggle';
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
			[ 'id' => 'page_id', 'label' => __( 'Wishlist page', 'webgram-core' ), 'type' => 'page', 'default' => 0, 'description' => __( 'Page containing the [webgram_wishlist] shortcode.', 'webgram-core' ) ],
			[ 'id' => 'card_button', 'label' => __( 'Button on product cards', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'product_position', 'label' => __( 'Button on the product page', 'webgram-core' ), 'type' => 'select', 'options' => [ 'after_cart' => __( 'Next to Add to cart', 'webgram-core' ), 'gallery' => __( 'Gallery corner (Webgram theme)', 'webgram-core' ), 'none' => __( 'Hidden', 'webgram-core' ) ], 'default' => 'after_cart' ],
			[ 'id' => 'show_share', 'label' => __( 'Share link on the wishlist page', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'share_days', 'label' => __( 'Share link validity (days)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 365, 'default' => 30 ],
			[ 'id' => 'require_login', 'label' => __( 'Require login to save items', 'webgram-core' ), 'type' => 'checkbox', 'default' => false, 'description' => __( 'Off: guests keep a signed cookie list that merges into their account on login.', 'webgram-core' ) ],
		];
	}

	public function page_request( array $pages ): array {
		$pages['wishlist'] = [ 'module' => 'wishlist', 'setting' => 'page_id', 'title' => __( 'Wishlist', 'webgram-core' ), 'shortcode' => '[webgram_wishlist]', 'label' => __( 'Wishlist', 'webgram-core' ) ];
		return $pages;
	}

	public function widget_definition( array $widgets ): array {
		$widgets['wishlist'] = [ 'title' => __( 'Webgram Wishlist', 'webgram-core' ), 'icon' => 'eicon-heart', 'shortcode' => 'webgram_wishlist', 'controls' => [] ];
		return $widgets;
	}

	public function frontend_data( array $data ): array {
		$data['wishlist'] = [
			'ids'          => $this->list()->ids(),
			'url'          => $this->page_url(),
			'requireLogin' => Helpers::bool( $this->settings()->get( 'require_login', false ) ) && ! is_user_logged_in(),
			'loginUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url(),
			'i18n'         => [
				'added'   => __( 'Added to wishlist', 'webgram-core' ),
				'removed' => __( 'Removed from wishlist', 'webgram-core' ),
				'full'    => __( 'Your wishlist is full.', 'webgram-core' ),
				'login'   => __( 'Please log in to save items.', 'webgram-core' ),
				'add'     => __( 'Add to wishlist', 'webgram-core' ),
				'remove'  => __( 'Remove from wishlist', 'webgram-core' ),
			],
		];
		return $data;
	}

	/** @return array{added: bool, count: int, ids: int[], message: string} */
	public function toggle( int $product_id, string $op = '' ): array {
		if ( Helpers::bool( $this->settings()->get( 'require_login', false ) ) && ! is_user_logged_in() ) {
			return [ 'added' => false, 'count' => 0, 'ids' => [], 'message' => __( 'Please log in to save items.', 'webgram-core' ), 'login' => true ];
		}
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
			$this->track( 'wishlist_add', $product_id );
		}
		$messages = [ 'added' => __( 'Added to wishlist', 'webgram-core' ), 'removed' => __( 'Removed from wishlist', 'webgram-core' ), 'full' => __( 'Your wishlist is full.', 'webgram-core' ) ];
		return [ 'added' => 'added' === $result, 'count' => $this->list()->count(), 'ids' => $this->list()->ids(), 'message' => $messages[ $result ], 'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ) ];
	}

	public function header_element( array $elements ): array {
		$elements[] = [
			'id'        => 'wishlist',
			'label'     => __( 'Wishlist', 'webgram-core' ),
			'icon'      => 'heart',
			'group'     => 'actions',
			'available' => static fn() => true,
			'fields'    => [
				'label'      => [ 'label' => __( 'Label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Wishlist', 'webgram-core' ) ],
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
		$label = (string) ( $settings['label'] ?? __( 'Wishlist', 'webgram-core' ) );
		printf(
			'<a class="wg-icon-btn%s wgc-list-link" href="%s" data-wgc-list-link="wishlist">%s%s%s</a>',
			! empty( $settings['show_label'] ) ? '' : ' wg-icon-btn--no-label',
			esc_url( $this->page_url() ?: '#' ),
			$this->icon_html( 'heart', self::HEART ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG.
			! empty( $settings['show_label'] ) ? '<span class="wg-icon-btn__label">' . esc_html( $label ) . '</span>' : '<span class="wg-sr-only">' . esc_html( $label ) . '</span>',
			! isset( $settings['show_count'] ) || ! empty( $settings['show_count'] ) ? '<span class="wg-icon-btn__count wg-wishlist-count" data-count="' . esc_attr( (string) $count ) . '">' . esc_html( (string) $count ) . '</span>' : ''
		);
	}

	private const HEART = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';

	private function enqueue(): void {
		$this->needed = true;
		\webgram_core()->assets()->enqueue_module( 'lists' );
	}

	public function button( \WC_Product $product, string $variant ): void {
		$this->enqueue();
		$active = $this->list()->has( $product->get_id() );
		$this->view( 'button', [ 'product' => $product, 'active' => $active, 'variant' => $variant, 'icon' => $this->icon_html( 'heart', self::HEART ) ] );
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

	public function gallery_button( $product = null ): void {
		global $product;
		if ( $product instanceof \WC_Product ) {
			$this->button( $product, 'gallery' );
		}
	}

	public function share_url(): string {
		$page = $this->page_url();
		if ( '' === $page || ! $this->list()->count() ) {
			return '';
		}
		$days  = max( 1, (int) $this->settings()->get( 'share_days', 30 ) );
		$token = ShareToken::create( $this->list()->ids(), time() + $days * DAY_IN_SECONDS, fn( string $p ) => \webgram_core()->crypto()->sign( 'wishlist-share|' . $p ) );
		return add_query_arg( 'wg_share', rawurlencode( $token ), $page );
	}

	public function shortcode( $atts = [] ): string {
		$this->enqueue();
		$shared = null;
		$token  = isset( $_GET['wg_share'] ) ? sanitize_text_field( wp_unslash( $_GET['wg_share'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $token ) {
			$shared = ShareToken::parse( $token, time(), fn( string $p ) => \webgram_core()->crypto()->sign( 'wishlist-share|' . $p ) );
		}
		$products = [];
		if ( null !== $shared ) {
			foreach ( $shared as $id ) {
				$p = wc_get_product( $id );
				if ( $p && 'publish' === $p->get_status() ) {
					$products[] = $p;
				}
			}
		} else {
			$products = $this->products();
		}
		return $this->view(
			'table',
			[
				'products'   => $products,
				'shared'     => null !== $shared,
				'invalid'    => '' !== $token && null === $shared,
				'share_url'  => null === $shared && Helpers::bool( $this->settings()->get( 'show_share', true ) ) ? $this->share_url() : '',
				'shop_url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'icon'       => $this->icon_html( 'heart', self::HEART ),
			],
			false
		);
	}
}
