<?php
namespace Webgram\Core\Modules\Emails;

defined( 'ABSPATH' ) || exit;

/** Pure branding token helpers shared by the email templates, the styles filter and the preview. */
final class Branding {

	public static function defaults(): array {
		return [
			'use_templates'  => true,
			'logo'           => 0,
			'logo_width'     => 160,
			'header_bg'      => '#a0181f',
			'header_text'    => '#ffffff',
			'body_bg'        => '#f3f4f6',
			'content_bg'     => '#ffffff',
			'text_color'     => '#1f2937',
			'link_color'     => '#a0181f',
			'button_color'   => '#a0181f',
			'button_text'    => '#ffffff',
			'button_radius'  => 8,
			'width'          => 600,
			'font'           => 'helvetica',
			'footer_text'    => '',
			'social'         => '',
			'attach_invoice' => [ 'customer_completed_order', 'customer_invoice' ],
		];
	}

	/** Pure: settings to a token array with validated colors and sizes. */
	public static function tokens( array $s ): array {
		$d   = self::defaults();
		$hex = static fn( $v, string $fallback ) => preg_match( '/^#[0-9a-f]{6}$/i', (string) $v ) ? strtolower( (string) $v ) : $fallback;
		return [
			'header_bg'     => $hex( $s['header_bg'] ?? '', $d['header_bg'] ),
			'header_text'   => $hex( $s['header_text'] ?? '', $d['header_text'] ),
			'body_bg'       => $hex( $s['body_bg'] ?? '', $d['body_bg'] ),
			'content_bg'    => $hex( $s['content_bg'] ?? '', $d['content_bg'] ),
			'text_color'    => $hex( $s['text_color'] ?? '', $d['text_color'] ),
			'link_color'    => $hex( $s['link_color'] ?? '', $d['link_color'] ),
			'button_color'  => $hex( $s['button_color'] ?? '', $d['button_color'] ),
			'button_text'   => $hex( $s['button_text'] ?? '', $d['button_text'] ),
			'button_radius' => max( 0, min( 40, (int) ( $s['button_radius'] ?? $d['button_radius'] ) ) ),
			'width'         => max( 480, min( 800, (int) ( $s['width'] ?? $d['width'] ) ) ),
			'font'          => self::font_stack( (string) ( $s['font'] ?? $d['font'] ) ),
			'logo_width'    => max( 60, min( 400, (int) ( $s['logo_width'] ?? $d['logo_width'] ) ) ),
		];
	}

	/** Pure: email safe font stacks. */
	public static function font_stack( string $key ): string {
		return match ( $key ) {
			'georgia'  => "Georgia, 'Times New Roman', serif",
			'arial'    => 'Arial, Helvetica, sans-serif',
			'verdana'  => 'Verdana, Geneva, sans-serif',
			'trebuchet' => "'Trebuchet MS', Helvetica, sans-serif",
			default    => "'Helvetica Neue', Helvetica, Arial, sans-serif",
		};
	}

	/** Pure: "Label|URL" lines to [{label, url}]. */
	public static function social_links( string $text ): array {
		$out = [];
		foreach ( preg_split( '/\r?\n/', trim( $text ) ) ?: [] as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( count( $parts ) === 2 && '' !== $parts[0] && preg_match( '#^https?://#i', $parts[1] ) ) {
				$out[] = [ 'label' => $parts[0], 'url' => $parts[1] ];
			}
		}
		return $out;
	}

	/** Pure: CSS appended to WooCommerce's email styles. */
	public static function css( array $t ): string {
		return sprintf(
			'#wrapper{background-color:%1$s !important;font-family:%6$s}#template_container{border-radius:12px;box-shadow:none !important;background-color:%7$s !important}#template_header{background-color:%2$s !important;color:%3$s !important;border-radius:12px 12px 0 0}#template_header h1{color:%3$s !important;font-family:%6$s}#body_content_inner,#body_content td,#body_content p,#body_content li{color:%4$s !important;font-family:%6$s}a{color:%5$s !important}#template_footer #credit{color:%4$s !important;opacity:.7}.wgc-email-button,a.wgc-email-button,.button,.wc-button{display:inline-block;padding:12px 24px;background-color:%8$s !important;color:%9$s !important;border-radius:%10$dpx;text-decoration:none !important;font-weight:bold}.td{border-color:#e5e7eb !important}.wgc-email-social a{display:inline-block;margin:0 6px;color:%4$s !important;font-size:12px}',
			$t['body_bg'],
			$t['header_bg'],
			$t['header_text'],
			$t['text_color'],
			$t['link_color'],
			$t['font'],
			$t['content_bg'],
			$t['button_color'],
			$t['button_text'],
			$t['button_radius']
		);
	}
}
