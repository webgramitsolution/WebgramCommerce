<?php
namespace Webgram\Core\Modules\AiAssistant;

use Webgram\Core\Support\ProductQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Assistant tools (function calling): search_products, best_sellers, active_coupons, order_status, store_info.
 * Third parties add tools through webgram_core/ai/tools. Product results always come from published, catalog
 * visible products. order_status only answers for the logged in owner of the order.
 */
final class Tools {

	public function __construct( private Module $module ) {}

	/** @return array<string, array{name: string, description: string, parameters: array, handler: callable}> */
	public function all(): array {
		$tools = [
			'search_products' => [
				'name'        => 'search_products',
				'description' => 'Search the store catalog. Use for any product question (what do you sell, find X, gifts under a price, items in a category). Returns up to 6 products with prices and links.',
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'query'     => [ 'type' => 'string', 'description' => 'Keywords from the shopper request, in the store language.' ],
						'min_price' => [ 'type' => 'number', 'description' => 'Minimum price in store currency.' ],
						'max_price' => [ 'type' => 'number', 'description' => 'Maximum price in store currency.' ],
						'category'  => [ 'type' => 'string', 'description' => 'Category name or slug when the shopper names one.' ],
						'sort'      => [ 'type' => 'string', 'enum' => [ 'relevance', 'price_asc', 'price_desc', 'newest', 'rating', 'popularity' ] ],
					],
					'required'   => [ 'query' ],
				],
				'handler'     => [ $this, 'search_products' ],
			],
			'best_sellers'    => [
				'name'        => 'best_sellers',
				'description' => 'Most popular products in the store, optionally within a category.',
				'parameters'  => [ 'type' => 'object', 'properties' => [ 'limit' => [ 'type' => 'integer', 'minimum' => 1, 'maximum' => 6 ], 'category' => [ 'type' => 'string' ] ], 'required' => [] ],
				'handler'     => [ $this, 'best_sellers' ],
			],
			'active_coupons'  => [
				'name'        => 'active_coupons',
				'description' => 'Currently valid public coupon codes and offers.',
				'parameters'  => [ 'type' => 'object', 'properties' => [], 'required' => [] ],
				'handler'     => [ $this, 'active_coupons' ],
			],
			'order_status'    => [
				'name'        => 'order_status',
				'description' => 'Status of one of the logged in shopper\'s own orders by order number. Works only when the shopper is logged in.',
				'parameters'  => [ 'type' => 'object', 'properties' => [ 'order_id' => [ 'type' => 'string', 'description' => 'Order number as shown to the customer.' ] ], 'required' => [ 'order_id' ] ],
				'handler'     => [ $this, 'order_status' ],
			],
			'store_info'      => [
				'name'        => 'store_info',
				'description' => 'Shipping, returns, payment and contact details of the store.',
				'parameters'  => [ 'type' => 'object', 'properties' => [], 'required' => [] ],
				'handler'     => [ $this, 'store_info' ],
			],
		];
		return array_filter( (array) apply_filters( 'webgram_core/ai/tools', $tools, $this->module ), static fn( $t ) => is_array( $t ) && ! empty( $t['name'] ) && is_callable( $t['handler'] ?? null ) );
	}

	/** Provider facing schemas (no handlers). */
	public function schemas(): array {
		return array_values( array_map( static fn( array $t ) => [ 'name' => $t['name'], 'description' => $t['description'], 'parameters' => $t['parameters'] ], $this->all() ) );
	}

	/** Pure: coerce arguments to the declared JSON schema types (strings, numbers, integers, enums). */
	public static function sanitize_arguments( array $schema, array $args ): array {
		$out = [];
		foreach ( (array) ( $schema['properties'] ?? [] ) as $key => $prop ) {
			if ( ! array_key_exists( $key, $args ) || null === $args[ $key ] ) {
				continue;
			}
			$value = $args[ $key ];
			switch ( $prop['type'] ?? 'string' ) {
				case 'number':
					$out[ $key ] = is_numeric( $value ) ? (float) $value : null;
					break;
				case 'integer':
					$n = is_numeric( $value ) ? (int) $value : null;
					if ( null !== $n && isset( $prop['minimum'] ) ) {
						$n = max( (int) $prop['minimum'], $n );
					}
					if ( null !== $n && isset( $prop['maximum'] ) ) {
						$n = min( (int) $prop['maximum'], $n );
					}
					$out[ $key ] = $n;
					break;
				case 'boolean':
					$out[ $key ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					break;
				default:
					$s = mb_substr( trim( wp_strip_all_tags( (string) ( is_array( $value ) ? implode( ' ', $value ) : $value ) ) ), 0, 200 );
					if ( isset( $prop['enum'] ) && ! in_array( $s, (array) $prop['enum'], true ) ) {
						$s = (string) ( $prop['enum'][0] ?? '' );
					}
					$out[ $key ] = $s;
			}
			if ( null === $out[ $key ] ) {
				unset( $out[ $key ] );
			}
		}
		return $out;
	}

	/** Run a tool by name. Returns a JSON-serializable array, never throws to the caller. */
	public function run( string $name, array $arguments, array $context ): array {
		$tool = $this->all()[ $name ] ?? null;
		if ( ! $tool ) {
			return [ 'error' => 'unknown_tool' ];
		}
		try {
			$args = self::sanitize_arguments( (array) $tool['parameters'], $arguments );
			return (array) call_user_func( $tool['handler'], $args, $context );
		} catch ( \Throwable $e ) {
			\webgram_core()->logger()->error( 'AI tool failed', [ 'tool' => $name, 'error' => $e->getMessage() ] );
			return [ 'error' => 'tool_failed' ];
		}
	}

	/** @return array<int, array{id: int, title: string, price: string, price_html: string, image: string, url: string, add_to_cart_url: string, in_stock: bool}> */
	public static function product_cards( array $ids, int $limit = 6 ): array {
		$out = [];
		foreach ( $ids as $id ) {
			$p = wc_get_product( (int) $id );
			if ( ! $p || 'publish' !== $p->get_status() || ! $p->is_visible() ) {
				continue;
			}
			$out[] = [
				'id'              => $p->get_id(),
				'title'           => $p->get_name(),
				'price'           => wp_strip_all_tags( wc_price( (float) $p->get_price() ) ),
				'price_html'      => $p->get_price_html(),
				'image'           => (string) wp_get_attachment_image_url( (int) $p->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
				'url'             => $p->get_permalink(),
				'add_to_cart_url' => $p->is_purchasable() && $p->is_in_stock() ? $p->add_to_cart_url() : '',
				'in_stock'        => $p->is_in_stock(),
			];
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return $out;
	}

	public function search_products( array $a ): array {
		$query = [ 'status' => 'publish', 'limit' => 6, 'return' => 'ids', 'visibility' => 'catalog', 's' => (string) ( $a['query'] ?? '' ), 'stock_status' => 'instock' ];
		if ( ! empty( $a['category'] ) ) {
			$term = get_term_by( 'slug', sanitize_title( (string) $a['category'] ), 'product_cat' ) ?: get_term_by( 'name', (string) $a['category'], 'product_cat' );
			if ( $term instanceof \WP_Term ) {
				$query['category'] = [ $term->slug ];
			}
		}
		if ( isset( $a['min_price'] ) || isset( $a['max_price'] ) ) {
			$query['meta_query'] = [ [ 'key' => '_price', 'value' => [ (float) ( $a['min_price'] ?? 0 ), (float) ( $a['max_price'] ?? 999999999 ) ], 'compare' => 'BETWEEN', 'type' => 'DECIMAL(12,2)' ] ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
		switch ( $a['sort'] ?? 'relevance' ) {
			case 'price_asc':
				$query['orderby'] = 'price';
				$query['order']   = 'ASC';
				break;
			case 'price_desc':
				$query['orderby'] = 'price';
				$query['order']   = 'DESC';
				break;
			case 'newest':
				$query['orderby'] = 'date';
				break;
			case 'rating':
				$query['orderby'] = 'rating';
				break;
			case 'popularity':
				$query['orderby'] = 'popularity';
				break;
		}
		$ids = array_map( 'intval', (array) wc_get_products( $query ) );
		if ( ! $ids && '' !== (string) ( $a['query'] ?? '' ) ) {
			// Fall back to the widest keyword so the assistant can still suggest something relevant.
			$words = preg_split( '/\s+/', (string) $a['query'] ) ?: [];
			usort( $words, static fn( $x, $y ) => strlen( $y ) <=> strlen( $x ) );
			if ( ! empty( $words[0] ) && strlen( $words[0] ) > 3 ) {
				$query['s'] = $words[0];
				unset( $query['meta_query'] );
				$ids = array_map( 'intval', (array) wc_get_products( $query ) );
			}
		}
		$cards = self::product_cards( $ids );
		return [ 'products' => $cards, 'count' => count( $cards ), 'currency' => get_woocommerce_currency() ];
	}

	public function best_sellers( array $a ): array {
		$ids   = ProductQuery::ids( [ 'source' => 'best_selling', 'limit' => (int) ( $a['limit'] ?? 4 ), 'category' => ! empty( $a['category'] ) ? [ sanitize_title( (string) $a['category'] ) ] : [] ] );
		$cards = self::product_cards( $ids, (int) ( $a['limit'] ?? 4 ) );
		return [ 'products' => $cards, 'count' => count( $cards ) ];
	}

	public function active_coupons(): array {
		$out = [];
		foreach ( get_posts( [ 'post_type' => 'shop_coupon', 'post_status' => 'publish', 'numberposts' => 20 ] ) as $post ) {
			$coupon = new \WC_Coupon( $post->post_title );
			if ( ! $coupon->get_id() || ( class_exists( '\Webgram\Core\Modules\Coupons\Module' ) && ! \Webgram\Core\Modules\Coupons\Module::is_live( $coupon ) ) ) {
				continue;
			}
			$out[] = [
				'code'        => $coupon->get_code(),
				'headline'    => class_exists( '\Webgram\Core\Modules\Coupons\Module' ) ? \Webgram\Core\Modules\Coupons\Module::headline( $coupon->get_discount_type(), (float) $coupon->get_amount(), $coupon->get_free_shipping(), $coupon->get_description() ) : $coupon->get_code(),
				'description' => wp_strip_all_tags( $coupon->get_description() ),
				'min_order'   => (float) $coupon->get_minimum_amount(),
				'expires'     => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '',
			];
			if ( count( $out ) >= 6 ) {
				break;
			}
		}
		return [ 'coupons' => $out, 'count' => count( $out ) ];
	}

	public function order_status( array $a, array $context ): array {
		$user_id = (int) ( $context['user_id'] ?? 0 );
		if ( $user_id <= 0 ) {
			return [ 'error' => 'login_required', 'message' => 'The shopper must log in to check an order.' ];
		}
		$number = preg_replace( '/\D+/', '', (string) ( $a['order_id'] ?? '' ) );
		$order  = $number ? wc_get_order( (int) $number ) : false;
		$order  = (int) apply_filters( 'webgram_core/track_order/order_id', $order ? $order->get_id() : 0, $number ) ? $order : false;
		if ( ! $order instanceof \WC_Order || (int) $order->get_customer_id() !== $user_id ) {
			return [ 'error' => 'not_found', 'message' => 'No order with that number belongs to this account.' ];
		}
		$items = [];
		foreach ( $order->get_items() as $item ) {
			$items[] = [ 'name' => $item->get_name(), 'qty' => $item->get_quantity() ];
		}
		return [
			'order_number' => $order->get_order_number(),
			'status'       => wc_get_order_status_name( $order->get_status() ),
			'date'         => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
			'total'        => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'items'        => $items,
			'tracking'     => (array) apply_filters( 'webgram_core/track_order/data', [], $order ),
			'url'          => $order->get_view_order_url(),
		];
	}

	public function store_info(): array {
		$s = $this->module->settings();
		return (array) apply_filters(
			'webgram_core/ai/store_info',
			[
				'store'    => get_bloginfo( 'name' ),
				'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
				'shipping' => (string) $s->get( 'info_shipping', '' ),
				'returns'  => (string) $s->get( 'info_returns', '' ),
				'payments' => (string) $s->get( 'info_payments', '' ),
				'contact'  => (string) $s->get( 'info_contact', '' ),
				'hours'    => (string) $s->get( 'info_hours', '' ),
			]
		);
	}
}
