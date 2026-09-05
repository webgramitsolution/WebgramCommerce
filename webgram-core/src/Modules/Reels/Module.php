<?php
namespace Webgram\Core\Modules\Reels;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Product reels (spec 4.6): 9:16 cards with poster and muted autoplay in viewport, full screen vertical viewer
 * with product sheet and AJAX add to cart, analytics events, shortcode, widget and block.
 */
final class Module extends BaseModule {

	private bool $needed = false;

	public function id(): string {
		return 'reels';
	}

	public function name(): string {
		return __( 'Product Reels', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Vertical video reels linked to products, with a full screen viewer and add to cart.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 6;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		( new PostType( $this ) )->register();
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_shortcode( 'webgram_reels', [ $this, 'shortcode' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		add_action( 'webgram/product/below/reels', [ $this, 'product_row' ] );
		add_action( 'wp_footer', [ $this, 'viewer_shell' ], 45 );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-reels', 'css/reels.css' );
		$assets->script( 'webgram-core-reels', 'js/reels.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'autoplay', 'label' => __( 'Autoplay muted in viewport', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_product', 'label' => __( 'Show product mini card on reels', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'show_mute', 'label' => __( 'Mute toggle on cards', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'count', 'label' => __( 'Reels per row (default)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 20, 'default' => 5 ],
			[ 'id' => 'product_row', 'label' => __( 'Show related reels on product pages', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Reels linked to the product, below the fold (Webgram theme section order).', 'webgram-core' ) ],
			[ 'id' => 'upload_limit_mb', 'label' => __( 'Upload size limit for reel videos (MB)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 2048, 'default' => 64, 'description' => __( '0 uses the WordPress limit.', 'webgram-core' ) ],
		];
	}

	public function frontend_data( array $data ): array {
		$data['reels'] = [
			'autoplay' => Helpers::bool( $this->settings()->get( 'autoplay', true ) ),
			'i18n'     => [ 'added' => __( 'Added to cart', 'webgram-core' ), 'mute' => __( 'Mute', 'webgram-core' ), 'unmute' => __( 'Unmute', 'webgram-core' ), 'close' => __( 'Close', 'webgram-core' ), 'viewProduct' => __( 'View product', 'webgram-core' ) ],
		];
		return $data;
	}

	/** Reel data for templates and the viewer. */
	public function item( \WP_Post $post ): array {
		$d     = PostType::data( (int) $post->ID );
		$embed = [];
		if ( 'upload' === $d['source'] && $d['video_id'] ) {
			$src = (string) wp_get_attachment_url( $d['video_id'] );
			if ( '' !== $src ) {
				$embed = [ 'type' => 'video', 'src' => $src, 'source' => 'mp4' ];
			}
		} elseif ( '' !== $d['url'] ) {
			$embed = Sources::embed( $d['url'] );
		}
		$products = [];
		foreach ( $d['products'] as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}
			$products[] = [
				'id'         => $product->get_id(),
				'name'       => $product->get_name(),
				'price_html' => $product->get_price_html(),
				'url'        => $product->get_permalink(),
				'image'      => (string) wp_get_attachment_image_url( (int) $product->get_image_id(), 'woocommerce_gallery_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
				'add'        => $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' ) && 'simple' === $product->get_type(),
			];
		}
		return (array) apply_filters(
			'webgram_core/reels/item',
			[
				'id'       => (int) $post->ID,
				'title'    => (string) $post->post_title,
				'caption'  => (string) $post->post_excerpt,
				'poster'   => $d['poster'] ? (string) wp_get_attachment_image_url( $d['poster'], 'large' ) : '',
				'embed'    => $embed,
				'products' => $products,
				'cta'      => $d['cta'],
			],
			$post
		);
	}

	/** @return array<int, array> */
	public function items( array $args ): array {
		$query = [ 'post_type' => PostType::TYPE, 'post_status' => 'publish', 'numberposts' => max( 1, min( 40, (int) ( $args['count'] ?? 5 ) ) ), 'orderby' => 'date', 'order' => 'DESC' ];
		if ( ! empty( $args['category'] ) ) {
			$query['tax_query'] = [ [ 'taxonomy' => PostType::TAX, 'field' => 'slug', 'terms' => array_map( 'sanitize_title', (array) $args['category'] ) ] ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
		if ( ! empty( $args['product_id'] ) ) {
			$query['meta_query'] = [ [ 'key' => '_wg_products', 'value' => serialize( (int) $args['product_id'] ), 'compare' => 'LIKE' ] ]; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}
		if ( ! empty( $args['ids'] ) ) {
			$query['post__in'] = array_map( 'intval', (array) $args['ids'] );
			$query['orderby']  = 'post__in';
		}
		$out = [];
		foreach ( get_posts( $query ) as $post ) {
			$item = $this->item( $post );
			if ( $item['embed'] && $item['poster'] ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	public function render( array $args = [] ): string {
		$items = $this->items( $args );
		if ( ! $items ) {
			return '';
		}
		$this->needed = true;
		\webgram_core()->assets()->enqueue_module( 'reels' );
		return $this->view(
			'row',
			[
				'items'        => $items,
				'title'        => (string) ( $args['title'] ?? '' ),
				'subtitle'     => (string) ( $args['subtitle'] ?? '' ),
				'layout'       => 'grid' === ( $args['layout'] ?? 'row' ) ? 'grid' : 'row',
				'columns'      => max( 2, min( 6, (int) ( $args['columns'] ?? 5 ) ) ),
				'show_product' => Helpers::bool( $args['show_product'] ?? $this->settings()->get( 'show_product', true ) ),
				'show_mute'    => Helpers::bool( $this->settings()->get( 'show_mute', true ) ),
				'autoplay'     => Helpers::bool( $this->settings()->get( 'autoplay', true ) ),
			],
			false
		);
	}

	public function shortcode( array|string $atts ): string {
		$atts = shortcode_atts( [ 'category' => '', 'count' => (int) $this->settings()->get( 'count', 5 ), 'layout' => 'row', 'title' => '', 'columns' => 5, 'ids' => '' ], (array) $atts, 'webgram_reels' );
		return $this->render( [ 'category' => array_filter( array_map( 'trim', explode( ',', (string) $atts['category'] ) ) ), 'count' => (int) $atts['count'], 'layout' => (string) $atts['layout'], 'title' => (string) $atts['title'], 'columns' => (int) $atts['columns'], 'ids' => array_filter( array_map( 'intval', explode( ',', (string) $atts['ids'] ) ) ) ] );
	}

	public function product_row( $product = null ): void {
		global $product;
		if ( ! $product instanceof \WC_Product || ! Helpers::bool( $this->settings()->get( 'product_row', true ) ) ) {
			return;
		}
		echo $this->render( [ 'product_id' => $product->get_id(), 'count' => 10, 'title' => __( 'See it in action', 'webgram-core' ) ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output.
	}

	public function viewer_shell(): void {
		if ( $this->needed && ! is_admin() ) {
			$this->view( 'viewer' );
		}
	}

	public function widget_definition( array $widgets ): array {
		$widgets['reels'] = [
			'title'    => __( 'Webgram Reels', 'webgram-core' ),
			'icon'     => 'eicon-video-playlist',
			'controls' => [
				'title'        => [ 'label' => __( 'Title', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Products Reels', 'webgram-core' ) ],
				'subtitle'     => [ 'label' => __( 'Subtitle', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				'category'     => [ 'label' => __( 'Reel category slugs (comma separated)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
				'count'        => [ 'label' => __( 'Reels', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 40, 'default' => 5 ],
				'columns'      => [ 'label' => __( 'Visible on desktop', 'webgram-core' ), 'type' => 'number', 'min' => 2, 'max' => 6, 'default' => 5 ],
				'layout'       => [ 'label' => __( 'Layout', 'webgram-core' ), 'type' => 'select', 'options' => [ 'row' => __( 'Row (slider)', 'webgram-core' ), 'grid' => __( 'Grid', 'webgram-core' ) ], 'default' => 'row' ],
				'show_product' => [ 'label' => __( 'Show product mini card', 'webgram-core' ), 'type' => 'switch', 'default' => true ],
			],
			'render'   => fn( array $a ) => $this->render( [ 'title' => $a['title'], 'subtitle' => $a['subtitle'], 'category' => array_filter( array_map( 'trim', explode( ',', (string) $a['category'] ) ) ), 'count' => $a['count'], 'columns' => $a['columns'], 'layout' => $a['layout'], 'show_product' => $a['show_product'] ] ),
		];
		return $widgets;
	}
}
