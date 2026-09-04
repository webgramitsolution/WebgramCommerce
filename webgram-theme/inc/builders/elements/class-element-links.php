<?php
/**
 * Icon-with-label link elements: Track order, Bulk order, Help. Each links to a chosen page or custom URL.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

abstract class Webgram_Element_Page_Link extends Webgram_Element {

	abstract protected function default_label(): string;

	abstract protected function default_icon(): string;

	public function group(): string {
		return 'actions';
	}

	public function icon(): string {
		return $this->default_icon();
	}

	public function settings_fields(): array {
		return $this->icon_fields( $this->default_label(), $this->default_icon() ) + [
			'page' => [ 'label' => __( 'Page', 'webgram' ), 'type' => 'page', 'default' => 0 ],
			'url'  => [ 'label' => __( 'Custom URL (overrides page)', 'webgram' ), 'type' => 'url', 'default' => '' ],
		];
	}

	protected function url( array $settings ): string {
		if ( ! empty( $settings['url'] ) ) {
			return (string) $settings['url'];
		}
		if ( ! empty( $settings['page'] ) ) {
			return (string) get_permalink( (int) $settings['page'] );
		}
		return (string) apply_filters( 'webgram/header/link_url', '', $this->id() );
	}

	public function render( array $settings, string $device, string $context ): void {
		$url = $this->url( $settings );
		if ( '' === $url ) {
			return;
		}
		$this->icon_link( $url, $this->default_icon(), (string) $settings['label'], $settings );
	}
}

final class Webgram_Element_Track_Order extends Webgram_Element_Page_Link {

	public function id(): string {
		return 'track_order';
	}

	public function label(): string {
		return __( 'Track order', 'webgram' );
	}

	protected function default_label(): string {
		return __( 'Track Order', 'webgram' );
	}

	protected function default_icon(): string {
		return 'truck';
	}
}

final class Webgram_Element_Bulk_Order extends Webgram_Element_Page_Link {

	public function id(): string {
		return 'bulk_order';
	}

	public function label(): string {
		return __( 'Bulk order', 'webgram' );
	}

	protected function default_label(): string {
		return __( 'Bulk Order', 'webgram' );
	}

	protected function default_icon(): string {
		return 'package';
	}
}

final class Webgram_Element_Help extends Webgram_Element_Page_Link {

	public function id(): string {
		return 'help';
	}

	public function label(): string {
		return __( 'Help', 'webgram' );
	}

	protected function default_label(): string {
		return __( 'Help', 'webgram' );
	}

	protected function default_icon(): string {
		return 'help-circle';
	}
}
