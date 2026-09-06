<?php
namespace Webgram\Core\Modules\AiAssistant;

defined( 'ABSPATH' ) || exit;

/** Retention cron plus WordPress privacy exporter and eraser for logged in shoppers' conversations. */
final class Privacy {

	public function __construct( private Module $module, private ConversationRepository $conversations, private MessageRepository $messages ) {}

	public function register(): void {
		add_action( 'webgram_core_daily_maintenance', [ $this, 'retention' ] );
		add_action( 'init', [ $this, 'schedule' ] );
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'exporter' ] );
		add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'eraser' ] );
	}

	public function schedule(): void {
		if ( ! wp_next_scheduled( 'webgram_core_daily_maintenance' ) ) {
			wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', 'webgram_core_daily_maintenance' );
		}
	}

	public function retention(): void {
		$days = max( 1, (int) $this->module->settings()->get( 'retention_days', 90 ) );
		if ( ! $this->conversations->exists() ) {
			return;
		}
		$ids = $this->conversations->ids_older_than( $days );
		if ( $ids ) {
			$this->messages->delete_for_conversations( $ids );
			$this->conversations->delete_ids( $ids );
		}
	}

	public function exporter( array $exporters ): array {
		$exporters['webgram-ai-assistant'] = [ 'exporter_friendly_name' => __( 'Webgram AI assistant conversations', 'webgram-core' ), 'callback' => [ $this, 'export' ] ];
		return $exporters;
	}

	public function eraser( array $erasers ): array {
		$erasers['webgram-ai-assistant'] = [ 'eraser_friendly_name' => __( 'Webgram AI assistant conversations', 'webgram-core' ), 'callback' => [ $this, 'erase' ] ];
		return $erasers;
	}

	public function export( string $email ): array {
		$user = get_user_by( 'email', $email );
		$data = [];
		if ( $user && $this->conversations->exists() ) {
			foreach ( $this->conversations->ids_for_user( (int) $user->ID ) as $conv_id ) {
				foreach ( $this->messages->recent( $conv_id, 500 ) as $row ) {
					$data[] = [
						'group_id'    => 'webgram-ai-assistant',
						'group_label' => __( 'AI assistant conversations', 'webgram-core' ),
						'item_id'     => 'wg-ai-message-' . $row['id'],
						'data'        => [
							[ 'name' => __( 'Date', 'webgram-core' ), 'value' => $row['created_at'] ],
							[ 'name' => __( 'Role', 'webgram-core' ), 'value' => $row['role'] ],
							[ 'name' => __( 'Message', 'webgram-core' ), 'value' => $row['content'] ],
						],
					];
				}
			}
		}
		return [ 'data' => $data, 'done' => true ];
	}

	public function erase( string $email ): array {
		$user    = get_user_by( 'email', $email );
		$removed = false;
		if ( $user && $this->conversations->exists() ) {
			$ids = $this->conversations->ids_for_user( (int) $user->ID );
			if ( $ids ) {
				$this->messages->delete_for_conversations( $ids );
				$this->conversations->delete_ids( $ids );
				$removed = true;
			}
		}
		return [ 'items_removed' => $removed, 'items_retained' => false, 'messages' => [], 'done' => true ];
	}
}
