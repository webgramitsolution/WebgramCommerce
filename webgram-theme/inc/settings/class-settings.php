<?php
/**
 * Theme Settings storage and tab registry.
 *
 * All theme design settings live in one autoloaded option, webgram_theme_settings. Tabs are declared as PHP arrays
 * in inc/settings/tabs/*.php and extended by Webgram Core through the webgram/settings/tabs filter. Core-owned tabs
 * declare their own `values` and `save` callbacks so their data stays in Core options.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Settings {

	public const OPTION = 'webgram_theme_settings';

	private static ?Webgram_Settings $instance = null;

	/** @var array<string, mixed>|null */
	private ?array $values = null;

	/** @var array<string, array>|null */
	private ?array $tabs = null;

	public static function instance(): Webgram_Settings {
		return self::$instance ??= new self();
	}

	/** Stored values merged over defaults. */
	public function all(): array {
		if ( null === $this->values ) {
			$stored       = get_option( self::OPTION, [] );
			$this->values = array_merge( webgram_defaults(), is_array( $stored ) ? $stored : [] );
		}
		return $this->values;
	}

	public function get( string $key, mixed $default = null ): mixed {
		$all = $this->all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/** Raw stored values only (no defaults), used by export. */
	public function stored(): array {
		$stored = get_option( self::OPTION, [] );
		return is_array( $stored ) ? $stored : [];
	}

	/**
	 * Merge sanitized values into the option. Callers sanitize first through Webgram_Settings_Sanitizer.
	 *
	 * @param array<string, mixed> $values
	 */
	public function update( array $values ): void {
		$stored = array_merge( $this->stored(), $values );
		update_option( self::OPTION, $stored, true );
		$this->values = null;
		$this->flush_caches();
		do_action( 'webgram/settings/updated', $values );
	}

	/** Remove stored values for the given keys (falls back to defaults). Empty list resets everything. */
	public function reset( array $keys = [] ): void {
		if ( ! $keys ) {
			delete_option( self::OPTION );
		} else {
			$stored = $this->stored();
			foreach ( $keys as $key ) {
				unset( $stored[ $key ] );
			}
			update_option( self::OPTION, $stored, true );
		}
		$this->values = null;
		$this->flush_caches();
		do_action( 'webgram/settings/reset', $keys );
	}

	public function flush_caches(): void {
		if ( class_exists( 'Webgram_CSS_Generator' ) ) {
			Webgram_CSS_Generator::instance()->flush();
		}
		delete_transient( 'webgram_mega_menu' );
	}

	/**
	 * Tab registry. Each tab: id, label, icon, priority, owner (theme|core), sections => [ id => [ label, fields ] ].
	 * Field keys: id, label, type, default, description, choices, show_if, plus type specific keys.
	 *
	 * @return array<string, array>
	 */
	public function tabs(): array {
		if ( null !== $this->tabs ) {
			return $this->tabs;
		}

		$tabs = [];
		foreach ( glob( WEBGRAM_DIR . '/inc/settings/tabs/*.php' ) ?: [] as $file ) {
			$tab = require $file;
			if ( is_array( $tab ) && ! empty( $tab['id'] ) ) {
				$tab['owner']       = 'theme';
				$tabs[ $tab['id'] ] = $tab;
			}
		}

		$tabs = (array) apply_filters( 'webgram/settings/tabs', $tabs );

		foreach ( $tabs as $id => &$tab ) {
			$tab['id']       = $id;
			$tab['owner']    = $tab['owner'] ?? 'core';
			$tab['priority'] = (int) ( $tab['priority'] ?? 100 );
			$tab['sections'] = (array) ( $tab['sections'] ?? [] );
		}
		unset( $tab );

		uasort( $tabs, static fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

		$this->tabs = $tabs;
		return $tabs;
	}

	public function tab( string $id ): ?array {
		return $this->tabs()[ $id ] ?? null;
	}

	/** Flat field list for a tab (id => field). */
	public function fields( string $tab_id ): array {
		$tab = $this->tab( $tab_id );
		if ( ! $tab ) {
			return [];
		}
		$fields = [];
		foreach ( $tab['sections'] as $section ) {
			foreach ( (array) ( $section['fields'] ?? [] ) as $id => $field ) {
				$field['id']   = $id;
				$fields[ $id ] = $field;
			}
		}
		return $fields;
	}

	/** Every theme-owned field across all tabs (used by import validation and reset). */
	public function theme_fields(): array {
		$fields = [];
		foreach ( $this->tabs() as $id => $tab ) {
			if ( 'theme' === $tab['owner'] ) {
				$fields += $this->fields( $id );
			}
		}
		return $fields;
	}

	/** Current values for a tab, honoring a Core-owned tab's `values` callback. */
	public function tab_values( string $tab_id ): array {
		$tab = $this->tab( $tab_id );
		if ( ! $tab ) {
			return [];
		}
		if ( ! empty( $tab['values'] ) && is_callable( $tab['values'] ) ) {
			$values = (array) call_user_func( $tab['values'] );
		} else {
			$values = $this->all();
		}
		$out = [];
		foreach ( $this->fields( $tab_id ) as $id => $field ) {
			$out[ $id ] = array_key_exists( $id, $values ) ? $values[ $id ] : ( $field['default'] ?? ( webgram_defaults()[ $id ] ?? '' ) );
		}
		return $out;
	}

	/** Persist sanitized values for a tab through its owner. */
	public function save_tab( string $tab_id, array $values ): void {
		$tab = $this->tab( $tab_id );
		if ( ! $tab ) {
			return;
		}
		if ( ! empty( $tab['save'] ) && is_callable( $tab['save'] ) ) {
			call_user_func( $tab['save'], $values );
			return;
		}
		$this->update( $values );
	}
}
