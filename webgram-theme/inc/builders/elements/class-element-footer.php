<?php
/**
 * Footer-only elements: description, menus with heading, widget areas, newsletter hook, payment icons, trust text,
 * copyright, contact info.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

final class Webgram_Element_Footer_Description extends Webgram_Element {

	public function id(): string {
		return 'description';
	}

	public function label(): string {
		return __( 'Brand description', 'webgram' );
	}

	public function icon(): string {
		return 'type';
	}

	public function group(): string {
		return 'brand';
	}

	public function settings_fields(): array {
		return [
			'text' => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'textarea', 'rows' => 3, 'default' => '', 'description' => __( 'Leave empty to use the site tagline.', 'webgram' ) ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$text = trim( (string) $settings['text'] ) ?: (string) get_bloginfo( 'description' );
		if ( '' !== $text ) {
			printf( '<p class="wg-footer__description">%s</p>', esc_html( $text ) );
		}
	}
}

final class Webgram_Element_Footer_Menu extends Webgram_Element {

	public function __construct( private int $index = 1 ) {}

	public function id(): string {
		return 'menu_' . $this->index;
	}

	public function label(): string {
		/* translators: %d: menu slot number */
		return sprintf( __( 'Menu %d', 'webgram' ), $this->index );
	}

	public function icon(): string {
		return 'list';
	}

	public function group(): string {
		return 'navigation';
	}

	public function settings_fields(): array {
		$defaults = [ 1 => __( 'Categories', 'webgram' ), 2 => __( 'Policy', 'webgram' ), 3 => __( 'Support', 'webgram' ) ];
		return [
			'heading' => [ 'label' => __( 'Heading', 'webgram' ), 'type' => 'text', 'default' => $defaults[ $this->index ] ?? '' ],
			'menu'    => [ 'label' => __( 'Menu', 'webgram' ), 'type' => 'menu', 'default' => 0 ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$menu = (int) $settings['menu'];
		$args = [ 'container' => false, 'menu_class' => 'wg-footer__menu', 'fallback_cb' => false, 'depth' => 1 ];
		if ( $menu > 0 ) {
			$args['menu'] = $menu;
		} elseif ( 1 === $this->index && has_nav_menu( 'footer' ) ) {
			$args['theme_location'] = 'footer';
		} else {
			return;
		}
		if ( ! empty( $settings['heading'] ) ) {
			printf( '<h3 class="wg-footer__heading">%s</h3>', esc_html( (string) $settings['heading'] ) );
		}
		wp_nav_menu( $args );
	}
}

final class Webgram_Element_Footer_Widget_Area extends Webgram_Element {

	public function __construct( private int $index = 1 ) {}

	public function id(): string {
		return 'widget_area_' . $this->index;
	}

	public function label(): string {
		/* translators: %d: widget area number */
		return sprintf( __( 'Widget area %d', 'webgram' ), $this->index );
	}

	public function icon(): string {
		return 'layout';
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( is_active_sidebar( 'footer-' . $this->index ) ) {
			dynamic_sidebar( 'footer-' . $this->index );
		}
	}
}

/** Newsletter: renders a form provided by any newsletter plugin through webgram/footer/newsletter. No own list. */
final class Webgram_Element_Footer_Newsletter extends Webgram_Element {

	public function id(): string {
		return 'newsletter';
	}

	public function label(): string {
		return __( 'Newsletter form (plugin hook)', 'webgram' );
	}

	public function icon(): string {
		return 'mail';
	}

	public function settings_fields(): array {
		return [
			'heading'   => [ 'label' => __( 'Heading', 'webgram' ), 'type' => 'text', 'default' => __( 'Newsletter', 'webgram' ) ],
			'text'      => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'textarea', 'rows' => 2, 'default' => '' ],
			'shortcode' => [ 'label' => __( 'Form shortcode', 'webgram' ), 'type' => 'text', 'default' => '', 'description' => __( 'Paste the shortcode of your newsletter or form plugin, e.g. Contact Form 7 or MailPoet.', 'webgram' ) ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		ob_start();
		do_action( 'webgram/footer/newsletter' );
		if ( '' !== trim( (string) $settings['shortcode'] ) && str_starts_with( trim( (string) $settings['shortcode'] ), '[' ) ) {
			echo do_shortcode( (string) $settings['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
		}
		$form = trim( (string) ob_get_clean() );
		if ( '' === $form ) {
			return;
		}
		if ( ! empty( $settings['heading'] ) ) {
			printf( '<h3 class="wg-footer__heading">%s</h3>', esc_html( (string) $settings['heading'] ) );
		}
		if ( ! empty( $settings['text'] ) ) {
			printf( '<p>%s</p>', esc_html( (string) $settings['text'] ) );
		}
		echo '<div class="wg-footer__newsletter">' . $form . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin output.
	}
}

final class Webgram_Element_Footer_Payment_Icons extends Webgram_Element {

	public function id(): string {
		return 'payment_icons';
	}

	public function label(): string {
		return __( 'Payment icons', 'webgram' );
	}

	public function icon(): string {
		return 'credit-card';
	}

	public function settings_fields(): array {
		return [ 'heading' => [ 'label' => __( 'Label before icons', 'webgram' ), 'type' => 'text', 'default' => '' ] ];
	}

	public function render( array $settings, string $device, string $context ): void {
		$icons = (array) webgram_option( 'footer_payment_icons' );
		if ( ! $icons ) {
			return;
		}
		echo '<div class="wg-payments">';
		if ( ! empty( $settings['heading'] ) ) {
			printf( '<span class="wg-payments__label">%s</span>', esc_html( (string) $settings['heading'] ) );
		}
		foreach ( $icons as $slug ) {
			webgram_payment_icon( (string) $slug );
		}
		echo '</div>';
	}
}

final class Webgram_Element_Footer_Trust_Text extends Webgram_Element {

	public function id(): string {
		return 'trust_text';
	}

	public function label(): string {
		return __( 'Trust text', 'webgram' );
	}

	public function icon(): string {
		return 'shield';
	}

	public function settings_fields(): array {
		return [
			'icon' => [ 'label' => __( 'Icon', 'webgram' ), 'type' => 'icon', 'default' => 'shield' ],
			'text' => [ 'label' => __( 'Text', 'webgram' ), 'type' => 'text', 'default' => __( '100% secure payments', 'webgram' ) ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		if ( '' === $settings['text'] ) {
			return;
		}
		echo '<p class="wg-trust-text">' . webgram_icon( (string) $settings['icon'], '', false ) . '<span>' . esc_html( (string) $settings['text'] ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

final class Webgram_Element_Footer_Copyright extends Webgram_Element {

	public function id(): string {
		return 'copyright';
	}

	public function label(): string {
		return __( 'Copyright', 'webgram' );
	}

	public function icon(): string {
		return 'copyright';
	}

	public function render( array $settings, string $device, string $context ): void {
		$text = webgram_replace_placeholders( (string) webgram_option( 'footer_copyright' ) );
		if ( '' !== trim( $text ) ) {
			printf( '<p class="wg-footer__copyright">%s</p>', wp_kses_post( $text ) );
		}
		do_action( 'webgram/footer/before_copyright_end' );
	}
}

final class Webgram_Element_Footer_Contact extends Webgram_Element {

	public function id(): string {
		return 'contact';
	}

	public function label(): string {
		return __( 'Contact info', 'webgram' );
	}

	public function icon(): string {
		return 'map-pin';
	}

	public function settings_fields(): array {
		return [
			'heading' => [ 'label' => __( 'Heading', 'webgram' ), 'type' => 'text', 'default' => __( 'Contact', 'webgram' ) ],
			'address' => [ 'label' => __( 'Address', 'webgram' ), 'type' => 'textarea', 'rows' => 2, 'default' => '' ],
			'phone'   => [ 'label' => __( 'Phone', 'webgram' ), 'type' => 'text', 'default' => '' ],
			'email'   => [ 'label' => __( 'Email', 'webgram' ), 'type' => 'email', 'default' => '' ],
			'hours'   => [ 'label' => __( 'Hours', 'webgram' ), 'type' => 'text', 'default' => '' ],
		];
	}

	public function render( array $settings, string $device, string $context ): void {
		$rows = [];
		if ( $settings['address'] ) {
			$rows[] = [ 'map-pin', nl2br( esc_html( (string) $settings['address'] ) ), '' ];
		}
		if ( $settings['phone'] ) {
			$rows[] = [ 'phone', esc_html( (string) $settings['phone'] ), 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $settings['phone'] ) ];
		}
		if ( $settings['email'] ) {
			$rows[] = [ 'mail', esc_html( (string) $settings['email'] ), 'mailto:' . (string) $settings['email'] ];
		}
		if ( $settings['hours'] ) {
			$rows[] = [ 'clock', esc_html( (string) $settings['hours'] ), '' ];
		}
		if ( ! $rows ) {
			return;
		}
		if ( ! empty( $settings['heading'] ) ) {
			printf( '<h3 class="wg-footer__heading">%s</h3>', esc_html( (string) $settings['heading'] ) );
		}
		echo '<ul class="wg-footer__contact">';
		foreach ( $rows as [ $icon, $html, $href ] ) {
			echo '<li>' . webgram_icon( $icon, '', false ) . ( $href ? '<a href="' . esc_url( $href ) . '">' . $html . '</a>' : '<span>' . $html . '</span>' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		}
		echo '</ul>';
	}
}
