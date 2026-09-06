<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** Pure invoice number formatting: {prefix}{number}{suffix}, {yyyy}, {yy}, {mm}, {fy}, padding, start, yearly reset. */
final class Numbering {

	public const DEFAULT_FORMAT = '{prefix}{fy}-{number}';

	/** Indian financial year label for a date, e.g. 2026-27 when the FY starts in April. */
	public static function financial_year( \DateTimeInterface $date, int $start_month = 4 ): string {
		$year  = (int) $date->format( 'Y' );
		$month = (int) $date->format( 'n' );
		$start = $month >= $start_month && $start_month > 1 ? $year : ( $start_month > 1 ? $year - 1 : $year );
		if ( 1 === $start_month ) {
			return (string) $year;
		}
		return $start . '-' . substr( (string) ( $start + 1 ), -2 );
	}

	/** First day of the numbering period that contains the date (calendar year or financial year), UTC string. */
	public static function period_start( \DateTimeInterface $date, bool $financial, int $start_month = 4 ): string {
		$year  = (int) $date->format( 'Y' );
		$month = (int) $date->format( 'n' );
		if ( $financial && $start_month > 1 ) {
			$start_year = $month >= $start_month ? $year : $year - 1;
			return sprintf( '%04d-%02d-01 00:00:00', $start_year, $start_month );
		}
		return sprintf( '%04d-01-01 00:00:00', $year );
	}

	public static function format( string $format, int $number, int $padding, \DateTimeInterface $date, string $prefix = '', string $suffix = '', int $fy_start_month = 4 ): string {
		$format = '' === trim( $format ) ? self::DEFAULT_FORMAT : $format;
		$map    = [
			'{prefix}' => $prefix,
			'{suffix}' => $suffix,
			'{number}' => str_pad( (string) max( 0, $number ), max( 0, $padding ), '0', STR_PAD_LEFT ),
			'{yyyy}'   => $date->format( 'Y' ),
			'{yy}'     => $date->format( 'y' ),
			'{mm}'     => $date->format( 'm' ),
			'{fy}'     => self::financial_year( $date, $fy_start_month ),
		];
		$out = strtr( $format, $map );
		return trim( preg_replace( '/[^A-Za-z0-9\-\/_.]+/', '-', $out ) ?? $out, '-' );
	}
}
