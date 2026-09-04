<?php
/**
 * Header builder: element registry, layout storage (option webgram_header_layout) and sanitization.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Header_Builder {

	public const OPTION  = 'webgram_header_layout';
	public const DEVICES = [ 'desktop', 'mobile' ];
	public const ROWS    = [ 'top', 'main', 'bottom' ];
	public const AREAS   = [ 'left', 'center', 'right' ];

	private static ?Webgram_Header_Builder $instance = null;

	/** @var array<string, Webgram_Element>|null */
	private ?array $elements = null;

	private ?array $layout = null;

	public static function instance(): Webgram_Header_Builder {
		return self::$instance ??= new self();
	}

	/** @return array<string, Webgram_Element> */
	public function elements(): array {
		if ( null !== $this->elements ) {
			return $this->elements;
		}

		$classes = [
			'Webgram_Element_Logo',
			'Webgram_Element_Menu',
			'Webgram_Element_Menu_Secondary',
			'Webgram_Element_Menu_Vertical',
			'Webgram_Element_Search',
			'Webgram_Element_Search_Toggle',
			'Webgram_Element_Track_Order',
			'Webgram_Element_Bulk_Order',
			'Webgram_Element_Help',
			'Webgram_Element_Cart',
			'Webgram_Element_Account',
			'Webgram_Element_Announcement',
			'Webgram_Element_Social',
			'Webgram_Element_Button',
			'Webgram_Element_Text',
			'Webgram_Element_Html_Block',
			'Webgram_Element_Phone',
			'Webgram_Element_Divider',
			'Webgram_Element_Spacer',
			'Webgram_Element_Menu_Toggle',
			'Webgram_Element_Currency',
			'Webgram_Element_Language',
		];

		$list = [];
		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				$el                = new $class();
				$list[ $el->id() ] = $el;
			}
		}

		// Core (and third parties) add array-defined elements; they disappear cleanly when the provider is off.
		foreach ( (array) apply_filters( 'webgram/header/elements', [] ) as $def ) {
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

	/** Elements that can be placed right now (dependencies satisfied). */
	public function available_elements(): array {
		return array_filter( $this->elements(), static fn( Webgram_Element $el ) => $el->is_available() );
	}

	public function default_layout(): array {
		$presets = webgram_header_presets();
		$key     = (string) apply_filters( 'webgram/header/default_preset', 'store' );
		return $presets[ $key ]['layout'] ?? reset( $presets )['layout'];
	}

	/** Sanitized layout from the option, falling back to the default preset. */
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
		do_action( 'webgram/header/layout_saved', $this->layout );
	}

	/**
	 * Validates a layout array: unknown element ids are stripped, an element may appear once per device, rows and
	 * areas are normalized, row and element settings are sanitized against their field schemas.
	 */
	/**
	 * Element ids provided by Webgram Core. They stay in a stored layout while Core is inactive (the renderer skips
	 * them), so deactivating Core temporarily never strips them from the header.
	 */
	public function reserved_ids(): array {
		return (array) apply_filters( 'webgram/header/reserved_elements', [ 'deliver_to', 'wishlist', 'compare' ] );
	}

	public function sanitize( array $raw ): array {
		$known = array_unique( array_merge( array_keys( $this->elements() ), $this->reserved_ids() ) );
		$out   = [];

		foreach ( self::DEVICES as $device ) {
			$seen = [];
			foreach ( self::ROWS as $row ) {
				$src = (array) ( $raw[ $device ][ $row ] ?? [] );
				foreach ( self::AREAS as $area ) {
					$ids = [];
					foreach ( (array) ( $src[ $area ] ?? [] ) as $id ) {
						$id = is_array( $id ) ? (string) ( $id['id'] ?? '' ) : (string) $id;
						$id = sanitize_key( $id );
						if ( in_array( $id, $known, true ) && ! isset( $seen[ $id ] ) ) {
							$ids[]       = $id;
							$seen[ $id ] = true;
						}
					}
					$out[ $device ][ $row ][ $area ] = $ids;
				}
				$out[ $device ][ $row ]['settings'] = $this->sanitize_row_settings( (array) ( $src['settings'] ?? [] ), $row );
			}
		}

		$sticky        = (array) ( $raw['sticky'] ?? [] );
		$out['sticky'] = [
			'enabled'        => ! empty( $sticky['enabled'] ),
			'rows'           => array_values( array_intersect( array_map( 'strval', (array) ( $sticky['rows'] ?? [] ) ), self::ROWS ) ),
			'shrink'         => ! empty( $sticky['shrink'] ),
			'hide_on_scroll' => ! empty( $sticky['hide_on_scroll'] ),
		];

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
		$d = webgram_header_row_defaults( $row );
		return [
			'enabled'   => [ 'label' => __( 'Show row', 'webgram' ), 'type' => 'switch', 'default' => $d['enabled'] ],
			'height'    => [ 'label' => __( 'Height', 'webgram' ), 'type' => 'number', 'min' => 24, 'max' => 160, 'unit' => 'px', 'default' => $d['height'] ],
			'bg'        => [ 'label' => __( 'Background', 'webgram' ), 'type' => 'text', 'default' => $d['bg'], 'description' => __( 'Color, gradient or a token like var(--wg-color-primary).', 'webgram' ), 'sanitize' => [ self::class, 'sanitize_css_value' ] ],
			'color'     => [ 'label' => __( 'Text and icon color', 'webgram' ), 'type' => 'text', 'default' => $d['color'], 'sanitize' => [ self::class, 'sanitize_css_value' ] ],
			'border'    => [ 'label' => __( 'Bottom border', 'webgram' ), 'type' => 'switch', 'default' => $d['border'] ],
			'shadow'    => [ 'label' => __( 'Shadow', 'webgram' ), 'type' => 'switch', 'default' => $d['shadow'] ],
			'container' => [ 'label' => __( 'Width', 'webgram' ), 'type' => 'radio', 'choices' => [ 'boxed' => __( 'Container', 'webgram' ), 'full' => __( 'Full width', 'webgram' ) ], 'default' => $d['container'] ],
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

	/** Allows colors, gradients and var() tokens; rejects anything that could escape a style attribute. */
	public static function sanitize_css_value( mixed $value ): string {
		$value = trim( (string) $value );
		if ( '' === $value || strlen( $value ) > 160 ) {
			return '';
		}
		if ( ! preg_match( '/^[a-zA-Z0-9#(),.%\s\-]+$/', $value ) ) {
			return '';
		}
		if ( preg_match( '/(url|expression|javascript|@import)/i', $value ) ) {
			return '';
		}
		return $value;
	}

	/** Settings of one element merged with its defaults. */
	public function element_settings( string $id ): array {
		$el = $this->element( $id );
		if ( ! $el ) {
			return [];
		}
		return array_merge( $el->defaults(), (array) ( $this->layout()['elements'][ $id ] ?? [] ) );
	}

	/** Whether an element is placed anywhere for the given device (or any device). */
	public function has_element( string $id, string $device = '' ): bool {
		$layout  = $this->layout();
		$devices = $device ? [ $device ] : self::DEVICES;
		foreach ( $devices as $d ) {
			foreach ( self::ROWS as $row ) {
				if ( empty( $layout[ $d ][ $row ]['settings']['enabled'] ) ) {
					continue;
				}
				foreach ( self::AREAS as $area ) {
					if ( in_array( $id, $layout[ $d ][ $row ][ $area ], true ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}
}
