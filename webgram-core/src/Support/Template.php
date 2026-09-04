<?php
namespace Webgram\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Template locator with theme override support, mirroring WooCommerce's approach.
 *
 * Resolution order:
 *   1. {child theme}/webgram-core/{path}.php
 *   2. {parent theme}/webgram-core/{path}.php
 *   3. {plugin}/templates/{path}.php
 */
final class Template {

	public function locate( string $path ): string {
		$path = ltrim( str_replace( '..', '', $path ), '/' );
		if ( ! str_ends_with( $path, '.php' ) ) {
			$path .= '.php';
		}

		$theme_file = locate_template( [ 'webgram-core/' . $path ] );
		if ( $theme_file ) {
			return $theme_file;
		}

		$plugin_file = WEBGRAM_CORE_PATH . 'templates/' . $path;
		$located     = is_readable( $plugin_file ) ? $plugin_file : '';

		return (string) apply_filters( 'webgram_core/locate_template', $located, $path );
	}

	public function render( string $path, array $args = [], bool $echo = true ): string {
		$file = $this->locate( $path );

		if ( '' === $file ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				webgram_core()->logger()->warning( 'Template not found: ' . $path );
			}
			return '';
		}

		$args = (array) apply_filters( 'webgram_core/template_args', $args, $path );

		ob_start();
		( static function ( string $__file, array $args ): void {
			// Expose $args as variables for template readability, plus $args itself.
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $__file;
		} )( $file, $args );
		$html = (string) ob_get_clean();

		if ( $echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- templates escape their own output.
		}
		return $html;
	}
}
