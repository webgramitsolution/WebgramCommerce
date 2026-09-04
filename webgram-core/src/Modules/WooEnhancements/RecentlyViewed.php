<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** Recently viewed products: cookie wg_recent (max 20 ids), shortcode and the product page row. */
final class RecentlyViewed {

	public const COOKIE = 'wg_recent';
	public const MAX    = 20;

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'track' ] );
		add_shortcode( 'webgram_recently_viewed', [ $this, 'shortcode' ] );
		add_action( 'webgram/product/below/recently_viewed', [ $this, 'product_row' ] );
	}

	/** @return int[] most recent first */
	public static function ids(): array {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return [];
		}
		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
		return array_values( array_filter( array_map( 'absint', explode( '|', $raw ) ) ) );
	}

	/** Pure: prepend an id, dedupe, cap. */
	public static function push( array $ids, int $id, int $max = self::MAX ): array {
		$ids = array_values( array_filter( array_map( 'intval', $ids ), static fn( $v ) => $v !== $id && $v > 0 ) );
		array_unshift( $ids, $id );
		return array_slice( $ids, 0, $max );
	}

	public function track(): void {
		if ( ! is_singular( 'product' ) || headers_sent() ) {
			return;
		}
		$ids = self::push( self::ids(), get_queried_object_id() );
		setcookie( self::COOKIE, implode( '|', $ids ), [ 'expires' => time() + 30 * DAY_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'domain' => COOKIE_DOMAIN ?: '', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ] );
		$_COOKIE[ self::COOKIE ] = implode( '|', $ids );
	}

	public function render( int $count, int $exclude = 0, string $title = '' ): string {
		$ids = array_values( array_filter( self::ids(), static fn( $id ) => $id !== $exclude ) );
		$ids = array_slice( $ids, 0, max( 1, $count ) );
		if ( ! $ids ) {
			return '';
		}
		$products = wc_get_products( [ 'include' => $ids, 'limit' => count( $ids ), 'status' => 'publish', 'orderby' => 'include' ] );
		$products = array_filter( $products, static fn( $p ) => $p->is_visible() );
		if ( ! $products ) {
			return '';
		}
		return \webgram_core()->view( 'woo-enhancements/product-row', [ 'products' => $products, 'title' => $title ?: __( 'Recently viewed', 'webgram-core' ), 'class' => 'recently-viewed' ], false );
	}

	public function shortcode( array|string $atts ): string {
		$atts = shortcode_atts( [ 'count' => 5, 'title' => '' ], (array) $atts, 'webgram_recently_viewed' );
		return $this->render( (int) $atts['count'], 0, (string) $atts['title'] );
	}

	public function product_row(): void {
		$count = (int) apply_filters( 'webgram_core/recently_viewed/count', 5 );
		echo $this->render( $count, get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output.
	}
}
