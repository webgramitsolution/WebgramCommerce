<?php
namespace Webgram\Core\Modules\VoiceSearch\Engines;

use Webgram\Core\Modules\VoiceSearch\EngineInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Server side speech-to-text placeholder: records audio in the browser and posts it to a REST endpoint that a
 * future provider (or a third party through webgram_core/voice_search/engines) transcribes. Not usable in v1.
 */
final class ServerSttEngine implements EngineInterface {

	public function id(): string {
		return 'server_stt';
	}

	public function label(): string {
		return __( 'Server transcription (coming later)', 'webgram-core' );
	}

	public function supported(): bool {
		return (bool) apply_filters( 'webgram_core/voice_search/server_stt_supported', false );
	}

	public function script_handle(): string {
		return 'webgram-core-voice-search';
	}

	public function client_config( array $settings ): array {
		return [ 'engine' => 'server_stt', 'lang' => (string) ( $settings['language'] ?? 'en-IN' ), 'endpoint' => (string) apply_filters( 'webgram_core/voice_search/server_stt_endpoint', '' ) ];
	}
}
