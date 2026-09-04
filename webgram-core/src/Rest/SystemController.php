<?php
namespace Webgram\Core\Rest;

use Webgram\Core\Abstracts\RestController;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-only status endpoint used by the Modules screen and support diagnostics.
 */
final class SystemController extends RestController {

	public function register_routes(): void {
		$this->route(
			'/system/status',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'status' ],
					'permission_callback' => $this->require_capability( 'manage_options' ),
				],
			]
		);
	}

	public function status(): \WP_REST_Response {
		$plugin  = \webgram_core();
		$modules = [];
		foreach ( $plugin->modules()->all() as $id => $module ) {
			$modules[ $id ] = [
				'name'        => $module->name(),
				'active'      => $plugin->modules()->is_active( $id ),
				'implemented' => $module->is_implemented(),
				'blocked'     => $plugin->modules()->blocked_reason( $id ),
			];
		}

		return $this->ok(
			[
				'version'     => WEBGRAM_CORE_VERSION,
				'db_version'  => get_option( 'webgram_core_db_version' ),
				'php'         => PHP_VERSION,
				'woocommerce' => $plugin->is_woocommerce_active() ? ( defined( 'WC_VERSION' ) ? WC_VERSION : true ) : false,
				'elementor'   => $plugin->is_elementor_active(),
				'theme'       => wp_get_theme()->get( 'Name' ),
				'theme_support' => (bool) get_theme_support( 'webgram-core' ),
				'sodium'      => $plugin->crypto()->is_available(),
				'modules'     => $modules,
			]
		);
	}
}
