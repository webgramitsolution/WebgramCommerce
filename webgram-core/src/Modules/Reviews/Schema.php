<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/** Extends WooCommerce's Review JSON-LD with the title, reviewBody and image URLs. Never overwrites existing keys. */
final class Schema {

	public static function register(): void {
		add_filter( 'woocommerce_structured_data_review', [ self::class, 'extend' ], 10, 2 );
	}

	public static function extend( array $markup, $comment ): array {
		if ( ! $comment instanceof \WP_Comment ) {
			return $markup;
		}
		return self::merge( $markup, (string) get_comment_meta( $comment->comment_ID, '_wg_title', true ), (string) $comment->comment_content, array_column( Media::items( (int) $comment->comment_ID ), 'url' ) );
	}

	/** Pure. */
	public static function merge( array $markup, string $title, string $body, array $image_urls ): array {
		if ( '' !== $title && empty( $markup['name'] ) ) {
			$markup['name'] = $title;
		}
		if ( empty( $markup['reviewBody'] ) && '' !== trim( $body ) ) {
			$markup['reviewBody'] = wp_strip_all_tags( $body );
		}
		$images = array_values( array_filter( array_map( 'strval', $image_urls ) ) );
		if ( $images && empty( $markup['image'] ) ) {
			$markup['image'] = $images;
		}
		return $markup;
	}
}
