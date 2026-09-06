<?php
namespace Webgram\Core\Modules\SiteTools;

defined( 'ABSPATH' ) || exit;

/** Custom header and footer scripts, printed via wp_add_inline_script on empty registered handles. */
final class CustomJs {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ], 99 );
	}

	public function enqueue(): void {
		if ( is_admin() ) {
			return;
		}
		$s      = $this->module->settings();
		$header = trim( (string) $s->get( 'js_header', '' ) );
		$footer = trim( (string) $s->get( 'js_footer', '' ) );
		if ( '' !== $header ) {
			wp_register_script( 'webgram-core-custom-head', false, [], WEBGRAM_CORE_VERSION, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script( 'webgram-core-custom-head' );
			wp_add_inline_script( 'webgram-core-custom-head', self::strip_tags( $header ) );
		}
		if ( '' !== $footer ) {
			wp_register_script( 'webgram-core-custom-foot', false, [], WEBGRAM_CORE_VERSION, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script( 'webgram-core-custom-foot' );
			wp_add_inline_script( 'webgram-core-custom-foot', self::strip_tags( $footer ) );
		}
	}

	/** Owners often paste <script> wrappers; strip them so the inline script stays valid. */
	public static function strip_tags( string $js ): string {
		$js = preg_replace( '/<\/?\s*script[^>]*>/i', '', $js ) ?? '';
		return trim( $js );
	}
}
