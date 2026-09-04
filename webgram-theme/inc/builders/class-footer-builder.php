<?php
/**
 * Footer builder: elements, layout storage (option webgram_footer_layout) and sanitization.
 * Rows: "widgets" (1 to 6 columns) and "bottom" (left, center, right).
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Footer_Builder {

	public const OPTION  = 'webgram_footer_layout';
	public const MAX_COLUMNS = 6;
	public const BOTTOM_AREAS = [ 'left', 'center', 'right' ];

	private static ?Webgram_Footer_Builder $instance = null;

	/** @var array<string, Webgram_Element>|null */
	private ?array $elements = null;

	private ?array $layout = null;

	public static function instance(): Webgram_Footer_Builder {
		return self::$instance ??= new self();
	}

	/** @return array<string, Webgram_Element> */
	public function elements(): array {
		if ( null !== $this->elements ) {
			return $this->elements;
		}
		$list = [];
		$classes = [ 'Webgram_Element_Logo', 'Webgram_Element_Footer_Description', 'Webgram_Element_Social', 'Webgram_Element_Footer_Newsletter', 'Webgram_Element_Footer_Payment_Icons', 'Webgram_Element_Footer_Trust_Text', 'Webgram_Element_Footer_Copyright', 'Webgram_Element_Footer_Contact', 'Webgram_Element_Text', 'Webgram_Element_Html_Block', 'Webgram_Element_Button' ];
		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$el                = new $class();
				$list[ $el->id() ] = $el;
			}
		}
		for ( $i = 1; $i <= 3; $i++ ) {
			$el                = new Webgram_Element_Footer_Menu( $i );
			$list[ $el->id() ] = $el;
		}
		$areas = max( 1, min( self::MAX_COLUMNS, (int) webgram_option( 'footer_columns' ) ) );
		for ( $i = 1; $i <= $areas; $i++ ) {
			$el                = new Webgram_Element_Footer_Widget_Area( $i );
			$list[ $el->id() ] = $el;
		}
		foreach ( (array) apply_filters( 'webgram/footer/elements', [] ) as $def ) {
			if ( $def instanceof Webgram_Element ) {
				$list[ $def->id() ] = $def;
			} elseif ( is_array( $def ) && ! empty( $def['id'] ) ) {
				$el                = new Webgram_Element_Callback( $def );
				$list[ $el->id() ] = $el;
			}
		}
		$this->elements = $list;
		return $list;
	}

	public function element( string $id ): ?Webgram_Element {
		return $this->elements()[ $id ] ?? null;
	}

	public function available_elements(): array {
		return array_filter( $this->elements(), static fn( Webgram_Element $el ) => $el->is_available() );
	}

	public function default_layout(): array {
		$presets = webgram_footer_presets();
		$key     = (string) apply_filters( 'webgram/footer/default_preset', 'store' );
		return $presets[ $key ]['layout'] ?? reset( $presets )['layout'];
	}

	public function layout(): array {
		if ( null === $this->layout ) {
			$stored       = get_option( self::OPTION );
			$this->layout = is_array( $stored ) && $stored ? $this->sanitize( $stored ) : $this->sanitize( $this->default_layout() );
		}
		return $this->layout;
	}

	public function save( array $layout ): void {
		$this->layout = $this->sanitize( $layout );
		update_option( self::OPTION, $this->layout, true );
		Webgram_Settings::instance()->flush_caches();
		do_action( 'webgram/footer/layout_saved', $this->layout );
	}

	public function sanitize( array $raw ): array {
		$known = array_keys( $this->elements() );
		$seen  = [];
		$pick  = static function ( $ids ) use ( $known, &$seen ): array {
			$out = [];
			foreach ( (array) $ids as $id ) {
				$id = sanitize_key( is_array( $id ) ? (string) ( $id['id'] ?? '' ) : (string) $id );
				if ( in_array( $id, $known, true ) && ! isset( $seen[ $id ] ) ) {
					$out[]       = $id;
					$seen[ $id ] = true;
				}
			}
			return $out;
		};

		$w   = (array) ( $raw['widgets'] ?? [] );
		$out = [
			'widgets' => [
				'enabled'  => ! isset( $w['enabled'] ) || ! empty( $w['enabled'] ),
				'columns'  => max( 1, min( self::MAX_COLUMNS, (int) ( $w['columns'] ?? 4 ) ) ),
				'areas'    => [],
				'settings' => $this->sanitize_row_settings( (array) ( $w['settings'] ?? [] ), 'widgets' ),
			],
		];
		for ( $i = 1; $i <= self::MAX_COLUMNS; $i++ ) {
			$out['widgets']['areas'][ 'col_' . $i ] = $pick( $w['areas'][ 'col_' . $i ] ?? [] );
		}

		$b             = (array) ( $raw['bottom'] ?? [] );
		$out['bottom'] = [ 'enabled' => ! isset( $b['enabled'] ) || ! empty( $b['enabled'] ) ];
		foreach ( self::BOTTOM_AREAS as $area ) {
			$out['bottom'][ $area ] = $pick( $b[ $area ] ?? [] );
		}
		$out['bottom']['settings'] = $this->sanitize_row_settings( (array) ( $b['settings'] ?? [] ), 'bottom' );

		$out['elements'] = [];
		foreach ( (array) ( $raw['elements'] ?? [] ) as $id => $settings ) {
			$el = $this->element( sanitize_key( (string) $id ) );
			if ( $el && is_array( $settings ) ) {
				$out['elements'][ $el->id() ] = $el->prepare( $settings );
			}
		}
		return $out;
	}

	public function row_fields( string $row ): array {
		if ( 'widgets' === $row ) {
			return [
				'first_wide' => [ 'label' => __( 'First column wider (brand column)', 'webgram' ), 'type' => 'switch', 'default' => true ],
				'padding'    => [ 'label' => __( 'Vertical padding', 'webgram' ), 'type' => 'number', 'min' => 16, 'max' => 160, 'unit' => 'px', 'default' => 64 ],
				'align'      => [ 'label' => __( 'Text alignment', 'webgram' ), 'type' => 'radio', 'choices' => [ 'start' => __( 'Start', 'webgram' ), 'center' => __( 'Center', 'webgram' ) ], 'default' => 'start' ],
			];
		}
		return [
			'border' => [ 'label' => __( 'Top border', 'webgram' ), 'type' => 'switch', 'default' => true ],
			'bg'     => [ 'label' => __( 'Background', 'webgram' ), 'type' => 'text', 'default' => '', 'description' => __( 'Leave empty to inherit the footer color.', 'webgram' ), 'sanitize' => [ 'Webgram_Header_Builder', 'sanitize_css_value' ] ],
		];
	}

	public function sanitize_row_settings( array $raw, string $row ): array {
		$fields = $this->row_fields( $row );
		foreach ( $fields as $id => &$field ) {
			$field['id'] = $id;
		}
		unset( $field );
		$defaults = array_map( static fn( $f ) => $f['default'], $fields );
		return array_merge( $defaults, Webgram_Settings_Sanitizer::sanitize_all( $fields, $raw ) );
	}

	public function element_settings( string $id ): array {
		$el = $this->element( $id );
		if ( ! $el ) {
			return [];
		}
		return array_merge( $el->defaults(), (array) ( $this->layout()['elements'][ $id ] ?? [] ) );
	}
}
