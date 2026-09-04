<?php
namespace Webgram\Core\Modules\WooEnhancements\Geo;

defined( 'ABSPATH' ) || exit;

/** Default adapter: no service configured, the current location button stays hidden. */
final class NullGeocoder implements ReverseGeocoderInterface {

	public function id(): string {
		return 'none';
	}

	public function label(): string {
		return __( 'None', 'webgram-core' );
	}

	public function attribution(): string {
		return '';
	}

	public function resolve( float $lat, float $lng ): ?array {
		return null;
	}
}
