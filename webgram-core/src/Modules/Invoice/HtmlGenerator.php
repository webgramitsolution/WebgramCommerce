<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

/** Fallback when dompdf is missing: the same template served as a printable HTML page. */
final class HtmlGenerator implements PdfGeneratorInterface {

	public function available(): bool {
		return true;
	}

	public function mime(): string {
		return 'text/html';
	}

	public function extension(): string {
		return 'html';
	}

	public function render( string $html, array $options = [] ): string {
		return str_replace( '</head>', '<style>@media screen{body{background:#e5e7eb;padding:24px}.wgc-invoice{box-shadow:0 10px 30px rgba(0,0,0,.15)}}</style><script>window.addEventListener("load",function(){if(location.search.indexOf("print=1")>-1){window.print();}});</script></head>', $html );
	}
}
