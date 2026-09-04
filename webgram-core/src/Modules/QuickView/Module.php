<?php
namespace Webgram\Core\Modules\QuickView;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Quick view: card action button opens a modal fetched through wc-ajax=webgram_quick_view (gallery, title, price,
 * rating, short description, variations form, add to cart). WooCommerce variation scripts run inside the modal.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'quick_view';
	}

	public function name(): string {
		return __( 'Quick View', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Product quick view modal from product cards.', 'webgram-core' );
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
		add_action( 'webgram/product_card/actions', [ $this, 'button' ], 30 );
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_action( 'wp_footer', [ $this, 'modal_shell' ], 40 );
		( new class( $this ) extends AjaxHandler {
			public function __construct( private Module $module ) {}
			protected function action(): string {
				return 'quick_view';
			}
			protected function fields(): array {
				return [ 'product_id' => 'int' ];
			}
			protected function handle( array $input ): void {
				$product = wc_get_product( (int) $input['product_id'] );
				if ( ! $product || ! $product->is_visible() ) {
					$this->error( __( 'Product not found.', 'webgram-core' ), 404 );
				}
				$this->success( [ 'html' => $this->module->render( $product ), 'title' => $product->get_name(), 'url' => $product->get_permalink() ] );
			}
		} )->register();
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-quick-view', 'css/quick-view.css' );
		$assets->script( 'webgram-core-quick-view', 'js/quick-view.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'show_gallery', 'label' => __( 'Show gallery thumbnails', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_description', 'label' => __( 'Show short description', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_meta', 'label' => __( 'Show SKU and categories', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
		];
	}

	private bool $needed = false;

	public function button( \WC_Product $product ): void {
		$this->needed = true;
		\webgram_core()->assets()->enqueue_module( 'quick_view' );
		printf(
			'<button type="button" class="%s" data-wgc-quick-view="%d" aria-label="%s" title="%s"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>',
			esc_attr( Helpers::css_class( 'quick-view-btn', 'wg-card__action' ) ),
			(int) $product->get_id(),
			esc_attr( sprintf( /* translators: %s: product name */ __( 'Quick view %s', 'webgram-core' ), $product->get_name() ) ),
			esc_attr__( 'Quick view', 'webgram-core' )
		);
	}

	public function modal_shell(): void {
		if ( ! $this->needed || is_admin() ) {
			return;
		}
		$this->view( 'shell' );
	}

	public function render( \WC_Product $product ): string {
		$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['post']    = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );
		$html = $this->view(
			'modal',
			[
				'product'          => $product,
				'images'           => array_values( array_filter( array_merge( [ (int) $product->get_image_id() ], Helpers::bool( $this->settings()->get( 'show_gallery', true ) ) ? array_map( 'intval', (array) $product->get_gallery_image_ids() ) : [] ) ) ),
				'show_description' => Helpers::bool( $this->settings()->get( 'show_description', true ) ),
				'show_meta'        => Helpers::bool( $this->settings()->get( 'show_meta', false ) ),
			],
			false
		);
		wp_reset_postdata();
		return $html;
	}
}
