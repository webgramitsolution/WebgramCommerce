<?php
namespace Webgram\Core\Modules\SiteTools;

use Webgram\Core\Abstracts\Module as BaseModule;

defined( 'ABSPATH' ) || exit;

/**
 * Site Tools: HTML Blocks, Layouts with assignment conditions, promo popup, age verification, cookie notice,
 * maintenance mode, white label, custom JS and the optional portfolio post type.
 *
 * Feature settings live in webgram_core_settings_site_tools. When the active theme provides the Webgram Theme
 * Settings panel, the tabs are registered there through webgram/settings/tabs; otherwise they render on the Core
 * Settings screen so the module works with any theme.
 */
final class Module extends BaseModule {

	public function id(): string {
		return 'site_tools';
	}

	public function name(): string {
		return __( 'Site Tools', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Layouts, HTML Blocks, promo popup, age verification, cookie notice, maintenance mode, white label and custom JS.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function default_enabled(): bool {
		return true;
	}

	public function phase(): int {
		return 1;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function boot(): void {
		( new Blocks( $this ) )->register();
		( new Layouts\PostType( $this ) )->register();
		( new Layouts\Resolver( $this ) )->register();
		( new PromoPopup( $this ) )->register();
		( new CookieNotice( $this ) )->register();
		( new AgeVerify( $this ) )->register();
		( new Maintenance( $this ) )->register();
		( new WhiteLabel( $this ) )->register();
		( new CustomJs( $this ) )->register();
		( new Portfolio( $this ) )->register();
		( new Settings( $this ) )->register();

		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
		add_filter( 'webgram/help/faqs', [ $this, 'help_faqs' ] );
		add_filter( 'webgram/export_data', [ $this, 'export' ] );
		add_action( 'webgram/import_data', [ $this, 'import' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-site-tools', 'css/site-tools.css' );
		$assets->script( 'webgram-core-site-tools', 'js/site-tools.js', [ 'webgram-core-base' ] );
	}

	/** Core settings page fallback (any theme): every tab's fields flattened with headings. */
	public function settings_fields(): array {
		if ( \Webgram\Core\Admin\ModulesPage::theme_has_panel() ) {
			return [
				[ 'id' => 'panel_link', 'label' => __( 'Site Tools settings', 'webgram-core' ), 'type' => 'link', 'url' => admin_url( 'admin.php?page=webgram&tab=promo_popup' ), 'button' => __( 'Open Theme Settings', 'webgram-core' ), 'description' => __( 'Promo popup, age verification, cookie notice, maintenance, white label and custom JS are configured in the Webgram Theme Settings panel.', 'webgram-core' ) ],
			];
		}
		$out = [];
		foreach ( Settings::definitions( $this ) as $tab ) {
			$out[] = [ 'id' => 'heading_' . $tab['id'], 'label' => $tab['label'], 'type' => 'heading', 'description' => $tab['description'] ?? '' ];
			foreach ( $tab['sections'] as $section ) {
				foreach ( (array) ( $section['fields'] ?? [] ) as $id => $field ) {
					$field['id'] = $id;
					$out[]       = $field;
				}
			}
		}
		return $out;
	}

	/** Pure parser: blank-line separated blocks, first line question, rest answer. */
	public static function parse_faqs( string $text ): array {
		$out = [];
		foreach ( preg_split( '/(\r?\n){2,}/', trim( $text ) ) ?: [] as $block ) {
			$lines = array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', $block ) ?: [] ), 'strlen' ) );
			if ( count( $lines ) < 2 ) {
				continue;
			}
			$out[] = [ 'q' => array_shift( $lines ), 'a' => implode( "\n", $lines ) ];
		}
		return $out;
	}

	public function help_faqs( array $faqs ): array {
		return array_merge( $faqs, self::parse_faqs( (string) $this->settings()->get( 'help_faqs', '' ) ) );
	}

	public function export( array $data ): array {
		$data['core']['site_tools'] = $this->settings()->all();
		return $data;
	}

	public function import( array $core ): void {
		if ( ! current_user_can( 'manage_options' ) || empty( $core['site_tools'] ) || ! is_array( $core['site_tools'] ) ) {
			return;
		}
		$fields = [];
		foreach ( Settings::definitions( $this ) as $tab ) {
			foreach ( $tab['sections'] as $section ) {
				foreach ( (array) ( $section['fields'] ?? [] ) as $id => $field ) {
					$field['id']   = $id;
					$fields[ $id ] = $field;
				}
			}
		}
		$clean = Settings::sanitize_values( $fields, $core['site_tools'] );
		$this->settings()->save( array_merge( $this->settings()->all(), $clean ) );
	}

	public function activate(): void {
		( new Layouts\PostType( $this ) )->register_types();
		( new Blocks( $this ) )->register_type();
		flush_rewrite_rules();
	}

	public function uninstall(): void {
		$this->settings()->delete();
	}
}
