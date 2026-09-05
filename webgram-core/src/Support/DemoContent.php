<?php
/**
 * Core side of the theme's demo importer. Listens to `webgram/demo/import` and creates a slider, testimonials,
 * coupons and the wishlist and compare pages when those modules are active. Idempotent: matched by the
 * `_webgram_demo` meta or by coupon code. Reports the slider id through `webgram/demo/context`.
 *
 * @package Webgram\Core
 */

namespace Webgram\Core\Support;

use Webgram\Core\Admin\PageSetup;
use Webgram\Core\Modules\Integrations\Testimonials;
use Webgram\Core\Modules\Slider\PostType as SliderType;
use Webgram\Core\Modules\Slider\Slides;
use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

final class DemoContent {

	/** @var string[] */
	private array $lines = [];

	private int $slider_id = 0;

	public function __construct( private Plugin $plugin, private PageSetup $page_setup ) {}

	public function register(): void {
		add_action( 'webgram/demo/import', [ $this, 'import' ], 10, 2 );
		add_filter( 'webgram/demo/context', [ $this, 'context' ] );
	}

	/**
	 * @param array<string, mixed> $context  products (ids), images (name => id), pages.
	 * @param callable|null        $image    Theme callback returning an attachment id for a demo image name.
	 */
	public function import( array $context, ?callable $image = null ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$image    = is_callable( $image ) ? $image : static fn( string $name ): int => (int) ( $context['images'][ $name ] ?? 0 );
		$products = array_values( array_filter( array_map( 'intval', (array) ( $context['products'] ?? [] ) ) ) );
		$modules  = $this->plugin->modules();

		if ( $modules->is_active( 'slider' ) ) {
			$this->slider_id = $this->slider( $image );
		}
		if ( $modules->is_active( 'integrations' ) ) {
			$this->lines[] = sprintf( /* translators: %d: count. */ __( '%d testimonials.', 'webgram-core' ), $this->testimonials( $products, $image ) );
		}
		if ( $modules->is_active( 'coupons' ) && class_exists( 'WC_Coupon' ) ) {
			$this->lines[] = sprintf( /* translators: %d: count. */ __( '%d coupons.', 'webgram-core' ), $this->coupons() );
		}
		if ( $modules->is_active( 'instagram' ) ) {
			$this->lines[] = sprintf( /* translators: %d: count. */ __( '%d Instagram posts.', 'webgram-core' ), $this->instagram( $image ) );
		}
		foreach ( [ 'wishlist', 'compare' ] as $key ) {
			if ( $modules->is_active( $key ) && $this->page_setup->create_page( $key ) > 0 ) {
				$this->lines[] = sprintf( /* translators: %s: page name. */ __( '%s page ready.', 'webgram-core' ), ucfirst( $key ) );
			}
		}
	}

	public function context( array $context ): array {
		if ( $this->slider_id > 0 ) {
			$context['slider_id'] = $this->slider_id;
			array_unshift( $this->lines, __( 'Home slider.', 'webgram-core' ) );
		}
		$context['core_message'] = __( 'Webgram Core demo content: ', 'webgram-core' ) . implode( ' ', $this->lines );
		return $context;
	}

