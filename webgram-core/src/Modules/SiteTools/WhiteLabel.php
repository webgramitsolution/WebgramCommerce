<?php
namespace Webgram\Core\Modules\SiteTools;

defined( 'ABSPATH' ) || exit;

/** White label: renamed panel and logo, hidden admin sections. Never touches code attribution or licensing. */
final class WhiteLabel {

	public function __construct( private Module $module ) {}

	public function register(): void {
		add_filter( 'webgram/white_label', [ $this, 'data' ] );
		add_filter( 'webgram_core/brand_name', [ $this, 'name' ] );
		add_action( 'admin_menu', [ $this, 'hide_pages' ], 999 );
	}

	public function data( array $data ): array {
		$s = $this->module->settings();
		$name = trim( (string) $s->get( 'wl_name', '' ) );
		if ( '' !== $name ) {
			$data['name'] = $name;
		}
		$logo = (int) $s->get( 'wl_logo', 0 );
		if ( $logo ) {
			$data['logo'] = (string) wp_get_attachment_image_url( $logo, 'medium' );
		}
		$data['hide'] = (array) $s->get( 'wl_hide_sections', [] );
		return $data;
	}

	public function name( string $name ): string {
		$custom = trim( (string) $this->module->settings()->get( 'wl_name', '' ) );
		return '' !== $custom ? $custom : $name;
	}

	public function hide_pages(): void {
		$hide = (array) $this->module->settings()->get( 'wl_hide_sections', [] );
		if ( ! $hide || current_user_can( 'manage_network' ) ) {
			return;
		}
		$parent = \Webgram\Core\Admin\ModulesPage::parent_slug();
		$map    = [ 'status' => 'webgram-status', 'import_export' => 'webgram-import-export', 'modules' => 'webgram-core' ];
		foreach ( $hide as $key ) {
			if ( isset( $map[ $key ] ) ) {
				remove_submenu_page( $parent, $map[ $key ] );
			}
		}
	}
}
