<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Delivery location picker: header "Deliver to" pill, modal (pincode input plus optional current location),
 * cookie wg_location (pincode, city, state; 30 days) and user meta for logged-in customers.
 */
final class Location {

	public const COOKIE = 'wg_location';
	public const META   = '_wg_location';

	private bool $modal_printed = false;

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/header/deliver_to', [ $this, 'render_pill' ] );
		add_filter( 'webgram/header/elements', [ $this, 'header_element' ] );
		add_action( 'wp_footer', [ $this, 'render_modal' ], 30 );
		add_filter( 'webgram/frontend_data', [ $this, 'frontend_data' ] );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
		add_shortcode( 'webgram_location', [ $this, 'shortcode' ] );

		$location = $this;
		( new class( $location ) extends AjaxHandler {
			public function __construct( private Location $location ) {}
			protected function action(): string {
				return 'location_resolve';
			}
			protected function fields(): array {
				return [ 'pincode' => 'text' ];
			}
			protected function handle( array $input ): void {
				if ( ! Helpers::rate_limit( 'location_resolve', 30, MINUTE_IN_SECONDS ) ) {
					$this->error( __( 'Too many requests. Please wait a moment.', 'webgram-core' ), 429 );
				}
				$result = $this->location->resolve_pincode( (string) $input['pincode'] );
				if ( empty( $result['valid'] ) ) {
					$this->error( $result['message'] );
				}
				$this->success( $result );
			}
		} )->register();

