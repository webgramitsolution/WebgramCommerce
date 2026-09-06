<?php
namespace Webgram\Core\Support\Lists;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Shared plumbing for the Wishlist and Compare modules: storage resolution, merge on login, page URL, fragments.
 * The using class must define LIST_KEY (user meta key / cookie name suffix) and LIST_MAX.
 */
trait ListModuleTrait {


	private ?ProductList $list = null;

	public function list(): ProductList {
		if ( null === $this->list ) {
			$this->list = new ProductList( $this->storage_for( get_current_user_id() ), static::LIST_MAX );
		}
		return $this->list;
	}

	private function storage_for( int $user_id ): StorageInterface {
		if ( $user_id > 0 ) {
			return new UserMetaStorage( $user_id, '_wg_' . static::LIST_KEY );
		}
		return new CookieStorage( 'wg_' . static::LIST_KEY, fn( string $p ) => \webgram_core()->crypto()->sign( static::LIST_KEY . '|' . $p ) );
	}

	/** wp_login: fold the guest cookie into the account list, then drop the cookie. */
	public function merge_on_login( string $login, \WP_User $user ): void {
		$cookie = new CookieStorage( 'wg_' . static::LIST_KEY, fn( string $p ) => \webgram_core()->crypto()->sign( static::LIST_KEY . '|' . $p ) );
		$guest  = $cookie->get();
		if ( ! $guest ) {
			return;
		}
		( new ProductList( new UserMetaStorage( (int) $user->ID, '_wg_' . static::LIST_KEY ), static::LIST_MAX ) )->merge( $guest );
		$cookie->set( [] );
		$this->list = null;
	}

	public function page_url(): string {
		$page = (int) $this->settings()->get( 'page_id', 0 );
		$url  = $page > 0 ? (string) get_permalink( $page ) : '';
		return (string) apply_filters( 'webgram_core/' . static::LIST_KEY . '/page_url', $url );
	}

	/** Theme bottom navbar and other theme links ask for the page URL by list id. */
	public function link_url( string $url, string $id ): string {
		return static::LIST_KEY === $id ? $this->page_url() : $url;
	}

	public function count_fragment( array $fragments ): array {
		$count = (string) $this->list()->count();
		$key   = static::LIST_KEY;
		$fragments[ '.wg-icon-btn__count.wg-' . $key . '-count' ]   = '<span class="wg-icon-btn__count wg-' . $key . '-count" data-count="' . esc_attr( $count ) . '">' . esc_html( $count ) . '</span>';
		$fragments[ '.wg-bottom-nav__badge.wg-' . $key . '-count' ] = '<span class="wg-bottom-nav__badge wg-' . $key . '-count" data-count="' . esc_attr( $count ) . '">' . esc_html( $count ) . '</span>';
		$fragments[ '.wg-drawer__count.wg-' . $key . '-count' ]     = '<span class="wg-drawer__count wg-' . $key . '-count" data-count="' . esc_attr( $count ) . '">' . esc_html( $count ) . '</span>';
		return $fragments;
	}

	/** Theme bottom navbar badge and drawer link ask for the current count by list id (webgram/header/link_count). */
	public function link_count( int $count, string $id ): int {
		return static::LIST_KEY === $id ? $this->list()->count() : $count;
	}

	/** Link with count inside the theme mobile drawer (webgram/mobile_menu/account_links). */
	public function drawer_link(): void {
		$url = $this->page_url();
		if ( '' === $url ) {
			return;
		}
		$count = (string) $this->list()->count();
		printf(
			'<li><a href="%s" data-wgc-list-link="%s">%s<span>%s</span> <span class="wg-drawer__count wg-%s-count" data-count="%s">%s</span></a></li>',
			esc_url( $url ),
			esc_attr( static::LIST_KEY ),
			$this->icon_html( 'wishlist' === static::LIST_KEY ? 'heart' : 'compare', $this->list_icon() ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG.
			esc_html( $this->list_label() ),
			esc_attr( static::LIST_KEY ),
			esc_attr( $count ),
			esc_html( $count )
		);
	}

	/** Inline SVG used when the theme has no icon set. */
	abstract protected function list_icon(): string;

	/** Human label for links, overridable per module. */
	protected function list_label(): string {
		return 'wishlist' === static::LIST_KEY ? __( 'Wishlist', 'webgram-core' ) : __( 'Compare', 'webgram-core' );
	}

	/** @return \WC_Product[] */
	public function products(): array {
		$out = [];
		foreach ( $this->list()->ids() as $id ) {
			$product = wc_get_product( $id );
			if ( $product && 'publish' === $product->get_status() ) {
				$out[] = $product;
			}
		}
		return $out;
	}

	private function icon_html( string $name, string $fallback_svg ): string {
		if ( function_exists( 'webgram_icon' ) ) {
			return (string) webgram_icon( $name, '', false );
		}
		return $fallback_svg;
	}

	private function track( string $event, int $product_id ): void {
		do_action( 'webgram_core/analytics/event', $event, [ 'product_id' => $product_id ], static::LIST_KEY );
	}

	private function css( string $name, string $extra = '' ): string {
		return Helpers::css_class( $name, $extra );
	}
}
