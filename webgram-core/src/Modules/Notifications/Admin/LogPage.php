<?php
namespace Webgram\Core\Modules\Notifications\Admin;

use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Modules\Notifications\Events;
use Webgram\Core\Modules\Notifications\Log;
use Webgram\Core\Modules\Notifications\Module;
use Webgram\Core\Modules\Notifications\Queue;

defined( 'ABSPATH' ) || exit;

/** Webgram > Notifications log with filters, retry and resend; order metabox with per-event status. */
final class LogPage {

	public const SLUG = 'webgram-core-notifications';

	public function __construct( private Module $module, private Log $log, private Queue $queue ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 35 );
		add_action( 'admin_post_webgram_core_notification_resend', [ $this, 'resend' ] );
		add_action( 'admin_post_webgram_core_notification_event', [ $this, 'resend_event' ] );
		add_action( 'admin_post_webgram_core_whatsapp_test', [ $this, 'test' ] );
		add_action( 'admin_post_webgram_core_whatsapp_sync', [ $this, 'sync' ] );
		add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
		add_action( 'admin_notices', [ $this, 'notices' ] );
	}

	public function menu(): void {
		add_submenu_page( ModulesPage::parent_slug(), __( 'Notifications log', 'webgram-core' ), __( 'Notifications log', 'webgram-core' ), 'manage_woocommerce', self::SLUG, [ $this, 'render' ] );
	}

	private function flash( string $type, string $message ): void {
		set_transient( 'webgram_core_notif_notice_' . get_current_user_id(), [ $type, $message ], 60 );
	}

	public function notices(): void {
		$n = get_transient( 'webgram_core_notif_notice_' . get_current_user_id() );
		if ( is_array( $n ) ) {
			delete_transient( 'webgram_core_notif_notice_' . get_current_user_id() );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $n[0] ), esc_html( $n[1] ) );
		}
	}

	private function guard( string $nonce ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( $nonce );
	}

	public function resend(): void {
		$this->guard( 'webgram_core_notification_resend' );
		$this->queue->resend( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->flash( 'success', __( 'Notification queued again.', 'webgram-core' ) );
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	public function resend_event(): void {
		$this->guard( 'webgram_core_notification_event' );
		$order = wc_get_order( isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$event = sanitize_key( wp_unslash( $_GET['event'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$chan  = sanitize_key( wp_unslash( $_GET['channel'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $order instanceof \WC_Order && isset( Events::all()[ $event ] ) && $this->module->channel( $chan ) ) {
			$this->queue->resend_event( $order, $event, $chan );
			$this->flash( 'success', __( 'Notification queued.', 'webgram-core' ) );
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	public function test(): void {
		$this->guard( 'webgram_core_whatsapp_test' );
		$r = $this->module->whatsapp()->test();
		update_option( Module::STATUS_OPTION, $r, false );
		$this->flash( $r['ok'] ? 'success' : 'error', $r['ok'] ? sprintf( /* translators: 1: number, 2: quality */ __( 'Connected: %1$s (quality %2$s)', 'webgram-core' ), $r['display_phone_number'], $r['quality_rating'] ?: '-' ) : sprintf( /* translators: %s: error */ __( 'WhatsApp connection failed: %s', 'webgram-core' ), $r['error'] ) );
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	public function sync(): void {
		$this->guard( 'webgram_core_whatsapp_sync' );
		$result = $this->module->sync_templates();
		$this->flash( is_wp_error( $result ) ? 'error' : 'success', is_wp_error( $result ) ? $result->get_error_message() : sprintf( /* translators: %d: count */ __( '%d templates synced from Meta.', 'webgram-core' ), count( $result ) ) );
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters = [ 'status' => sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ), 'channel' => sanitize_key( wp_unslash( $_GET['channel'] ?? '' ) ), 'event' => sanitize_key( wp_unslash( $_GET['event'] ?? '' ) ), 'order_id' => isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0 ];
		$page    = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		// phpcs:enable
		$list  = $this->log->list( $filters, $page );
		$pages = (int) ceil( $list['total'] / 25 );
		?>
		<div class="wrap wgc-admin">
			<h1><?php esc_html_e( 'Notifications log', 'webgram-core' ); ?></h1>
			<form method="get" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<select name="status"><option value=""><?php esc_html_e( 'All statuses', 'webgram-core' ); ?></option>
				<?php
				foreach ( Log::STATUSES as $s ) :
?>
<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $filters['status'], $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option><?php endforeach; ?></select>
				<select name="channel"><option value=""><?php esc_html_e( 'All channels', 'webgram-core' ); ?></option>
				<?php
				foreach ( $this->module->channels() as $c ) :
?>
<option value="<?php echo esc_attr( $c->id() ); ?>" <?php selected( $filters['channel'], $c->id() ); ?>><?php echo esc_html( $c->label() ); ?></option><?php endforeach; ?></select>
				<select name="event"><option value=""><?php esc_html_e( 'All events', 'webgram-core' ); ?></option>
				<?php
				foreach ( Events::all() as $e => $def ) :
?>
<option value="<?php echo esc_attr( $e ); ?>" <?php selected( $filters['event'], $e ); ?>><?php echo esc_html( $def['label'] ); ?></option><?php endforeach; ?></select>
				<input type="number" name="order_id" placeholder="<?php esc_attr_e( 'Order ID', 'webgram-core' ); ?>" value="<?php echo $filters['order_id'] ? (int) $filters['order_id'] : ''; ?>" class="small-text">
				<button class="button"><?php esc_html_e( 'Filter', 'webgram-core' ); ?></button>
				<span class="description"><?php echo esc_html( sprintf( /* translators: %d: rows */ _n( '%d entry', '%d entries', $list['total'], 'webgram-core' ), $list['total'] ) ); ?></span>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Date', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Order', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Event', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Channel', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Recipient', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Status', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Attempts', 'webgram-core' ); ?></th><th><?php esc_html_e( 'Error', 'webgram-core' ); ?></th><th></th></tr></thead>
				<tbody>
					<?php if ( ! $list['rows'] ) : ?>
						<tr><td colspan="9" class="description"><?php esc_html_e( 'No notifications yet.', 'webgram-core' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $list['rows'] as $row ) : ?>
						<?php $retry = wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_notification_resend', 'id' => (int) $row['id'] ], admin_url( 'admin-post.php' ) ), 'webgram_core_notification_resend' ); ?>
						<tr>
							<td><?php echo esc_html( $row['created_at'] ); ?></td>
							<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $row['order_id'] . '&action=edit' ) ); ?>">#<?php echo (int) $row['order_id']; ?></a></td>
							<td><?php echo esc_html( Events::all()[ $row['event'] ]['label'] ?? $row['event'] ); ?></td>
							<td><?php echo esc_html( $row['channel'] ); ?></td>
							<td><?php echo esc_html( (string) $row['recipient_masked'] ); ?></td>
							<td><span class="wgc-badge wgc-badge--<?php echo esc_attr( in_array( $row['status'], [ 'sent', 'delivered', 'read' ], true ) ? 'ok' : ( 'failed' === $row['status'] ? 'warn' : 'muted' ) ); ?>"><?php echo esc_html( $row['status'] ); ?></span></td>
							<td><?php echo (int) $row['attempts']; ?></td>
							<td><?php echo esc_html( trim( (string) $row['error_code'] . ' ' . (string) $row['error_message'] ) ); ?></td>
							<td>
							<?php
							if ( in_array( $row['status'], [ 'failed', 'skipped', 'sent' ], true ) ) :
?>
<a class="button button-small" href="<?php echo esc_url( $retry ); ?>"><?php echo esc_html( 'failed' === $row['status'] ? __( 'Retry', 'webgram-core' ) : __( 'Resend', 'webgram-core' ) ); ?></a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $pages > 1 ) : ?>
				<p><?php echo wp_kses_post( paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $page, 'total' => $pages ] ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function metabox(): void {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'wgc-notifications', __( 'Notifications', 'webgram-core' ), [ $this, 'render_metabox' ], $screen, 'side' );
	}

	public function render_metabox( $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$rows = [];
		foreach ( $this->log->for_order( $order->get_id() ) as $row ) {
			$rows[ $row['event'] . '|' . $row['channel'] ] = $row;
		}
		echo '<table class="widefat" style="font-size:12px"><tbody>';
		foreach ( Events::all() as $event => $def ) {
			foreach ( $this->module->channels() as $channel ) {
				if ( ! $this->module->event_enabled( $event, $channel->id() ) ) {
					continue;
				}
				$row  = $rows[ $event . '|' . $channel->id() ] ?? null;
				$url  = wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_notification_event', 'order_id' => $order->get_id(), 'event' => $event, 'channel' => $channel->id() ], admin_url( 'admin-post.php' ) ), 'webgram_core_notification_event' );
				printf( '<tr><td>%s<br><small>%s</small></td><td>%s</td><td><a href="%s" class="button-link">%s</a></td></tr>', esc_html( $def['label'] ), esc_html( $channel->id() ), esc_html( $row ? $row['status'] . ( $row['error_code'] ? ' (' . $row['error_code'] . ')' : '' ) : '-' ), esc_url( $url ), esc_html__( 'Send', 'webgram-core' ) );
			}
		}
		echo '</tbody></table>';
	}
}
