<?php
namespace Webgram\Core\Modules\WooEnhancements;

defined( 'ABSPATH' ) || exit;

/**
 * Specifications table: WooCommerce attributes merged with the per-product key/value repeater (_wg_specs), one
 * table. Source is configurable (both, attributes only, custom only).
 */
final class Specifications {

	public const META = '_wg_specs';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/product/specifications', [ $this, 'render' ] );
		add_shortcode( 'webgram_specifications', [ $this, 'shortcode' ] );
	}

	/**
	 * Pure merge (harness tested).
	 *
	 * @param array<int, array{label: string, value: string}> $attributes
	 * @param array<int, array{label: string, value: string}> $custom
	 */
	public static function merge( array $attributes, array $custom, string $source = 'both' ): array {
		$rows = [];
		if ( 'custom' !== $source ) {
			foreach ( $attributes as $row ) {
				if ( '' !== trim( (string) ( $row['label'] ?? '' ) ) && '' !== trim( (string) ( $row['value'] ?? '' ) ) ) {
					$rows[ strtolower( trim( $row['label'] ) ) ] = [ 'label' => trim( $row['label'] ), 'value' => trim( $row['value'] ) ];
				}
			}
		}
		if ( 'attributes' !== $source ) {
			foreach ( $custom as $row ) {
				if ( '' !== trim( (string) ( $row['label'] ?? '' ) ) && '' !== trim( (string) ( $row['value'] ?? '' ) ) ) {
					$rows[ strtolower( trim( $row['label'] ) ) ] = [ 'label' => trim( $row['label'] ), 'value' => trim( $row['value'] ) ];
				}
			}
		}
		return array_values( $rows );
	}

	public function rows( \WC_Product $product ): array {
		$attributes = [];
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}
			if ( $attribute->is_taxonomy() ) {
				$terms  = wc_get_product_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'names' ] );
				$values = implode( ', ', $terms );
			} else {
				$values = implode( ', ', array_map( 'trim', (array) $attribute->get_options() ) );
			}
			$attributes[] = [ 'label' => wc_attribute_label( $attribute->get_name(), $product ), 'value' => wp_strip_all_tags( $values ) ];
		}
		if ( $product->has_weight() ) {
			$attributes[] = [ 'label' => __( 'Weight', 'webgram-core' ), 'value' => wc_format_weight( $product->get_weight() ) ];
		}
		if ( $product->has_dimensions() ) {
			$attributes[] = [ 'label' => __( 'Dimensions', 'webgram-core' ), 'value' => wc_format_dimensions( $product->get_dimensions( false ) ) ];
		}
		$custom = (array) get_post_meta( $product->get_id(), self::META, true );
		$rows   = self::merge( $attributes, array_filter( $custom, 'is_array' ), (string) $this->module->settings()->get( 'specs_source', 'both' ) );
		return (array) apply_filters( 'webgram_core/specifications/rows', $rows, $product );
	}

	public function render( \WC_Product $product ): void {
		$rows = $this->rows( $product );
		if ( ! $rows ) {
			return;
		}
		\webgram_core()->view( 'woo-enhancements/specifications', [ 'rows' => $rows, 'title' => __( 'Specifications', 'webgram-core' ) ] );
	}

	public function shortcode( array|string $atts ): string {
		$atts    = shortcode_atts( [ 'product_id' => 0 ], (array) $atts, 'webgram_specifications' );
		$product = wc_get_product( (int) $atts['product_id'] ?: get_the_ID() );
		if ( ! $product ) {
			return '';
		}
		ob_start();
		$this->render( $product );
		return (string) ob_get_clean();
	}

	/** Sanitize repeater rows from the product panel. */
	public static function sanitize_rows( array $raw ): array {
		$rows = [];
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
			$value = sanitize_text_field( (string) ( $row['value'] ?? '' ) );
			if ( '' !== $label && '' !== $value ) {
				$rows[] = [ 'label' => $label, 'value' => $value ];
			}
			if ( count( $rows ) >= 50 ) {
				break;
			}
		}
		return $rows;
	}
}
