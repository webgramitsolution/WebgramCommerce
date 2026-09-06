<?php
namespace Webgram\Core\Modules\WooEnhancements\Geo;

defined( 'ABSPATH' ) || exit;

/**
 * OpenStreetMap Nominatim reverse geocoding (free, attribution required, max 1 request per second per their
 * usage policy). Responses are validated and cached for 30 days per rounded coordinate.
 */
final class NominatimGeocoder implements ReverseGeocoderInterface {

	public function __construct( private string $contact_email = '' ) {}

	public function id(): string {
		return 'nominatim';
	}

	public function label(): string {
		return 'OpenStreetMap Nominatim';
	}

	public function attribution(): string {
		return '<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">&copy; OpenStreetMap</a>';
	}

	public function resolve( float $lat, float $lng ): ?array {
		$lat = round( $lat, 3 );
		$lng = round( $lng, 3 );
		$key = 'geo_' . $lat . '_' . $lng;
		$hit = \webgram_core()->cache()->get( $key, 'geo' );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$url = add_query_arg(
			[ 'format' => 'jsonv2', 'lat' => $lat, 'lon' => $lng, 'zoom' => 18, 'addressdetails' => 1, 'email' => $this->contact_email ],
			'https://nominatim.openstreetmap.org/reverse'
		);
		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 10,
				'user-agent' => 'WebgramCore/' . WEBGRAM_CORE_VERSION . ' (' . home_url( '/' ) . ( $this->contact_email ? '; ' . $this->contact_email : '' ) . ')',
				'headers'    => [ 'Accept-Language' => 'en' ],
			]
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			\webgram_core()->logger()->warning( 'Nominatim reverse geocode failed', [ 'error' => is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response ) ] );
			return null;
		}
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$result = self::parse( is_array( $data ) ? $data : [] );
		if ( $result ) {
			\webgram_core()->cache()->set( $key, $result, 30 * DAY_IN_SECONDS, 'geo' );
		}
		return $result;
	}

	/** Pure parser for a Nominatim jsonv2 response. */
	public static function parse( array $data ): ?array {
		$address = (array) ( $data['address'] ?? [] );
		$pincode = sanitize_text_field( (string) ( $address['postcode'] ?? '' ) );
		if ( '' === $pincode ) {
			return null;
		}
		$city = '';
		foreach ( [ 'city', 'town', 'village', 'suburb', 'county', 'state_district' ] as $k ) {
			if ( ! empty( $address[ $k ] ) ) {
				$city = sanitize_text_field( (string) $address[ $k ] );
				break;
			}
		}
		return [
			'pincode' => $pincode,
			'city'    => $city,
			'state'   => sanitize_text_field( (string) ( $address['state'] ?? '' ) ),
		];
	}
}
