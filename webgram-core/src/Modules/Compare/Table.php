<?php
namespace Webgram\Core\Modules\Compare;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the comparison rows: fixed rows (image, price, rating, stock, SKU, description) plus the union of
 * product attributes across the compared products. Marks rows whose values differ so the page can highlight them.
 */
final class Table {

	/**
	 * Pure: given per-product attribute maps, return the ordered union of attribute labels.
	 *
	 * @param array<int, array<string, string>> $maps product id => [label => value]
	 * @return string[]
	 */
	public static function attribute_labels( array $maps ): array {
		$labels = [];
		foreach ( $maps as $map ) {
			foreach ( array_keys( $map ) as $label ) {
				if ( ! in_array( $label, $labels, true ) ) {
					$labels[] = $label;
				}
			}
		}
		return $labels;
	}

	/** Pure: true when at least two values differ (case-insensitive, whitespace-trimmed, empty counts as a value). */
	public static function differs( array $values ): bool {
		$norm = array_map( static fn( $v ) => strtolower( trim( wp_strip_all_tags( (string) $v ) ) ), array_values( $values ) );
		return count( array_unique( $norm ) ) > 1;
	}

	/**
	 * Rows for the compare template.
	 *
	 * @param \WC_Product[] $products
	 * @return array<int, array{key: string, label: string, cells: array<int, string>, differs: bool, html: bool}>
	 */
	public static function rows( array $products, array $show ): array {
		$rows = [];
		$add  = static function ( string $key, string $label, array $cells, bool $html = false ) use ( &$rows ): void {
			$rows[] = [ 'key' => $key, 'label' => $label, 'cells' => $cells, 'differs' => self::differs( $cells ), 'html' => $html ];
		};
		if ( ! empty( $show['price'] ) ) {
			$add( 'price', __( 'Price', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => $p->get_price_html(), $products ), true );
		}
		if ( ! empty( $show['rating'] ) ) {
			$add( 'rating', __( 'Rating', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => $p->get_review_count() ? wc_get_rating_html( (float) $p->get_average_rating(), $p->get_rating_count() ) . '<span class="wgc-compare__rating-text">' . esc_html( number_format_i18n( (float) $p->get_average_rating(), 1 ) ) . '</span>' : '<span class="wgc-compare__muted">' . esc_html__( 'No reviews', 'webgram-core' ) . '</span>', $products ), true );
		}
		if ( ! empty( $show['stock'] ) ) {
			$add( 'stock', __( 'Availability', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => $p->is_in_stock() ? __( 'In stock', 'webgram-core' ) : __( 'Out of stock', 'webgram-core' ), $products ) );
		}
		if ( ! empty( $show['sku'] ) ) {
			$add( 'sku', __( 'SKU', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => (string) $p->get_sku(), $products ) );
		}
		if ( ! empty( $show['description'] ) ) {
			$add( 'description', __( 'Description', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => wp_trim_words( wp_strip_all_tags( (string) $p->get_short_description() ), 30 ), $products ) );
		}
		if ( ! empty( $show['dimensions'] ) ) {
			$add( 'weight', __( 'Weight', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => $p->has_weight() ? wc_format_weight( $p->get_weight() ) : '', $products ) );
			$add( 'dimensions', __( 'Dimensions', 'webgram-core' ), array_map( static fn( \WC_Product $p ) => $p->has_dimensions() ? wc_format_dimensions( $p->get_dimensions( false ) ) : '', $products ) );
		}
		if ( ! empty( $show['attributes'] ) ) {
			$maps = [];
			foreach ( $products as $i => $product ) {
				$maps[ $i ] = self::attributes( $product );
			}
			foreach ( self::attribute_labels( $maps ) as $label ) {
				$add( 'attr_' . sanitize_title( $label ), $label, array_map( static fn( array $map ) => $map[ $label ] ?? '', $maps ) );
			}
		}
		return (array) apply_filters( 'webgram_core/compare/rows', $rows, $products );
	}

	/** @return array<string, string> label => value */
	public static function attributes( \WC_Product $product ): array {
		$out = [];
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}
			$label = wc_attribute_label( $attribute->get_name(), $product );
			if ( $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'names' ] );
				$value = implode( ', ', array_map( 'strval', (array) $terms ) );
			} else {
				$value = implode( ', ', array_map( 'trim', (array) $attribute->get_options() ) );
			}
			$out[ $label ] = wp_strip_all_tags( $value );
		}
		return $out;
	}
}