	private function existing( string $type, string $key ): int {
		$found = get_posts( [ 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_webgram_demo', 'meta_value' => $key ] ); // phpcs:ignore WordPress.DB.SlowDBQuery
		return $found ? (int) $found[0] : 0;
	}

	private function slider( callable $image ): int {
		$id = $this->existing( SliderType::TYPE, 'home-slider' );
		if ( $id ) {
			return $id;
		}
		$id = wp_insert_post( [ 'post_type' => SliderType::TYPE, 'post_status' => 'publish', 'post_title' => __( 'Home slider', 'webgram-core' ) ], true );
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		$benefits = "truck|Free shipping above 499\nshield|Secure payments\nrefresh|Easy 7 day returns\nheadset|365 days help desk";
		$slides   = Slides::sanitize(
			[
				[
					'image'        => $image( 'slide-1' ),
					'image_mobile' => $image( 'slide-1-mobile' ),
					'heading'      => __( 'Festive pooja essentials', 'webgram-core' ),
					'subheading'   => __( 'Brass, copper and sandalwood, chosen for daily rituals', 'webgram-core' ),
					'cta_text'     => __( 'Shop now', 'webgram-core' ),
					'cta_url'      => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
					'text_color'   => '#ffffff',
					'benefits'     => $benefits,
				],
				[
					'image'        => $image( 'slide-2' ),
					'image_mobile' => $image( 'slide-2-mobile' ),
					'heading'      => __( 'Home decor that feels like home', 'webgram-core' ),
					'subheading'   => __( 'Handloom textiles, ceramics and wall art', 'webgram-core' ),
					'cta_text'     => __( 'Explore decor', 'webgram-core' ),
					'cta_url'      => home_url( '/product-category/home-decor/' ),
					'text_color'   => '#ffffff',
					'benefits'     => $benefits,
				],
			]
		);
		update_post_meta( $id, SliderType::META_SLIDES, $slides );
		update_post_meta( $id, SliderType::META_SETTINGS, Slides::defaults() );
		update_post_meta( $id, '_webgram_demo', 'home-slider' );
		return (int) $id;
	}

	/** @param int[] $products */
	private function testimonials( array $products, callable $image ): int {
		$items = [
			[ 'Anjali Mehta', 'Pune, homemaker', 'The brass diyas are heavier than they look and the finish is beautiful. Delivered in two days.', 'avatar-1' ],
			[ 'Rahul Verma', 'Bengaluru, engineer', 'Ordered the copper bottle for office use. Leak proof and the hammered finish gets compliments.', 'avatar-2' ],
			[ 'Meera Iyer', 'Chennai, teacher', 'Bought the gift hamper for Diwali. Packaging was neat and the handwritten card option was a nice touch.', 'avatar-3' ],
			[ 'Sana Khan', 'Hyderabad, designer', 'The table runner colours match the photos exactly. Washed twice, no fading.', 'avatar-1' ],
			[ 'Vikram Singh', 'Jaipur, retailer', 'We order puja thalis in bulk for our shop. Consistent quality and quick replies on WhatsApp.', 'avatar-2' ],
			[ 'Priya Nair', 'Kochi, nurse', 'Cast iron tawa came pre seasoned and my first dosa did not stick. Very happy.', 'avatar-3' ],
		];
		$count = 0;
		foreach ( $items as $i => [ $name, $label, $text, $avatar ] ) {
			$key = 'testimonial-' . ( $i + 1 );
			if ( $this->existing( Testimonials::TYPE, $key ) ) {
				continue;
			}
			$id = wp_insert_post( [ 'post_type' => Testimonials::TYPE, 'post_status' => 'publish', 'post_title' => $name, 'post_content' => $text ], true );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_wg_label', $label );
			update_post_meta( $id, '_wg_rating', 5 );
			update_post_meta( $id, '_wg_product_id', (int) ( $products[ $i % max( 1, count( $products ) ) ] ?? 0 ) );
			update_post_meta( $id, '_webgram_demo', $key );
			$avatar_id = (int) $image( $avatar );
			if ( $avatar_id ) {
				set_post_thumbnail( $id, $avatar_id );
			}
			++$count;
		}
		return $count;
	}

	/** Manual Instagram gallery from the demo images, so the feed works without an API token. */
	private function instagram( callable $image ): int {
		$settings = $this->plugin->settings( 'instagram' );
		$existing = (array) $settings->get( 'manual_items', [] );
		if ( $existing ) {
			return count( $existing );
		}
		$items = [];
		for ( $i = 1; $i <= 6; $i++ ) {
			$id = (int) $image( 'insta-' . $i );
			if ( $id > 0 ) {
				$items[] = [ 'image' => $id, 'link' => '', 'caption' => '' ];
			}
		}
		if ( $items ) {
			$settings->set( 'mode', 'manual' );
			$settings->set( 'manual_items', $items );
		}
		return count( $items );
	}

	private function coupons(): int {
		$defs = [
			'WELCOME10' => [ 'discount_type' => 'percent', 'amount' => 10, 'description' => __( '10% off your first order', 'webgram-core' ), 'free_shipping' => false, 'minimum_amount' => 499 ],
			'FREESHIP'  => [ 'discount_type' => 'fixed_cart', 'amount' => 0, 'description' => __( 'Free shipping on orders above 999', 'webgram-core' ), 'free_shipping' => true, 'minimum_amount' => 999 ],
		];
		$count = 0;
		foreach ( $defs as $code => $def ) {
			if ( wc_get_coupon_id_by_code( $code ) ) {
				continue;
			}
			$coupon = new \WC_Coupon();
			$coupon->set_code( $code );
			$coupon->set_discount_type( $def['discount_type'] );
			$coupon->set_amount( (float) $def['amount'] );
			$coupon->set_description( $def['description'] );
			$coupon->set_free_shipping( $def['free_shipping'] );
			$coupon->set_minimum_amount( (float) $def['minimum_amount'] );
			$coupon->set_individual_use( true );
			if ( $coupon->save() ) {
				++$count;
			}
		}
		return $count;
	}
}
