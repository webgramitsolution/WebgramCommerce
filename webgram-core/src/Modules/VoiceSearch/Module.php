<?php
namespace Webgram\Core\Modules\VoiceSearch;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Voice search: a mic button injected into the theme search input (webgram/search/input) and the assistant input
 * (webgram_core/assistant/input). Engines implement EngineInterface; the browser hides the button when the engine
 * is not supported (Firefox has no SpeechRecognition).
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'voice_search';
	}

	public function name(): string {
		return __( 'Voice Search', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Microphone button in the search bar and assistant, using the browser speech recognition.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function phase(): int {
		return 6;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_action( 'webgram/search/input', [ $this, 'button' ] );
		add_action( 'webgram_core/assistant/input', [ $this, 'button' ] );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-voice-search', 'css/voice-search.css' );
		$assets->script( 'webgram-core-voice-search', 'js/voice-search.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		$engines = [];
		foreach ( $this->engines() as $engine ) {
			$engines[ $engine->id() ] = $engine->label();
		}
		return [
			[ 'id' => 'engine', 'label' => __( 'Engine', 'webgram-core' ), 'type' => 'select', 'options' => $engines, 'default' => 'web_speech' ],
			[ 'id' => 'language', 'label' => __( 'Language', 'webgram-core' ), 'type' => 'select', 'options' => self::languages(), 'default' => 'en-IN' ],
			[ 'id' => 'auto_submit', 'label' => __( 'Search automatically when speech ends', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'in_search', 'label' => __( 'Show in the header search', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'in_assistant', 'label' => __( 'Show in the AI assistant', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
		];
	}

	/** @return array<string, string> BCP 47 tag => label */
	public static function languages(): array {
		return (array) apply_filters(
			'webgram_core/voice_search/languages',
			[ 'en-IN' => 'English (India)', 'hi-IN' => 'Hindi', 'en-US' => 'English (US)', 'en-GB' => 'English (UK)', 'bn-IN' => 'Bengali', 'ta-IN' => 'Tamil', 'te-IN' => 'Telugu', 'mr-IN' => 'Marathi', 'gu-IN' => 'Gujarati', 'kn-IN' => 'Kannada', 'ml-IN' => 'Malayalam', 'pa-IN' => 'Punjabi', 'ar-SA' => 'Arabic', 'de-DE' => 'German', 'es-ES' => 'Spanish', 'fr-FR' => 'French' ]
		);
	}

	/** @return EngineInterface[] */
	public function engines(): array {
		$engines = [ new Engines\WebSpeechEngine(), new Engines\ServerSttEngine() ];
		return array_values( array_filter( (array) apply_filters( 'webgram_core/voice_search/engines', $engines ), static fn( $e ) => $e instanceof EngineInterface ) );
	}

	public function engine(): ?EngineInterface {
		$id = (string) $this->settings()->get( 'engine', 'web_speech' );
		foreach ( $this->engines() as $engine ) {
			if ( $engine->id() === $id ) {
				return $engine->supported() ? $engine : null;
			}
		}
		return null;
	}

	public function frontend_data( array $data ): array {
		$engine = $this->engine();
		if ( $engine ) {
			$data['voice'] = $engine->client_config( $this->settings()->all() ) + [
				'autoSubmit' => Helpers::bool( $this->settings()->get( 'auto_submit', true ) ),
				'i18n'       => [ 'start' => __( 'Search by voice', 'webgram-core' ), 'listening' => __( 'Listening', 'webgram-core' ), 'stop' => __( 'Stop listening', 'webgram-core' ), 'denied' => __( 'Microphone access was denied.', 'webgram-core' ), 'error' => __( 'Could not hear you. Please try again.', 'webgram-core' ) ],
			];
		}
		return $data;
	}

	/** Mic button next to an input. $input_id: id of the target input; falls back to the closest form input. */
	public function button( string $input_id = '' ): void {
		$engine  = $this->engine();
		$context = current_action() === 'webgram_core/assistant/input' ? 'in_assistant' : 'in_search';
		if ( ! $engine || ! Helpers::bool( $this->settings()->get( $context, true ) ) ) {
			return;
		}
		\webgram_core()->assets()->enqueue_module( 'voice_search' );
		printf(
			'<button type="button" class="%s" data-wgc-voice="%s" aria-label="%s" title="%s" hidden><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2M12 19v4M8 23h8"/></svg><span class="%s"></span></button>',
			esc_attr( Helpers::css_class( 'voice-btn', 'wg-search__mic' ) ),
			esc_attr( $input_id ),
			esc_attr__( 'Search by voice', 'webgram-core' ),
			esc_attr__( 'Search by voice', 'webgram-core' ),
			esc_attr( Helpers::css_class( 'voice-btn__pulse' ) )
		);
	}
}
