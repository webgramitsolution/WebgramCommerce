<?php
namespace Webgram\Core\Modules\Emails;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Branded WooCommerce emails: header, footer and styles templates swapped through woocommerce_locate_template
 * only when enabled, extra CSS through woocommerce_email_styles, preview and test page, invoice attachments.
 * Every WooCommerce email hook stays in place; delivery stays with wp_mail and the store's SMTP plugin.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'emails';
	}

	public function name(): string {
		return __( 'Branded Emails', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Logo, colors and layout for every WooCommerce email, preview and test send, invoice attachments.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 7;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		if ( $this->enabled() ) {
			add_filter( 'woocommerce_locate_template', [ $this, 'locate_template' ], 20, 2 );
			add_filter( 'woocommerce_email_styles', [ $this, 'styles' ], 20 );
			add_filter( 'woocommerce_email_footer_text', [ $this, 'footer_text' ] );
		}
		add_filter( 'woocommerce_email_attachments', [ $this, 'attachments' ], 10, 3 );
		if ( is_admin() ) {
			( new Preview( $this ) )->register();
		}
	}

	public function enabled(): bool {
		return Helpers::bool( $this->settings()->get( 'use_templates', true ) );
	}

	public function settings_fields(): array {
		$d = Branding::defaults();
		return [
			[ 'id' => 'use_templates', 'label' => __( 'Use Webgram email templates', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Off restores the WooCommerce default header, footer and styles.', 'webgram-core' ) ],
			[ 'id' => 'logo', 'label' => __( 'Logo', 'webgram-core' ), 'type' => 'image', 'default' => 0 ],
			[ 'id' => 'logo_width', 'label' => __( 'Logo width (px)', 'webgram-core' ), 'type' => 'number', 'min' => 60, 'max' => 400, 'default' => $d['logo_width'] ],
			[ 'id' => 'header_bg', 'label' => __( 'Header background', 'webgram-core' ), 'type' => 'color', 'default' => $d['header_bg'] ],
			[ 'id' => 'header_text', 'label' => __( 'Header text color', 'webgram-core' ), 'type' => 'color', 'default' => $d['header_text'] ],
			[ 'id' => 'body_bg', 'label' => __( 'Page background', 'webgram-core' ), 'type' => 'color', 'default' => $d['body_bg'] ],
			[ 'id' => 'content_bg', 'label' => __( 'Content background', 'webgram-core' ), 'type' => 'color', 'default' => $d['content_bg'] ],
			[ 'id' => 'text_color', 'label' => __( 'Text color', 'webgram-core' ), 'type' => 'color', 'default' => $d['text_color'] ],
			[ 'id' => 'link_color', 'label' => __( 'Link color', 'webgram-core' ), 'type' => 'color', 'default' => $d['link_color'] ],
			[ 'id' => 'button_color', 'label' => __( 'Button color', 'webgram-core' ), 'type' => 'color', 'default' => $d['button_color'] ],
			[ 'id' => 'button_text', 'label' => __( 'Button text color', 'webgram-core' ), 'type' => 'color', 'default' => $d['button_text'] ],
			[ 'id' => 'button_radius', 'label' => __( 'Button radius (px)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 40, 'default' => $d['button_radius'] ],
			[ 'id' => 'width', 'label' => __( 'Layout width (px)', 'webgram-core' ), 'type' => 'number', 'min' => 480, 'max' => 800, 'default' => $d['width'] ],
			[ 'id' => 'font', 'label' => __( 'Font family', 'webgram-core' ), 'type' => 'select', 'options' => [ 'helvetica' => 'Helvetica / Arial', 'arial' => 'Arial', 'georgia' => 'Georgia (serif)', 'verdana' => 'Verdana', 'trebuchet' => 'Trebuchet MS' ], 'default' => $d['font'] ],
			[ 'id' => 'footer_text', 'label' => __( 'Footer text', 'webgram-core' ), 'type' => 'textarea', 'default' => '', 'description' => __( 'Placeholders: {site_title}, {site_url}, {year}. Empty keeps the WooCommerce footer text.', 'webgram-core' ) ],
			[ 'id' => 'social', 'label' => __( 'Social links (one per line: Label|URL)', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'attach_invoice', 'label' => __( 'Attach the invoice PDF to', 'webgram-core' ), 'type' => 'text', 'default' => implode( ',', $d['attach_invoice'] ), 'description' => __( 'Comma separated WooCommerce email ids: customer_completed_order, customer_invoice, customer_processing_order. Needs the Invoice module.', 'webgram-core' ) ],
		];
	}

	public function tokens(): array {
		return Branding::tokens( $this->settings()->all() );
	}

	/** Swap only the three layout templates; a theme override in {theme}/woocommerce/emails/ still wins. */
	public function locate_template( string $template, string $template_name ): string {
		if ( ! in_array( $template_name, [ 'emails/email-header.php', 'emails/email-footer.php', 'emails/email-styles.php' ], true ) ) {
			return $template;
		}
		$theme_override = locate_template( [ trailingslashit( WC()->template_path() ) . $template_name, $template_name ] );
		if ( $theme_override ) {
			return $template;
		}
		$core = WEBGRAM_CORE_PATH . 'templates/' . $template_name;
		return is_readable( $core ) ? $core : $template;
	}

	public function styles( string $css ): string {
		return $css . "\n" . Branding::css( $this->tokens() );
	}

	public function footer_text( string $text ): string {
		$custom = trim( (string) $this->settings()->get( 'footer_text', '' ) );
		if ( '' === $custom ) {
			return $text;
		}
		return strtr( $custom, [ '{site_title}' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), '{site_url}' => home_url( '/' ), '{year}' => gmdate( 'Y' ) ] );
	}

	/** Attach the invoice PDF through the Invoice module (no PDF code lives here). */
	public function attachments( array $attachments, string $email_id, $object ): array {
		$ids = array_filter( array_map( 'trim', explode( ',', (string) $this->settings()->get( 'attach_invoice', 'customer_completed_order,customer_invoice' ) ) ) );
		if ( ! in_array( $email_id, $ids, true ) || ! $object instanceof \WC_Order ) {
			return $attachments;
		}
		$invoice = \webgram_core()->modules()->get( 'invoice' );
		if ( ! $invoice || ! \webgram_core()->modules()->is_active( 'invoice' ) || ! method_exists( $invoice, 'attachment_for' ) ) {
			return $attachments;
		}
		$path = $invoice->attachment_for( $object );
		if ( $path && ! in_array( $path, $attachments, true ) ) {
			$attachments[] = $path;
		}
		return $attachments;
	}
}