		( new class( $location ) extends AjaxHandler {
			public function __construct( private Location $location ) {}
			protected function action(): string {
				return 'location_geocode';
			}
			protected function fields(): array {
				return [ 'lat' => 'float', 'lng' => 'float' ];
			}
			protected function handle( array $input ): void {
				if ( ! Helpers::rate_limit( 'location_geocode', 10, MINUTE_IN_SECONDS ) ) {
					$this->error( __( 'Too many requests. Please wait a moment.', 'webgram-core' ), 429 );
				}
				$lat = (float) $input['lat'];
				$lng = (float) $input['lng'];
				if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
					$this->error( __( 'Invalid coordinates.', 'webgram-core' ) );
				}
				$geo = $this->location->geocoder()->resolve( $lat, $lng );
				if ( ! $geo || empty( $geo['pincode'] ) ) {
					$this->error( __( 'We could not detect your area. Please enter your pincode.', 'webgram-core' ) );
				}
				$result = $this->location->resolve_pincode( (string) $geo['pincode'], $geo );
				if ( empty( $result['valid'] ) ) {
					$this->error( $result['message'] );
				}
				$this->success( $result );
			}
		} )->register();
	}

	/** Active reverse geocoder from the API integrations setting, extensible via webgram_core/geo/adapters. */
	public function geocoder(): Geo\ReverseGeocoderInterface {
		$site = \webgram_core()->settings( 'site_tools' );
		$id   = (string) $site->get( 'geo_adapter', 'none' );
		$list = (array) apply_filters(
			'webgram_core/geo/adapters',
			[
				'none'      => new Geo\NullGeocoder(),
				'nominatim' => new Geo\NominatimGeocoder( (string) $site->get( 'nominatim_email', '' ) ),
			]
		);
		$adapter = $list[ $id ] ?? $list['none'];
		return $adapter instanceof Geo\ReverseGeocoderInterface ? $adapter : new Geo\NullGeocoder();
	}

	public function has_geocoder(): bool {
		return 'none' !== $this->geocoder()->id();
	}

	/** Saved location from cookie or user meta. */
	public function current(): array {
		$empty = [ 'pincode' => '', 'city' => '', 'state' => '' ];
		if ( isset( $_COOKIE[ self::COOKIE ] ) ) {
			$data = json_decode( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ), true );
			if ( is_array( $data ) && ! empty( $data['pincode'] ) ) {
				return self::clean( $data );
			}
		}
		if ( is_user_logged_in() ) {
			$meta = get_user_meta( get_current_user_id(), self::META, true );
			if ( is_array( $meta ) && ! empty( $meta['pincode'] ) ) {
				return self::clean( $meta );
			}
		}
		return $empty;
	}

	public static function clean( array $data ): array {
		return [
			'pincode' => sanitize_text_field( (string) ( $data['pincode'] ?? '' ) ),
			'city'    => sanitize_text_field( (string) ( $data['city'] ?? '' ) ),
			'state'   => sanitize_text_field( (string) ( $data['state'] ?? '' ) ),
		];
	}

	/** Validate, resolve city/state, persist and return the location for the client. */
	public function resolve_pincode( string $raw, array $hint = [] ): array {
		$checker = new PincodeChecker( $this->module );
		$result  = $checker->check( $raw );
		if ( empty( $result['valid'] ) ) {
			return $result;
		}
		$location = self::clean(
			[
				'pincode' => $result['pincode'],
				'city'    => $result['city'] ?: (string) ( $hint['city'] ?? '' ),
				'state'   => $result['state'] ?: (string) ( $hint['state'] ?? '' ),
			]
		);
		$this->save( $location );
		$result['location'] = $location;
		$result['label']    = self::display_label( $location );
		return $result;
	}

	public function save( array $location ): void {
		$location = self::clean( $location );
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, (string) wp_json_encode( $location ), [ 'expires' => time() + 30 * DAY_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'domain' => COOKIE_DOMAIN ?: '', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ] );
		}
		$_COOKIE[ self::COOKIE ] = (string) wp_json_encode( $location );
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), self::META, $location );
		}
		do_action( 'webgram_core/location/saved', $location );
	}

	/** "Mumbai 400001" or just the pincode. */
	public static function display_label( array $location ): string {
		$parts = array_filter( [ $location['city'] ?? '', $location['pincode'] ?? '' ] );
		return implode( ' ', $parts );
	}

	public function frontend_data( array $data ): array {
		$data['location'] = [
			'current'      => $this->current(),
			'hasGeocoder'  => $this->has_geocoder(),
			'label'        => PincodeChecker::label( PincodeChecker::country() ),
			'placeholder'  => (string) $this->module->settings()->get( 'location_placeholder', __( 'Select location', 'webgram-core' ) ),
		];
		return $data;
	}

	/** Header builder element definition (array form, no theme class dependency). */
	public function header_element( array $elements ): array {
		$elements[] = [
			'id'        => 'deliver_to',
			'label'     => __( 'Deliver to (location)', 'webgram-core' ),
			'icon'      => 'map-pin',
			'group'     => 'actions',
			'available' => static fn() => true,
			'fields'    => [
				'label' => [ 'label' => __( 'Small label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Deliver to', 'webgram-core' ) ],
				'style' => [ 'label' => __( 'Style', 'webgram-core' ), 'type' => 'radio', 'choices' => [ 'pill' => __( 'Pill', 'webgram-core' ), 'plain' => __( 'Plain', 'webgram-core' ) ], 'default' => 'pill' ],
			],
			'render'    => function ( array $settings ) {
				$this->render_pill( $settings );
			},
		];
		return $elements;
	}

	public function render_pill( array $settings = [] ): void {
		$s = $this->module->settings();
		if ( ! $settings && ! Helpers::bool( $s->get( 'location_show_header', true ) ) ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		$current = $this->current();
		\webgram_core()->view(
			'woo-enhancements/location-pill',
			[
				'label'       => (string) ( $settings['label'] ?? $s->get( 'location_label', __( 'Deliver to', 'webgram-core' ) ) ),
				'style'       => (string) ( $settings['style'] ?? 'pill' ),
				'value'       => self::display_label( $current ) ?: (string) $s->get( 'location_placeholder', __( 'Select location', 'webgram-core' ) ),
				'has_value'   => '' !== $current['pincode'],
			]
		);
		$this->modal_needed = true;
	}

	private bool $modal_needed = false;

	public function render_modal(): void {
		if ( ! $this->modal_needed || $this->modal_printed || is_admin() ) {
			return;
		}
		$this->modal_printed = true;
		$country             = PincodeChecker::country();
		\webgram_core()->view(
			'woo-enhancements/location-modal',
			[
				'field_label'  => PincodeChecker::label( $country ),
				'country'      => $country,
				'current'      => $this->current(),
				'has_geocoder' => $this->has_geocoder(),
				'attribution'  => $this->geocoder()->attribution(),
			]
		);
	}

	public function shortcode(): string {
		ob_start();
		$this->render_pill( [ 'label' => (string) $this->module->settings()->get( 'location_label', __( 'Deliver to', 'webgram-core' ) ), 'style' => 'pill' ] );
		return (string) ob_get_clean();
	}
}
