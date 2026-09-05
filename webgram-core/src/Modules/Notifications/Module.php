<?php
namespace Webgram\Core\Modules\Notifications;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Modules\Notifications\Channels\ChannelInterface;
use Webgram\Core\Modules\Notifications\Channels\EmailChannel;
use Webgram\Core\Modules\Notifications\Channels\SmsChannel;
use Webgram\Core\Modules\Notifications\Channels\WhatsAppBspChannel;
use Webgram\Core\Modules\Notifications\Channels\WhatsAppCloudChannel;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Order notifications on email and WhatsApp (Meta Cloud API with the owner's own credentials). Events fire into a
 * background queue with an idempotent log; consent is required for WhatsApp; a webhook records delivery states.
 */
final class Module extends BaseModule {

	public const STATUS_OPTION = 'webgram_core_whatsapp_status';

	private ?Log $log            = null;
	private ?Queue $queue        = null;
	private ?Templates $tpl      = null;
	private ?OptIn $optin        = null;
	private ?array $channels     = null;
	private ?WhatsAppCloudChannel $whatsapp = null;

	public function id(): string {
		return 'notifications';
	}

	public function name(): string {
		return __( 'Order Notifications', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Email and WhatsApp order updates (placed, paid, shipped, out for delivery, delivered, cancelled, refunded) with consent, queue, retries and a delivery log.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function phase(): int {
		return 7;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function activate(): void {
		$this->log()->install();
	}

	public function uninstall(): void {
		$this->log()->drop();
	}

	public function log(): Log {
		return $this->log ??= new Log();
	}

	public function templates(): Templates {
		return $this->tpl ??= new Templates( $this );
	}

	public function optin(): OptIn {
		return $this->optin ??= new OptIn( $this );
	}

	public function queue(): Queue {
		return $this->queue ??= new Queue( $this, $this->log(), $this->templates() );
	}

	public function whatsapp(): WhatsAppCloudChannel {
		return $this->whatsapp ??= new WhatsAppCloudChannel( $this );
	}

	/** @return ChannelInterface[] enabled channels (email, whatsapp, plus third party) */
	public function channels(): array {
		if ( null === $this->channels ) {
			$list = [];
			if ( Helpers::bool( $this->settings()->get( 'channel_email', true ) ) ) {
				$list['email'] = new EmailChannel( $this );
			}
			if ( Helpers::bool( $this->settings()->get( 'channel_whatsapp', false ) ) ) {
				$list['whatsapp'] = $this->whatsapp();
			}
			$list['whatsapp_bsp'] = new WhatsAppBspChannel();
			$list['sms']          = new SmsChannel();
			$list                 = array_filter( (array) apply_filters( 'webgram_core/notifications/channels', $list, $this ), static fn( $c ) => $c instanceof ChannelInterface );
			$this->channels       = array_filter( $list, static fn( ChannelInterface $c ) => $c->configured() || in_array( $c->id(), [ 'email', 'whatsapp' ], true ) );
		}
		return $this->channels;
	}

	public function channel( string $id ): ?ChannelInterface {
		return $this->channels()[ $id ] ?? null;
	}

	public function event_enabled( string $event, string $channel ): bool {
		$default = (bool) ( Events::all()[ $event ]['default'] ?? false );
		return Helpers::bool( $this->settings()->get( 'on_' . $event . '_' . $channel, $default ) );
	}

	public function whatsapp_connected(): bool {
		return Helpers::bool( $this->settings()->get( 'channel_whatsapp', false ) ) && $this->whatsapp()->configured();
	}

	/** @return array{state: string, html: string} CONNECTED, NOT CONFIGURED or ERROR with the last message */
	public function whatsapp_status(): array {
		if ( ! $this->whatsapp()->configured() ) {
			return [ 'state' => 'NOT CONFIGURED', 'html' => '<span class="wgc-badge wgc-badge--muted">NOT CONFIGURED</span>' ];
		}
		$last = get_option( self::STATUS_OPTION, [] );
		if ( is_array( $last ) && array_key_exists( 'ok', $last ) ) {
			return $last['ok']
				? [ 'state' => 'CONNECTED', 'html' => '<span class="wgc-badge wgc-badge--ok">CONNECTED</span> ' . esc_html( (string) ( $last['display_phone_number'] ?? '' ) ) . ( ! empty( $last['quality_rating'] ) ? ' (' . esc_html( (string) $last['quality_rating'] ) . ')' : '' ) ]
				: [ 'state' => 'ERROR', 'html' => '<span class="wgc-badge wgc-badge--warn">ERROR</span> ' . esc_html( (string) ( $last['error'] ?? '' ) ) ];
		}
		return [ 'state' => 'CONNECTED', 'html' => '<span class="wgc-badge wgc-badge--ok">CONFIGURED</span> ' . esc_html__( 'Not tested yet.', 'webgram-core' ) ];
	}

	public function sync_templates(): array|\WP_Error {
		$templates = $this->whatsapp()->templates();
		if ( ! is_wp_error( $templates ) ) {
			update_option( Templates::OPTION_SYNCED, $templates, false );
		}
		return $templates;
	}

	public function boot(): void {
		( new Events( $this, $this->queue() ) )->register();
		$this->queue()->register();
		$this->optin()->register();
		if ( isset( $this->channels()['email'] ) ) {
			$this->channels()['email']->register_matrix();
		}
		add_filter( 'webgram_core/rest_controllers', fn( array $c ) => array_merge( $c, [ new Rest\WhatsAppController( $this, $this->log() ) ] ) );
		add_filter( 'webgram_core/analytics/notification_rate', fn( array $rate, int $days ) => $this->log()->rate( $days ), 10, 2 );
		add_action( 'webgram_core_daily_maintenance', [ $this, 'retention' ] );
		if ( is_admin() ) {
			( new Admin\LogPage( $this, $this->log(), $this->queue() ) )->register();
		}
	}

	public function retention(): void {
		if ( $this->log()->exists() ) {
			$this->log()->purge_older_than( max( 7, (int) $this->settings()->get( 'retention_days', 180 ) ) );
		}
	}

	public function settings_fields(): array {
		return Settings::fields( $this );
	}
}
