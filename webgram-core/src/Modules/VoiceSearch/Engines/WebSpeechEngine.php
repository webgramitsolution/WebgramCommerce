<?php
namespace Webgram\Core\Modules\VoiceSearch\Engines;

use Webgram\Core\Modules\VoiceSearch\EngineInterface;

defined( 'ABSPATH' ) || exit;

/** Browser SpeechRecognition (Chrome, Edge, Safari). Feature detected in JS; hidden in Firefox. */
final class WebSpeechEngine implements EngineInterface {

	public function id(): string {
		return 'web_speech';
	}

	public function label(): string {
		return __( 'Browser speech recognition (Web Speech API)', 'webgram-core' );
	}

	public function supported(): bool {
		return true;
	}

	public function script_handle(): string {
		return 'webgram-core-voice-search';
	}

	public function client_config( array $settings ): array {
		return [ 'engine' => 'web_speech', 'lang' => (string) ( $settings['language'] ?? 'en-IN' ), 'interim' => true ];
	}
}
