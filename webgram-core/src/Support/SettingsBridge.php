<?php
namespace Webgram\Core\Support;

use Webgram\Core\Abstracts\Module;
use Webgram\Core\Modules\SiteTools\Settings as SiteToolsSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Attaches a module's tab definitions (shared field schema) to the theme settings panel with Core-owned storage,
 * and flattens the same definitions for the Core settings screen when the theme panel is absent.
 */
final class SettingsBridge {

	/**
	 * @param array<string, array>|callable $tabs id => tab definition (label, icon, priority, sections), or a
	 *                                            callable returning them (resolved only when the panel renders).
	 */
	public static function attach( Module $module, array|callable $tabs ): void {
		add_filter(
			'webgram/settings/tabs',
			static function ( array $all ) use ( $module, $tabs ): array {
				$tabs = is_callable( $tabs ) ? (array) $tabs() : $tabs;
				foreach ( $tabs as $id => $tab ) {
					$all[ $id ] = self::tab( $module, $tab );
				}
				return $all;
			}
		);
	}

	public static function tab( Module $module, array $tab ): array {
		$fields = self::flatten( $tab );
		$tab['owner']  = 'core';
		$tab['values'] = static fn() => $module->settings()->all();
		$tab['save']   = static function ( array $values ) use ( $module, $fields ): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$clean = SiteToolsSettings::sanitize_values( $fields, $values );
			foreach ( $fields as $fid => $field ) {
				if ( 'secret' === ( $field['type'] ?? '' ) && array_key_exists( $fid, $clean ) ) {
					if ( '' === $clean[ $fid ] ) {
						unset( $clean[ $fid ] ); // Empty submission keeps the stored secret; use "clear" to remove.
					} else {
						$clean[ $fid ] = \webgram_core()->crypto()->encrypt( (string) $clean[ $fid ] );
					}
				}
			}
			$module->settings()->save( array_merge( $module->settings()->all(), $clean ) );
			do_action( 'webgram_core/settings_saved', $module->id(), $module->settings()->all() );
		};
		$tab['reset'] = static function () use ( $module, $fields ): void {
			$all = $module->settings()->all();
			foreach ( array_keys( $fields ) as $fid ) {
				unset( $all[ $fid ] );
			}
			$module->settings()->save( $all );
		};
		return $tab;
	}

	/** @return array<string, array> id => field */
	public static function flatten( array $tab ): array {
		$fields = [];
		foreach ( (array) ( $tab['sections'] ?? [] ) as $section ) {
			foreach ( (array) ( $section['fields'] ?? [] ) as $fid => $field ) {
				$field['id']    = $fid;
				$fields[ $fid ] = $field;
			}
		}
		return $fields;
	}

	/** Fields for the Core settings screen: same definitions, panel-only types replaced by their fallbacks. */
	public static function fallback_fields( array $tabs, array $replacements = [] ): array {
		$out = [];
		foreach ( $tabs as $tab ) {
			$out[] = [ 'id' => 'heading_' . $tab['id'], 'label' => $tab['label'], 'type' => 'heading', 'description' => $tab['description'] ?? '' ];
			foreach ( self::flatten( $tab ) as $fid => $field ) {
				if ( isset( $replacements[ $fid ] ) ) {
					$field = $replacements[ $fid ] ? $replacements[ $fid ] + [ 'id' => $fid ] : null;
				} elseif ( in_array( $field['type'] ?? '', [ 'repeater', 'sortable', 'radio_image', 'icon', 'dimensions', 'typography', 'code', 'menu' ], true ) ) {
					$field = null;
				}
				if ( $field ) {
					if ( 'switch' === $field['type'] ) {
						$field['type'] = 'checkbox';
					}
					if ( isset( $field['choices'] ) ) {
						$field['options'] = $field['choices'];
					}
					$out[] = $field;
				}
			}
		}
		return $out;
	}
}
