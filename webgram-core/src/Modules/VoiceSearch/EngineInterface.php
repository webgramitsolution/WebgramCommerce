<?php
namespace Webgram\Core\Modules\VoiceSearch;

defined( 'ABSPATH' ) || exit;

/** A speech-to-text engine. The mic button renders only when supported() is true; JS re-checks in the browser. */
interface EngineInterface {

	public function id(): string;

	public function label(): string;

	/** Server side knowledge: is this engine configured and usable at all? */
	public function supported(): bool;

	/** Registered script handle that implements the engine in the browser (empty when server side only). */
	public function script_handle(): string;

	/** Data passed to the browser (language, endpoints). Never secrets. */
	public function client_config( array $settings ): array;
}
