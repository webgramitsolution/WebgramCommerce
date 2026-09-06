<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** dompdf (Composer, vendor/) with A4 portrait, DejaVu Sans, remote images allowed only for the site's own uploads. */
final class DompdfGenerator implements PdfGeneratorInterface {

	public function available(): bool {
		if ( ! class_exists( '\Dompdf\Dompdf' ) ) {
			$autoload = WEBGRAM_CORE_PATH . 'vendor/autoload.php';
			if ( is_readable( $autoload ) ) {
				require_once $autoload;
			}
		}
		return class_exists( '\Dompdf\Dompdf' );
	}

	public function mime(): string {
		return 'application/pdf';
	}

	public function extension(): string {
		return 'pdf';
	}

	public function render( string $html, array $options = [] ): string {
		if ( ! $this->available() ) {
			return '';
		}
		try {
			$upload  = wp_upload_dir();
			$options = new \Dompdf\Options( [
				'isRemoteEnabled'         => true,
				'isHtml5ParserEnabled'    => true,
				'defaultFont'             => 'DejaVu Sans',
				'chroot'                  => [ WEBGRAM_CORE_PATH, (string) $upload['basedir'] ],
				'allowedProtocols'        => [ 'http://' => [ 'rules' => [ static fn( string $uri ) => self::same_site( $uri ) ] ], 'https://' => [ 'rules' => [ static fn( string $uri ) => self::same_site( $uri ) ] ] ],
				'tempDir'                 => get_temp_dir(),
				'fontDir'                 => (string) $upload['basedir'] . '/webgram-invoices/fonts',
				'fontCache'               => (string) $upload['basedir'] . '/webgram-invoices/fonts',
			] );
			wp_mkdir_p( (string) $upload['basedir'] . '/webgram-invoices/fonts' );
			$dompdf = new \Dompdf\Dompdf( $options );
			$dompdf->setPaper( 'A4', 'portrait' );
			$dompdf->loadHtml( $html, 'UTF-8' );
			$dompdf->render();
			return (string) $dompdf->output();
		} catch ( \Throwable $e ) {
			\webgram_core()->logger()->error( 'Invoice PDF rendering failed', [ 'error' => $e->getMessage() ] );
			return '';
		}
	}

	/** @return array{0: bool, 1: string} dompdf rule callback shape: [allowed, reason] */
	private static function same_site( string $uri ): array {
		$host = wp_parse_url( $uri, PHP_URL_HOST );
		$site = wp_parse_url( home_url(), PHP_URL_HOST );
		return [ $host && $site && strcasecmp( (string) $host, (string) $site ) === 0, 'Only images from this site are embedded.' ];
	}
}
