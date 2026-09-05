<?php
namespace Webgram\Core\Modules\AiAssistant\Providers;

use Webgram\Core\Modules\AiAssistant\CompletionResult;
use Webgram\Core\Modules\AiAssistant\ProviderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * No API key needed: keyword intent detection routes the question to a tool (products, best sellers, coupons,
 * order status, store info, FAQ from settings) and composes a plain answer from the tool result.
 */
final class RuleBasedProvider implements ProviderInterface {

	/** @param array<int, array{q: string, a: string}> $faqs */
	public function __construct( private array $faqs = [], private string $store_name = '' ) {}

	public function name(): string {
		return 'rule_based';
	}

	public function supports_tools(): bool {
		return true;
	}

	/**
	 * Pure: detect the intent of a shopper message.
	 *
	 * @return array{tool: string, args: array, text: string}
	 */
	public static function intent( string $text ): array {
		$t = mb_strtolower( trim( $text ) );
		if ( '' === $t ) {
			return [ 'tool' => '', 'args' => [], 'text' => '' ];
		}
		if ( preg_match( '/^(hi|hello|hey|namaste|hola|good (morning|evening|afternoon))\b/u', $t ) && mb_strlen( $t ) < 25 ) {
			return [ 'tool' => '', 'args' => [], 'text' => 'greeting' ];
		}
		if ( preg_match( '/\b(thank|thanks|thx)\b/u', $t ) && mb_strlen( $t ) < 30 ) {
			return [ 'tool' => '', 'args' => [], 'text' => 'thanks' ];
		}
		if ( preg_match( '/\border\b.*?(#?\s?\d{2,})|(#\s?\d{2,}).*?\border\b|\b(track|where is my|status of)\b.*?(\d{2,})/u', $t, $m ) ) {
			preg_match( '/\d{2,}/', $t, $n );
			return [ 'tool' => 'order_status', 'args' => [ 'order_id' => $n[0] ?? '' ], 'text' => '' ];
		}
		if ( preg_match( '/\b(coupon|coupons|promo|discount code|offer|offers|deal|deals|code)\b/u', $t ) ) {
			return [ 'tool' => 'active_coupons', 'args' => [], 'text' => '' ];
		}
		if ( preg_match( '/\b(shipping|delivery|deliver|return|returns|refund|exchange|payment|pay|cod|cash on delivery|contact|phone|email|address|hours|open)\b/u', $t ) && ! preg_match( '/\b(show|find|buy|under|below|less than|cheap)\b/u', $t ) ) {
			return [ 'tool' => 'store_info', 'args' => [], 'text' => '' ];
		}
		if ( preg_match( '/\b(best ?sellers?|popular|trending|top (rated|selling)|most (sold|loved))\b/u', $t ) ) {
			return [ 'tool' => 'best_sellers', 'args' => [ 'limit' => 4 ], 'text' => '' ];
		}
		$args = [];
		if ( preg_match( '/\b(under|below|less than|upto|up to|max(?:imum)?|within)\s*(?:rs\.?|₹|inr|\$)?\s*([\d,]+)/u', $t, $m ) ) {
			$args['max_price'] = (float) str_replace( ',', '', $m[2] );
		}
		if ( preg_match( '/\b(above|over|more than|min(?:imum)?|from)\s*(?:rs\.?|₹|inr|\$)?\s*([\d,]+)/u', $t, $m ) ) {
			$args['min_price'] = (float) str_replace( ',', '', $m[2] );
		}
		if ( preg_match( '/\bbetween\s*(?:rs\.?|₹|inr|\$)?\s*([\d,]+)\s*(?:and|to|-)\s*(?:rs\.?|₹|inr|\$)?\s*([\d,]+)/u', $t, $m ) ) {
			$args['min_price'] = (float) str_replace( ',', '', $m[1] );
			$args['max_price'] = (float) str_replace( ',', '', $m[2] );
		}
		if ( preg_match( '/\b(cheapest|lowest price|low to high)\b/u', $t ) ) {
			$args['sort'] = 'price_asc';
		} elseif ( preg_match( '/\b(premium|expensive|high to low)\b/u', $t ) ) {
			$args['sort'] = 'price_desc';
		} elseif ( preg_match( '/\b(new|latest|newest|recent)\b/u', $t ) ) {
			$args['sort'] = 'newest';
		}
		$query = preg_replace( '/\b(show|find|search|looking for|i want|i need|do you have|any|some|please|me|for|a|an|the|under|below|less than|upto|up to|above|over|more than|between|and|to|cheapest|premium|expensive|new|latest|newest|recent|products?|items?|rs\.?|inr|₹|\$)\b|[\d,]+|[^\p{L}\p{N}\s]/u', ' ', $t );
		$query = trim( preg_replace( '/\s+/', ' ', (string) $query ) );
		return [ 'tool' => 'search_products', 'args' => $args + [ 'query' => $query ], 'text' => '' ];
	}

