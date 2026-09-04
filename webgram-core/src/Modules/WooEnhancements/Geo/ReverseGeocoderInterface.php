<?php
namespace Webgram\Core\Modules\WooEnhancements\Geo;

defined( 'ABSPATH' ) || exit;

interface ReverseGeocoderInterface {

	public function id(): string;

	public function label(): string;

	/** Attribution HTML shown next to the "Use my current location" button (may be empty). */
	public function attribution(): string;

	/** @return array{pincode: string, city: string, state: string}|null */
	public function resolve( float $lat, float $lng ): ?array;
}
