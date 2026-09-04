<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/** Promo popup: HTML Block or simple content, trigger by delay, scroll depth or exit intent, frequency by cookie. */
final class PromoPopup {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'wp_footer', [ $this, 'render' ], 20 );
	}

	public function should_show(): bool {
		$s = $this->module->settings();
		if ( ! $s->get( 'popup_enabled', false ) || is_admin() || is_customize_preview() ) {
			return false;
		}
		if ( ! in_array( Helpers::device(), (array) $s->get( 'popup_devices', [ 'desktop', 'tablet', 'mobile' ] ), true ) ) {
			return false;
		}
		if ( ! in_array( Helpers::page_type(), (array) $s->get( 'popup_pages', [] ), true ) ) {
			return false;
		}
		if ( ! $s->get( 'popup_block', 0 ) && '' === trim( (string) $s->get( 'popup_content', '' ) ) ) {
			return false;
		}
		return (bool) apply_filters( 'webgram_core/promo_popup/show', true );
	}

	public function render(): void {
		if ( ! $this->should_show() ) {
			return;
		}
		$s = $this->module->settings();
		\webgram_core()->assets()->enqueue_module( 'site_tools' );
		$this->module_view(
			'promo-popup',
			[
				'content'   => $s->get( 'popup_block', 0 ) ? Blocks::render( (int) $s->get( 'popup_block' ) ) : wpautop( wp_kses_post( (string) $s->get( 'popup_content', '' ) ) ),
				'image'     => (int) $s->get( 'popup_image', 0 ),
				'width'     => (int) $s->get( 'popup_width', 640 ),
				'trigger'   => (string) $s->get( 'popup_trigger', 'delay' ),
				'delay'     => (int) $s->get( 'popup_delay', 5 ),
				'scroll'    => (int) $s->get( 'popup_scroll', 40 ),
				'frequency' => (string) $s->get( 'popup_frequency', 'day' ),
				'key'       => 'wg_popup_seen_' . substr( md5( (string) wp_json_encode( [ $s->get( 'popup_block' ), $s->get( 'popup_content' ) ] ) ), 0, 8 ),
			]
		);
	}

	private function module_view( string $name, array $args ): void {
		\webgram_core()->view( 'site-tools/' . $name, $args );
	}
}
