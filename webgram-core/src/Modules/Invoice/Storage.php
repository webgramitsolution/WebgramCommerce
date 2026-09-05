<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** uploads/webgram-invoices/{yyyy}/{mm}/invoice-{number}.pdf, directory protected with index.html and .htaccess. */
final class Storage {

	public const DIR = 'webgram-invoices';

	/** Pure: file name from an invoice number. */
	public static function filename( string $invoice_no, string $extension = 'pdf' ): string {
		$slug = preg_replace( '/[^A-Za-z0-9\-_.]+/', '-', $invoice_no ) ?? $invoice_no;
		return 'invoice-' . trim( $slug, '-' ) . '.' . $extension;
	}

	public function base(): string {
		$upload = wp_upload_dir();
		return trailingslashit( (string) $upload['basedir'] ) . self::DIR;
	}

	public function path( string $invoice_no, string $created_at, string $extension = 'pdf' ): string {
		$ts  = strtotime( $created_at ) ?: time();
		$dir = $this->base() . '/' . gmdate( 'Y', $ts ) . '/' . gmdate( 'm', $ts );
		$this->protect( $dir );
		return $dir . '/' . self::filename( $invoice_no, $extension );
	}

	private function protect( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		foreach ( [ $this->base(), $dir ] as $d ) {
			if ( ! file_exists( $d . '/index.html' ) ) {
				file_put_contents( $d . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}
		if ( ! file_exists( $this->base() . '/.htaccess' ) ) {
			file_put_contents( $this->base() . '/.htaccess', "Order deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}

	public function write( string $path, string $bytes ): bool {
		return false !== file_put_contents( $path, $bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	public function delete( string $path ): void {
		if ( is_file( $path ) && str_starts_with( realpath( $path ) ?: '', realpath( $this->base() ) ?: '/no' ) ) {
			wp_delete_file( $path );
		}
	}
}
