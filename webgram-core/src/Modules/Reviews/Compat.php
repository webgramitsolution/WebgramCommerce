<?php
namespace Webgram\Core\Modules\Reviews;

defined( 'ABSPATH' ) || exit;

/** Detects third-party review plugins so both systems never render at once. */
final class Compat {

	private const PLUGINS = [
		'judgeme'                          => 'Judge.me',
		'yith-woocommerce-advanced-reviews' => 'YITH Advanced Reviews',
		'customer-reviews-woocommerce'     => 'Customer Reviews for WooCommerce',
	];

	/** Pure: first known plugin found in the active plugin file list, or ''. */
	public static function detect( array $plugin_files ): string {
		foreach ( $plugin_files as $file ) {
			$dir = strtolower( strtok( (string) $file, '/' ) ?: '' );
			foreach ( self::PLUGINS as $slug => $name ) {
				if ( str_contains( $dir, $slug ) ) {
					return $name;
				}
			}
		}
		return '';
	}

	public static function third_party(): string {
		$files = (array) get_option( 'active_plugins', [] );
		if ( is_multisite() ) {
			$files = array_merge( $files, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
		}
		return self::detect( $files );
	}
}
