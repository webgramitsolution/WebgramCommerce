<?php
namespace Webgram\Core\Modules\Invoice;

defined( 'ABSPATH' ) || exit;

interface PdfGeneratorInterface {

	public function available(): bool;

	/** Returns PDF bytes (or an empty string on failure). */
	public function render( string $html, array $options = [] ): string;

	/** Output MIME type and extension of what render() produces. */
	public function mime(): string;

	public function extension(): string;
}
