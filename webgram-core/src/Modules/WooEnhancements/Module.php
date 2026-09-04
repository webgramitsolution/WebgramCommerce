<?php
namespace Webgram\Core\Modules\WooEnhancements;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Enhancements. Phase 1 ships the pincode / location picker (the header needs it). Buy Now,
 * recently viewed, specifications, product video, contact seller, bulk inquiry and track order arrive in Phase 2.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'woo_enhancements';
	}

	public function name(): string {
		return __( 'WooCommerce Enhancements', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Pincode delivery check and location picker, Buy Now, recently viewed, specifications table, bulk inquiry, contact seller and track order.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [ 'woocommerce' ];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 2;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		( new PincodeChecker( $this ) )->register();
		( new Location( $this ) )->register();
		if ( is_admin() ) {
			( new Admin\PincodesPage( $this ) )->register();
		}
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_filter( 'webgram/export_data', [ $this, 'export' ] );
		add_action( 'webgram/import_data', [ $this, 'import' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-woo-enhancements', 'css/woo-enhancements.css' );
		$assets->script( 'webgram-core-woo-enhancements', 'js/woo-enhancements.js', [ 'webgram-core-base' ] );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'h_pincode', 'label' => __( 'Pincode and location', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'Header "Deliver to" picker and product page delivery check.', 'webgram-core' ) ],
			[ 'id' => 'pincode_mode', 'label' => __( 'Delivery data', 'webgram-core' ), 'type' => 'select', 'options' => [ 'all' => __( 'All pincodes deliverable', 'webgram-core' ), 'csv' => __( 'Imported pincode list', 'webgram-core' ) ], 'default' => 'all', 'description' => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=webgram-pincodes' ) ), esc_html__( 'Import a CSV of pincodes', 'webgram-core' ) ) ],
			[ 'id' => 'pincode_default_eta', 'label' => __( 'Default delivery time (days)', 'webgram-core' ), 'type' => 'number', 'min' => 0, 'max' => 60, 'default' => 4 ],
			[ 'id' => 'pincode_default_cod', 'label' => __( 'Cash on delivery available by default', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
			[ 'id' => 'pincode_unknown_deliverable', 'label' => __( 'Treat pincodes missing from the list as deliverable', 'webgram-core' ), 'type' => 'checkbox', 'default' => false ],
			[ 'id' => 'location_show_header', 'label' => __( 'Show "Deliver to" pill in the header', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Themes with a header builder place the element themselves.', 'webgram-core' ) ],
			[ 'id' => 'location_label', 'label' => __( 'Pill label', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Deliver to', 'webgram-core' ) ],
			[ 'id' => 'location_placeholder', 'label' => __( 'Pill value when empty', 'webgram-core' ), 'type' => 'text', 'default' => __( 'Select location', 'webgram-core' ) ],
			[ 'id' => 'h_geo', 'label' => __( 'Current location button', 'webgram-core' ), 'type' => 'heading', 'description' => __( 'The reverse geocoding adapter is chosen under API integrations (Site Tools).', 'webgram-core' ) ],
		];
	}

	public function export( array $data ): array {
		$data['core']['woo_enhancements'] = $this->settings()->all();
		return $data;
	}

	public function import( array $core ): void {
		if ( current_user_can( 'manage_options' ) && ! empty( $core['woo_enhancements'] ) && is_array( $core['woo_enhancements'] ) ) {
			$allowed = [ 'pincode_mode', 'pincode_default_eta', 'pincode_default_cod', 'pincode_unknown_deliverable', 'location_show_header', 'location_label', 'location_placeholder' ];
			$clean   = [];
			foreach ( $allowed as $key ) {
				if ( array_key_exists( $key, $core['woo_enhancements'] ) ) {
					$value         = $core['woo_enhancements'][ $key ];
					$clean[ $key ] = is_bool( $value ) || is_int( $value ) ? $value : sanitize_text_field( (string) $value );
				}
			}
			$this->settings()->save( array_merge( $this->settings()->all(), $clean ) );
		}
	}

	public function activate(): void {
		( new PincodeRepository() )->install();
	}

	public function uninstall(): void {
		( new PincodeRepository() )->drop();
		$this->settings()->delete();
	}
}
