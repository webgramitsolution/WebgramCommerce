<?php
namespace Webgram\Core\Modules\AiAssistant;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * AI shopping assistant: pluggable providers (rule based without a key, OpenAI, Gemini, Anthropic), store tools,
 * conversations in custom tables with retention and privacy tools, nonce protected REST, a small launcher that
 * loads the chat bundle on first click, and an inline Elementor widget and block.
 */
final class Module extends BaseModule {

	private ?Assistant $assistant = null;
	private ?Tools $tools         = null;
	private bool $needed          = false;

	public function id(): string {
		return 'ai_assistant';
	}

	public function name(): string {
		return __( 'AI Shopping Assistant', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Chat assistant that finds products, shares offers and tracks orders. Works without an API key (rule based) or with OpenAI, Gemini or Anthropic.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return false;
	}

	public function phase(): int {
		return 6;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function activate(): void {
		( new ConversationRepository() )->install();
		( new MessageRepository() )->install();
	}

	public function uninstall(): void {
		( new MessageRepository() )->drop();
		( new ConversationRepository() )->drop();
	}

	public function boot(): void {
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_filter( 'webgram_core/rest_controllers', fn( array $c ) => array_merge( $c, [ new Rest\AssistantController( $this ) ] ) );
		add_filter( 'webgram_core/frontend_data', [ $this, 'frontend_data' ] );
		add_filter( 'webgram_core/elementor/widgets', [ $this, 'widget_definition' ] );
		add_shortcode( 'webgram_assistant', [ $this, 'shortcode' ] );
		add_action( 'wp_footer', [ $this, 'launcher' ], 50 );
		( new Privacy( $this, new ConversationRepository(), new MessageRepository() ) )->register();
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-ai-assistant', 'css/assistant.css' );
		$assets->script( 'webgram-core-ai-assistant', 'js/assistant-launcher.js', [ 'webgram-core-base' ] );
		$assets->script( 'webgram-core-ai-assistant-chat', 'js/assistant.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'provider', 'label' => __( 'Provider', 'webgram-core' ), 'type' => 'select', 'options' => [ 'rule_based' => __( 'Rule based (no API key, keyword search)', 'webgram-core' ), 'anthropic' => 'Anthropic (Claude)', 'openai' => 'OpenAI', 'gemini' => 'Google Gemini' ], 'default' => 'rule_based' ],
			[ 'id' => 'api_key', 'label' => __( 'API key', 'webgram-core' ), 'type' => 'secret', 'default' => '', 'description' => __( 'Stored encrypted. Not needed for the rule based provider.', 'webgram-core' ) ],
			[ 'id' => 'model', 'label' => __( 'Model name', 'webgram-core' ), 'type' => 'text', 'default' => '', 'description' => __( 'Leave empty for the provider default (Anthropic: claude-opus-5, OpenAI: gpt-4o-mini, Gemini: gemini-2.0-flash).', 'webgram-core' ) ],
			[ 'id' => 'system_prompt', 'label' => __( 'Store notes for the assistant', 'webgram-core' ), 'type' => 'textarea', 'default' => '', 'description' => __( 'Extra instructions or facts (tone, brand voice, policies). Appended to the built in system prompt.', 'webgram-core' ) ],
			[ 'id' => 'name', 'label' => __( 'Assistant name', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Shopping Assistant', 'webgram-core' ) ],
			[ 'id' => 'avatar', 'label' => __( 'Avatar', 'webgram-core' ), 'type' => 'image', 'default' => 0 ],
			[ 'id' => 'greeting', 'label' => __( 'Greeting', 'webgram-core' ), 'type' => 'textarea', 'default' => __( 'Hi! Looking for something? I can find products, share offers and track your order.', 'webgram-core' ) ],
			[ 'id' => 'suggestions', 'label' => __( 'Suggested questions (one per line)', 'webgram-core' ), 'type' => 'textarea', 'default' => __( "Show me best sellers\nAny coupons today?\nWall decor under 2000\nWhat is your return policy?", 'webgram-core' ) ],
			[ 'id' => 'visibility', 'label' => __( 'Show the launcher on', 'webgram-core' ), 'type' => 'select', 'options' => [ 'all' => __( 'All pages', 'webgram-core' ), 'shop' => __( 'Shop, category and product pages', 'webgram-core' ), 'selected' => __( 'Selected page IDs', 'webgram-core' ), 'none' => __( 'Nowhere (inline widget only)', 'webgram-core' ) ], 'default' => 'all' ],
			[ 'id' => 'pages', 'label' => __( 'Page IDs (comma separated)', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'position', 'label' => __( 'Launcher position', 'webgram-core' ), 'type' => 'select', 'options' => [ 'right' => __( 'Bottom right', 'webgram-core' ), 'left' => __( 'Bottom left', 'webgram-core' ) ], 'default' => 'right' ],
			[ 'id' => 'color', 'label' => __( 'Accent color', 'webgram-core' ), 'type' => 'color', 'default' => '', 'description' => __( 'Empty uses the theme primary color.', 'webgram-core' ) ],
			[ 'id' => 'logged_in_only', 'label' => __( 'Logged in shoppers only', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'daily_budget', 'label' => __( 'Daily message budget (all visitors)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 100000, 'default' => 500, 'description' => __( '0 means unlimited.', 'webgram-core' ) ],
			[ 'id' => 'rate_limit', 'label' => __( 'Messages per minute per visitor', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 60, 'default' => 10 ],
			[ 'id' => 'retention_days', 'label' => __( 'Keep conversations for (days)', 'webgram-core' ), 'type' => 'number', 'min' => 1, 'max' => 3650, 'default' => 90 ],
			[ 'id' => 'consent_text', 'label' => __( 'Consent text shown before the first message', 'webgram-core' ), 'type' => 'textarea', 'default' => __( 'Messages are processed by an AI assistant and stored to improve your experience. Do not share sensitive information.', 'webgram-core' ) ],
			[ 'id' => 'info_heading', 'label' => __( 'Store facts for answers', 'webgram-core' ), 'type' => 'heading' ],
			[ 'id' => 'info_shipping', 'label' => __( 'Shipping', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'info_returns', 'label' => __( 'Returns', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'info_payments', 'label' => __( 'Payments', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'info_contact', 'label' => __( 'Contact', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
			[ 'id' => 'info_hours', 'label' => __( 'Support hours', 'webgram-core' ), 'type' => 'text', 'default' => '' ],
			[ 'id' => 'faqs', 'label' => __( 'FAQ for the rule based provider (blank line separated: question, then answer lines)', 'webgram-core' ), 'type' => 'textarea', 'default' => '' ],
		];
	}

	public function tools(): Tools {
		return $this->tools ??= new Tools( $this );
	}

	public function assistant(): Assistant {
		return $this->assistant ??= new Assistant( $this, new ConversationRepository(), new MessageRepository() );
	}

	private function api_key(): string {
		$stored = (string) $this->settings()->get( 'api_key', '' );
		return '' === $stored ? '' : \webgram_core()->crypto()->decrypt( $stored );
	}

	public function provider(): ProviderInterface {
		$id       = (string) $this->settings()->get( 'provider', 'rule_based' );
		$provider = match ( $id ) {
			'anthropic' => new Providers\AnthropicProvider( $this->api_key() ),
			'openai'    => new Providers\OpenAiProvider( $this->api_key() ),
			'gemini'    => new Providers\GeminiProvider( $this->api_key() ),
			default     => new Providers\RuleBasedProvider( $this->faqs(), get_bloginfo( 'name' ) ),
		};
		$provider = apply_filters( 'webgram_core/ai/provider', $provider, $id, $this );
		return $provider instanceof ProviderInterface ? $provider : new Providers\RuleBasedProvider( $this->faqs(), get_bloginfo( 'name' ) );
	}

	/** @return array<int, array{q: string, a: string}> */
	public function faqs(): array {
		$faqs = class_exists( '\Webgram\Core\Modules\SiteTools\Module' ) ? \Webgram\Core\Modules\SiteTools\Module::parse_faqs( (string) $this->settings()->get( 'faqs', '' ) ) : [];
		return array_values( (array) apply_filters( 'webgram_core/ai/faqs', array_merge( (array) apply_filters( 'webgram/help/faqs', [] ), $faqs ) ) );
	}

	public function greeting(): string {
		return (string) $this->settings()->get( 'greeting', __( 'Hi! Looking for something?', 'webgram-core' ) );
	}

	/** @return string[] */
	public function suggested_questions(): array {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $this->settings()->get( 'suggestions', '' ) ) ?: [] ) ) );
	}

	public function should_show(): bool {
		$mode = (string) $this->settings()->get( 'visibility', 'all' );
		if ( 'none' === $mode || is_admin() ) {
			return false;
		}
		if ( 'shop' === $mode ) {
			return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() );
		}
		if ( 'selected' === $mode ) {
			$ids = array_filter( array_map( 'intval', explode( ',', (string) $this->settings()->get( 'pages', '' ) ) ) );
			return is_singular() && in_array( get_queried_object_id(), $ids, true );
		}
		return (bool) apply_filters( 'webgram_core/ai/show_launcher', true );
	}

	public function frontend_data( array $data ): array {
		$avatar = (int) $this->settings()->get( 'avatar', 0 );
		$data['assistant'] = [
			'name'        => (string) $this->settings()->get( 'name', __( 'Shopping Assistant', 'webgram-core' ) ),
			'avatar'      => $avatar ? (string) wp_get_attachment_image_url( $avatar, 'thumbnail' ) : '',
			'greeting'    => $this->greeting(),
			'suggestions' => $this->suggested_questions(),
			'consent'     => (string) $this->settings()->get( 'consent_text', '' ),
			'color'       => (string) $this->settings()->get( 'color', '' ),
			'position'    => 'left' === $this->settings()->get( 'position', 'right' ) ? 'left' : 'right',
			'bundle'      => WEBGRAM_CORE_URL . 'assets/js/assistant.js?ver=' . rawurlencode( WEBGRAM_CORE_VERSION ),
			'loginUrl'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url(),
			'i18n'        => [
				'placeholder' => __( 'Ask about products, offers or your order', 'webgram-core' ),
				'send'        => __( 'Send', 'webgram-core' ),
				'typing'      => __( 'Typing', 'webgram-core' ),
				'retry'       => __( 'Retry', 'webgram-core' ),
				'error'       => __( 'Something went wrong. Please try again.', 'webgram-core' ),
				'addToCart'   => __( 'Add to cart', 'webgram-core' ),
				'view'        => __( 'View', 'webgram-core' ),
				'online'      => __( 'Online', 'webgram-core' ),
				'close'       => __( 'Close chat', 'webgram-core' ),
				'open'        => __( 'Chat with us', 'webgram-core' ),
				'agree'       => __( 'Start chatting', 'webgram-core' ),
				'you'         => __( 'You', 'webgram-core' ),
			],
		];
		return $data;
	}

	/** Floating launcher plus the hidden window shell (chat bundle loads on first click). */
	public function launcher(): void {
		if ( $this->needed || ! $this->should_show() ) {
			return;
		}
		$this->needed = true;
		\webgram_core()->assets()->enqueue_module( 'ai_assistant' );
		$this->view( 'launcher', [ 'position' => (string) $this->settings()->get( 'position', 'right' ), 'color' => (string) $this->settings()->get( 'color', '' ), 'name' => (string) $this->settings()->get( 'name', '' ) ] );
		$this->view( 'window', [ 'inline' => false ] + $this->window_args() );
	}

	private function window_args(): array {
		$avatar = (int) $this->settings()->get( 'avatar', 0 );
		return [ 'name' => (string) $this->settings()->get( 'name', __( 'Shopping Assistant', 'webgram-core' ) ), 'avatar' => $avatar ? (string) wp_get_attachment_image_url( $avatar, 'thumbnail' ) : '', 'color' => (string) $this->settings()->get( 'color', '' ), 'consent' => (string) $this->settings()->get( 'consent_text', '' ) ];
	}

	/** Inline chat (Elementor widget, block, shortcode). */
	public function inline_html(): string {
		\webgram_core()->assets()->enqueue_module( 'ai_assistant' );
		wp_enqueue_script( 'webgram-core-ai-assistant-chat' );
		return $this->view( 'window', [ 'inline' => true ] + $this->window_args(), false );
	}

	public function shortcode(): string {
		return $this->inline_html();
	}

	public function widget_definition( array $widgets ): array {
		$widgets['ai_assistant'] = [ 'title' => __( 'Webgram AI Assistant', 'webgram-core' ), 'icon' => 'eicon-chat', 'controls' => [], 'render' => fn() => $this->inline_html() ];
		return $widgets;
	}
}
