<?php
namespace Webgram\Core\Modules\WooEnhancements\Admin;

use Webgram\Core\Admin\ModulesPage;
use Webgram\Core\Modules\WooEnhancements\Module;
use Webgram\Core\Modules\WooEnhancements\PincodeChecker;
use Webgram\Core\Modules\WooEnhancements\PincodeRepository;

defined( 'ABSPATH' ) || exit;

/** Webgram > Pincodes: CSV import into wg_pincodes, row count, clear. */
final class PincodesPage {

	public const SLUG = 'webgram-pincodes';

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 30 );
		add_action( 'admin_post_webgram_pincodes_import', [ $this, 'import' ] );
		add_action( 'admin_post_webgram_pincodes_clear', [ $this, 'clear' ] );
	}

	public function menu(): void {
		add_submenu_page( ModulesPage::parent_slug(), __( 'Pincodes', 'webgram-core' ), __( 'Pincodes', 'webgram-core' ), 'manage_woocommerce', self::SLUG, [ $this, 'render' ] );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$repo   = new PincodeRepository();
		$count  = $repo->count();
		$notice = isset( $_GET['wg_notice'] ) ? sanitize_key( wp_unslash( $_GET['wg_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$n      = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$s      = isset( $_GET['s'] ) ? absint( $_GET['s'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap wgc-admin">
			<h1><?php esc_html_e( 'Pincodes', 'webgram-core' ); ?></h1>
			<?php if ( 'imported' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php printf( esc_html__( '%1$d pincodes imported or updated, %2$d rows skipped as invalid.', 'webgram-core' ), (int) $n, (int) $s ); ?></p></div>
			<?php elseif ( 'invalid' === $notice ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The file could not be read. Upload a UTF-8 CSV under 8 MB.', 'webgram-core' ); ?></p></div>
			<?php elseif ( 'cleared' === $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pincode table cleared.', 'webgram-core' ); ?></p></div>
			<?php endif; ?>

			<p><?php printf( esc_html__( 'Rows in the offline table: %d.', 'webgram-core' ), (int) $count ); ?> <?php printf( esc_html__( 'Delivery data mode: %s.', 'webgram-core' ), esc_html( (string) $this->module->settings()->get( 'pincode_mode', 'all' ) ) ); ?></p>

			<h2><?php esc_html_e( 'Import CSV', 'webgram-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Columns: pincode, city, state, deliverable (1/0), cod (1/0), eta_days. A header row is optional. Existing pincodes are updated.', 'webgram-core' ); ?> <a href="<?php echo esc_url( WEBGRAM_CORE_URL . 'data/pincodes-sample.csv' ); ?>"><?php esc_html_e( 'Download sample', 'webgram-core' ); ?></a></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="webgram_pincodes_import">
				<?php wp_nonce_field( 'webgram_pincodes_import' ); ?>
				<p><input type="file" name="pincodes_csv" accept=".csv,text/csv" required></p>
				<?php submit_button( __( 'Import', 'webgram-core' ), 'primary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Clear table', 'webgram-core' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete every imported pincode? This cannot be undone.', 'webgram-core' ) ); ?>');">
				<input type="hidden" name="action" value="webgram_pincodes_clear">
				<?php wp_nonce_field( 'webgram_pincodes_clear' ); ?>
				<?php submit_button( __( 'Clear all pincodes', 'webgram-core' ), 'delete', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public function import(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_pincodes_import' );
		$file = $_FILES['pincodes_csv'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( ! $file || ! empty( $file['error'] ) || ! is_uploaded_file( $file['tmp_name'] ) || (int) $file['size'] > 8 * MB_IN_BYTES ) {
			$this->redirect( 'invalid' );
		}
		$check = wp_check_filetype_and_ext( $file['tmp_name'], (string) $file['name'], [ 'csv' => 'text/csv', 'txt' => 'text/plain' ] );
		if ( empty( $check['ext'] ) && ! str_ends_with( strtolower( (string) $file['name'] ), '.csv' ) ) {
			$this->redirect( 'invalid' );
		}
		$csv = (string) file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! mb_check_encoding( $csv, 'UTF-8' ) ) {
			$csv = mb_convert_encoding( $csv, 'UTF-8', 'ISO-8859-1' );
		}
		$parsed = PincodeChecker::parse_csv( $csv, PincodeChecker::country() );
		$n      = ( new PincodeRepository() )->upsert_many( $parsed['rows'] );
		if ( $n > 0 && 'csv' !== $this->module->settings()->get( 'pincode_mode' ) ) {
			$this->module->settings()->set( 'pincode_mode', 'csv' );
		}
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'wg_notice' => 'imported', 'n' => $n, 's' => $parsed['skipped'] ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function clear(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'webgram-core' ), 403 );
		}
		check_admin_referer( 'webgram_pincodes_clear' );
		( new PincodeRepository() )->truncate();
		$this->redirect( 'cleared' );
	}

	private function redirect( string $notice ): void {
		wp_safe_redirect( add_query_arg( [ 'page' => self::SLUG, 'wg_notice' => $notice ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