	public function complete( array $messages, array $tools, array $options ): CompletionResult {
		$last = end( $messages );
		if ( $last && 'tool' === $last['role'] ) {
			return new CompletionResult( self::compose( (string) ( $last['name'] ?? '' ), json_decode( (string) $last['content'], true ) ?: [] ) );
		}
		$user = '';
		for ( $i = count( $messages ) - 1; $i >= 0; $i-- ) {
			if ( 'user' === $messages[ $i ]['role'] ) {
				$user = (string) $messages[ $i ]['content'];
				break;
			}
		}
		$faq = self::faq_match( $user, $this->faqs );
		if ( null !== $faq ) {
			return new CompletionResult( $faq );
		}
		$intent = self::intent( $user );
		if ( 'greeting' === $intent['text'] ) {
			return new CompletionResult( sprintf( /* translators: %s: store name */ __( 'Hello! I can help you find products, check offers, track an order or answer questions about shipping and returns at %s. What are you looking for?', 'webgram-core' ), $this->store_name ?: get_bloginfo( 'name' ) ) );
		}
		if ( 'thanks' === $intent['text'] ) {
			return new CompletionResult( __( 'You are welcome! Anything else I can help with?', 'webgram-core' ) );
		}
		if ( '' === $intent['tool'] || ( 'search_products' === $intent['tool'] && '' === trim( (string) $intent['args']['query'] ) && ! isset( $intent['args']['max_price'] ) ) ) {
			return new CompletionResult( __( 'Tell me what you are looking for, for example "wall decor under 2000", "best sellers" or "any coupons?".', 'webgram-core' ) );
		}
		$available = array_column( $tools, 'name' );
		if ( $available && ! in_array( $intent['tool'], $available, true ) ) {
			return new CompletionResult( __( 'I cannot help with that yet, but our support team can.', 'webgram-core' ) );
		}
		return new CompletionResult( '', [ [ 'id' => 'rule_' . wp_rand( 1000, 9999 ), 'name' => $intent['tool'], 'arguments' => $intent['args'] ] ] );
	}

	/** Pure: FAQ match by keyword overlap (at least two shared words of 4+ letters, or question contained). */
	public static function faq_match( string $text, array $faqs ): ?string {
		$t = mb_strtolower( $text );
		if ( '' === trim( $t ) ) {
			return null;
		}
		$words = array_filter( preg_split( '/[^\p{L}\p{N}]+/u', $t ) ?: [], static fn( $w ) => mb_strlen( $w ) >= 4 );
		$best  = null;
		$score = 0;
		foreach ( $faqs as $faq ) {
			$q = mb_strtolower( (string) ( $faq['q'] ?? '' ) );
			if ( '' === $q ) {
				continue;
			}
			if ( str_contains( $t, $q ) ) {
				return (string) $faq['a'];
			}
			$qwords = array_filter( preg_split( '/[^\p{L}\p{N}]+/u', $q ) ?: [], static fn( $w ) => mb_strlen( $w ) >= 4 );
			$common = count( array_intersect( $words, $qwords ) );
			if ( $common >= 2 && $common > $score ) {
				$score = $common;
				$best  = (string) $faq['a'];
			}
		}
		return $best;
	}

	/** Pure: answer text from a tool result. */
	public static function compose( string $tool, array $result ): string {
		if ( isset( $result['error'] ) ) {
			return match ( $result['error'] ) {
				'login_required' => __( 'Please log in to your account so I can check your order.', 'webgram-core' ),
				'not_found'      => __( 'I could not find an order with that number on your account. Please check the number from your confirmation email.', 'webgram-core' ),
				default          => __( 'Sorry, I could not fetch that right now. Please try again.', 'webgram-core' ),
			};
		}
		switch ( $tool ) {
			case 'search_products':
			case 'best_sellers':
				$count = (int) ( $result['count'] ?? 0 );
				if ( 0 === $count ) {
					return __( 'I could not find matching products. Try different words or a wider price range.', 'webgram-core' );
				}
				return 'best_sellers' === $tool
					? __( 'These are our best sellers right now:', 'webgram-core' )
					: sprintf( /* translators: %d: count */ _n( 'Here is %d product that matches:', 'Here are %d products that match:', $count, 'webgram-core' ), $count );
			case 'active_coupons':
				if ( empty( $result['coupons'] ) ) {
					return __( 'There are no public coupons right now, but check the offers on the homepage.', 'webgram-core' );
				}
				$lines = [];
				foreach ( (array) $result['coupons'] as $c ) {
					$lines[] = sprintf( '%s: %s%s', strtoupper( (string) $c['code'] ), (string) $c['headline'], ! empty( $c['min_order'] ) ? ' (' . sprintf( /* translators: %s: amount */ __( 'min order %s', 'webgram-core' ), number_format_i18n( (float) $c['min_order'] ) ) . ')' : '' );
				}
				return __( 'Current offers:', 'webgram-core' ) . "\n" . implode( "\n", $lines );
			case 'order_status':
				$items = implode( ', ', array_map( static fn( $i ) => $i['qty'] . ' x ' . $i['name'], (array) ( $result['items'] ?? [] ) ) );
				return sprintf( /* translators: 1: number, 2: status, 3: date, 4: total, 5: items */ __( 'Order #%1$s is %2$s (placed %3$s, total %4$s). Items: %5$s.', 'webgram-core' ), (string) $result['order_number'], (string) $result['status'], (string) $result['date'], (string) $result['total'], $items );
			case 'store_info':
				$parts = [];
				foreach ( [ 'shipping' => __( 'Shipping', 'webgram-core' ), 'returns' => __( 'Returns', 'webgram-core' ), 'payments' => __( 'Payments', 'webgram-core' ), 'contact' => __( 'Contact', 'webgram-core' ), 'hours' => __( 'Hours', 'webgram-core' ) ] as $key => $label ) {
					if ( ! empty( $result[ $key ] ) ) {
						$parts[] = $label . ': ' . $result[ $key ];
					}
				}
				return $parts ? implode( "\n", $parts ) : __( 'Store details are not filled in yet. Please contact us for shipping and return information.', 'webgram-core' );
		}
		return __( 'Done.', 'webgram-core' );
	}
}
