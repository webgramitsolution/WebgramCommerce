<?php
namespace Webgram\Core\Modules\Emails;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** Webgram > Email preview: renders any WooCommerce email with a sample order and sends a test to the admin. */
final class Preview {

	public const SLUG = 'webgram-core-email-preview';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 40 );
		add_action( 'admin_post_webgram_core_email_preview', [ $this, 'render_frame' ] );
		add_action( 'admin_post_webgram_core_email_test', [ $this, 'send_test' ] );
	}

	public function menu(): void {
		add_submenu_page( ModulesPage::parent_slug(), __( 'Email preview', 'webgram-core' ), __( 'Email preview', 'webgram-core' ), 'manage_woocommerce', self::SLUG, [ $this, 'render' ] );
	}

	/** @return array<string, \WC_Email> */
	public static function emails(): array {
		if ( ! class_exists( 'WC_Emails' ) ) {
			return [];
		}
		$out = [];
		foreach ( \WC_Emails::instance()->get_emails() as $email ) {
			if ( $email instanceof \WC_Email ) {
				$out[ $email->id ] = $email;
			}
		}
		return $out;
	}

	public static function sample_order(): ?\WC_Order {
		$id = (int) apply_filters( 'webgram_core/emails/sample_order_id', 0 );
		if ( $id <= 0 ) {
			$orders = wc_get_orders( [ 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'status' => [ 'wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending' ], 'return' => 'ids' ] );
			$id     = (int) ( $orders[0] ?? 0 );
		}
		$order = $id ? wc_get_order( $id ) : false;
		return $order instanceof \WC_Order ? $order : null;
	}

	/** Full HTML of an email for an order (styles inlined by WooCommerce). */
	public static function html( \WC_Email $email, \WC_Order $order ): string {
		$email->object = $order;
		$email->setup_locale();
		if ( method_exists( $email, 'placeholders' ) || property_exists( $email, 'placeholders' ) ) {
			$email->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$email->placeholders['{order_number}'] = $order->get_order_number();
		}
		$email->recipient = $order->get_billing_email();
		$content = $email->get_content();
		$email->restore_locale();
		return (string) $email->style_inline( $content );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$emails  = self::emails();
		$current = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : 'customer_processing_order'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $emails[ $current ] ) ? $current : (string) array_key_first( $emails );
		$order   = self::sample_order();
		$frame   = wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_email_preview', 'email' => $current ], admin_url( 'admin-post.php' ) ), 'webgram_core_email_preview' );
		$test    = wp_nonce_url( add_query_arg( [ 'action' => 'webgram_core_email_test', 'email' => $current ], admin_url( 'admin-post.php' ) ), 'webgram_core_email_test' );
		?>
		<div class="wrap wgc-admin">
			<h1><?php esc_html_e( 'Email preview', 'webgram-core' ); ?></h1>
			<?php if ( isset( $_GET['sent'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( /* translators: %s: email */ __( 'Test email sent to %s.', 'webgram-core' ), get_option( 'admin_email' ) ) ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! $order ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Create at least one order to preview emails with real content.', 'webgram-core' ); ?></p></div>
			<?php endif; ?>
			<form method="get" style="margin:12px 0">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<label><?php esc_html_e( 'Email type', 'webgram-core' ); ?>
					<select name="email" onchange="this.form.submit()">
						<?php foreach ( $emails as $id => $email ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $current, $id ); ?>><?php echo esc_html( $email->get_title() ); ?><?php echo $email->is_enabled() ? '' : ' (' . esc_html__( 'disabled', 'webgram-core' ) . ')'; ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php if ( $order ) : ?>
					<a class="button" href="<?php echo esc_url( $frame ); ?>" target="_blank"><?php esc_html_e( 'Open in new tab', 'webgram-core' ); ?></a>
					<a class="button button-primary" href="<?php echo esc_url( $test ); ?>"><?php esc_html_e( 'Send test to admin', 'webgram-core' ); ?></a>
				<?php endif; ?>
				<span class="description"><?php echo esc_html( $order ? sprintf( /* translators: %s: order number */ __( 'Sample order #%s', 'webgram-core' ), $order->get_order_number() ) : '' ); ?></span>
			</form>
			<?php if ( $order ) : ?>
				<iframe src="<?php echo esc_url( $frame ); ?>" style="width:100%;height:80vh;border:1px solid #dcdcde;background:#fff" title="<?php esc_attr_e( 'Email preview', 'webgram-core' ); ?>"></iframe>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_frame(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_core_email_preview' );
		$emails = self::emails();
		$id     = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : '';
		$order  = self::sample_order();
		if ( ! isset( $emails[ $id ] ) || ! $order ) {
			wp_die( esc_html__( 'Nothing to preview.', 'webgram-core' ) );
		}
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo self::html( $emails[ $id ], $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce email HTML.
		exit;
	}

	public function send_test(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_core_email_test' );
		$emails = self::emails();
		$id     = isset( $_GET['email'] ) ? sanitize_key( wp_unslash( $_GET['email'] ) ) : '';
		$order  = self::sample_order();
		if ( isset( $emails[ $id ] ) && $order ) {
			$email = $emails[ $id ];
			$html  = self::html( $email, $order );
			wp_mail( (string) get_option( 'admin_email' ), '[' . __( 'Test', 'webgram-core' ) . '] ' . $email->get_subject(), $html, [ 'Content-Type: text/html; charset=UTF-8' ] );
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'email' => $id, 'sent' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
