<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\AjaxHandler;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Pincode validation and delivery resolution. Modes: "all" (every valid pincode is deliverable, defaults apply) or
 * "csv" (offline table). Also parses CSV rows for the import screen. The pure methods are harness tested.
 */
final class PincodeChecker {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'webgram/product/summary/pincode', [ $this, 'render_product_checker' ] );
		add_shortcode( 'webgram_pincode_checker', [ $this, 'shortcode' ] );
		( new class( $this ) extends AjaxHandler {
			public function __construct( private PincodeChecker $checker ) {}
			protected function action(): string {
				return 'pincode_check';
			}
			protected function fields(): array {
				return [ 'pincode' => 'text', 'product_id' => 'int' ];
			}
			protected function handle( array $input ): void {
				if ( ! Helpers::rate_limit( 'pincode_check', 60, MINUTE_IN_SECONDS ) ) {
					$this->error( __( 'Too many requests. Please wait a moment.', 'webgram-core' ), 429 );
				}
				$result = $this->checker->check( (string) $input['pincode'], (int) $input['product_id'] );
				if ( empty( $result['valid'] ) ) {
					$this->error( $result['message'] );
				}
				$this->success( $result );
			}
		} )->register();
	}

	/** Product page "Check delivery details" block, prefilled from the saved location. */
	public function render_product_checker(): void {
		global $product;
		if ( ! Helpers::bool( $this->module->settings()->get( 'pincode_show_product', true ) ) ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'woo_enhancements' );
		$location = ( new Location( $this->module ) )->current();
		\webgram_core()->view(
			'woo-enhancements/pincode-checker',
			[
				'label'       => (string) $this->module->settings()->get( 'pincode_title', __( 'Check Delivery Details', 'webgram-core' ) ),
				'field_label' => self::label( self::country() ),
				'value'       => $location['pincode'],
				'product_id'  => $product instanceof \WC_Product ? $product->get_id() : 0,
				'country'     => self::country(),
			]
		);
	}

	public function shortcode(): string {
		ob_start();
		$this->render_product_checker();
		return (string) ob_get_clean();
	}

	/** Country of the store, used for label and validation. */
	public static function country(): string {
		if ( function_exists( 'wc_get_base_location' ) ) {
			return strtoupper( (string) ( wc_get_base_location()['country'] ?? 'IN' ) );
		}
		return 'IN';
	}

	/** Field label per country: Pincode, ZIP code or Postal code. */
	public static function label( string $country ): string {
		return match ( $country ) {
			'IN'      => __( 'Pincode', 'webgram-core' ),
			'US', 'PH' => __( 'ZIP code', 'webgram-core' ),
			default   => __( 'Postal code', 'webgram-core' ),
		};
	}

	/** Normalize and validate a code for the given country. Returns '' when invalid. */
	public static function normalize( string $raw, string $country = 'IN' ): string {
		$code = strtoupper( trim( preg_replace( '/\s+/', ' ', $raw ) ?? '' ) );
		if ( in_array( $country, [ 'IN', 'US', 'AU', 'NZ', 'BE', 'CH', 'AT', 'DK', 'NO', 'DE', 'FR', 'IT', 'ES', 'MY', 'TH', 'AE', 'BR' ], true ) ) {
			$code = str_replace( ' ', '', $code ); // Numeric systems: "400 001" is the same as "400001".
		}
		$pattern = match ( $country ) {
			'IN'    => '/^[1-9][0-9]{5}$/',
			'US'    => '/^[0-9]{5}(-[0-9]{4})?$/',
			'GB'    => '/^[A-Z]{1,2}[0-9][A-Z0-9]? ?[0-9][A-Z]{2}$/',
			'CA'    => '/^[A-Z][0-9][A-Z] ?[0-9][A-Z][0-9]$/',
			'AU', 'NZ', 'BE', 'CH', 'AT', 'DK', 'NO' => '/^[0-9]{4}$/',
			'DE', 'FR', 'IT', 'ES', 'MY', 'TH', 'AE' => '/^[0-9]{5}$/',
			'BR'    => '/^[0-9]{5}-?[0-9]{3}$/',
			'NL'    => '/^[0-9]{4} ?[A-Z]{2}$/',
			default => '/^[A-Z0-9 \-]{3,10}$/',
		};
		$pattern = (string) apply_filters( 'webgram_core/pincode/pattern', $pattern, $country );
		return preg_match( $pattern, $code ) ? $code : '';
	}

	/**
	 * @return array{valid: bool, pincode: string, city: string, state: string, deliverable: bool, cod: bool, eta_days: int|null, eta_date: string, message: string}
	 */
	public function check( string $raw, int $product_id = 0 ): array {
		$country = self::country();
		$code    = self::normalize( $raw, $country );
		$out     = [ 'valid' => false, 'pincode' => $code, 'city' => '', 'state' => '', 'deliverable' => false, 'cod' => false, 'eta_days' => null, 'eta_date' => '', 'message' => '' ];
		if ( '' === $code ) {
			/* translators: %s: field label (Pincode, ZIP code) */
			$out['message'] = sprintf( __( 'Please enter a valid %s.', 'webgram-core' ), self::label( $country ) );
			return $out;
		}
		$s      = $this->module->settings();
		$mode   = (string) $s->get( 'pincode_mode', 'all' );
		$eta    = (int) $s->get( 'pincode_default_eta', 4 );
		$cod    = Helpers::bool( $s->get( 'pincode_default_cod', true ) );
		$row    = 'csv' === $mode ? ( new PincodeRepository() )->find( $code ) : null;
		$out['valid'] = true;

		if ( $row ) {
			$out['city']        = $row['city'];
			$out['state']       = $row['state'];
			$out['deliverable'] = $row['deliverable'];
			$out['cod']         = $row['deliverable'] && $row['cod'];
			$out['eta_days']    = $row['eta_days'] ?? $eta;
		} elseif ( 'csv' === $mode && ! Helpers::bool( $s->get( 'pincode_unknown_deliverable', false ) ) ) {
			$out['deliverable'] = false;
		} else {
			$out['deliverable'] = true;
			$out['cod']         = $cod;
			$out['eta_days']    = $eta;
		}

		$out = (array) apply_filters( 'webgram_core/pincode/result', $out, $code, $product_id );

		if ( $out['deliverable'] ) {
			$days            = (int) ( $out['eta_days'] ?? $eta );
			$out['eta_date'] = wp_date( (string) get_option( 'date_format' ), time() + $days * DAY_IN_SECONDS );
			$out['message']  = $days > 0
				/* translators: %s: date */
				? sprintf( __( 'Delivery by %s', 'webgram-core' ), wp_date( 'D, j M', time() + $days * DAY_IN_SECONDS ) )
				: __( 'Available for delivery', 'webgram-core' );
			if ( $out['cod'] ) {
				$out['message'] .= ' • ' . __( 'COD available', 'webgram-core' );
			}
		} else {
			/* translators: %s: field label */
			$out['message'] = sprintf( __( 'Not deliverable to this %s', 'webgram-core' ), strtolower( self::label( $country ) ) );
		}
		return $out;
	}

	/**
	 * Parse CSV text (header row: pincode, city, state, deliverable, cod, eta_days; header optional) into rows.
	 *
	 * @return array{rows: array, skipped: int}
	 */
	public static function parse_csv( string $csv, string $country = 'IN' ): array {
		$rows    = [];
		$skipped = 0;
		$lines   = preg_split( '/\r\n|\r|\n/', $csv ) ?: [];
		$map     = [ 'pincode' => 0, 'city' => 1, 'state' => 2, 'deliverable' => 3, 'cod' => 4, 'eta_days' => 5 ];
		foreach ( $lines as $i => $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$cols = array_map( 'trim', str_getcsv( $line ) );
			if ( 0 === $i && ! is_numeric( $cols[0] ) && preg_match( '/pin|zip|postal/i', (string) $cols[0] ) ) {
				foreach ( $cols as $idx => $name ) {
					$name = strtolower( preg_replace( '/[^a-z_]/', '', strtolower( $name ) ) );
					$key  = match ( true ) {
						str_contains( $name, 'pin' ), str_contains( $name, 'zip' ), str_contains( $name, 'postal' ) => 'pincode',
						str_contains( $name, 'city' ), str_contains( $name, 'district' ) => 'city',
						str_contains( $name, 'state' ), str_contains( $name, 'region' ) => 'state',
						str_contains( $name, 'deliver' ) => 'deliverable',
						str_contains( $name, 'cod' ) => 'cod',
						str_contains( $name, 'eta' ), str_contains( $name, 'days' ) => 'eta_days',
						default => null,
					};
					if ( $key ) {
						$map[ $key ] = $idx;
					}
				}
				continue;
			}
			$code = self::normalize( (string) ( $cols[ $map['pincode'] ] ?? '' ), $country );
			if ( '' === $code ) {
				$skipped++;
				continue;
			}
			$del = $cols[ $map['deliverable'] ] ?? '1';
			$cod = $cols[ $map['cod'] ] ?? '1';
			$eta = $cols[ $map['eta_days'] ] ?? '';
			$rows[ $code ] = [
				'pincode'     => $code,
				'city'        => mb_substr( sanitize_text_field( (string) ( $cols[ $map['city'] ] ?? '' ) ), 0, 80 ),
				'state'       => mb_substr( sanitize_text_field( (string) ( $cols[ $map['state'] ] ?? '' ) ), 0, 80 ),
				'deliverable' => '' === (string) $del || Helpers::bool( $del ),
				'cod'         => '' === (string) $cod || Helpers::bool( $cod ),
				'eta_days'    => is_numeric( $eta ) ? max( 0, min( 60, (int) $eta ) ) : null,
			];
		}
		return [ 'rows' => array_values( $rows ), 'skipped' => $skipped ];
	}
}
