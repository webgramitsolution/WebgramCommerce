<?php
namespace Webgram\Core\Modules\Integrations;

use Webgram\Core\Abstracts\Module as BaseModule;
use Webgram\Core\Support\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations: one registry of section definitions rendered as Elementor widgets (category "Webgram"),
 * server-rendered Gutenberg blocks and shortcodes. Also owns the testimonials post type and the single product
 * layout widgets used by Core Layouts. Elementor stays optional.
 */
final class Module extends BaseModule {

	private ?Registry $registry = null;

	public function id(): string {
		return 'integrations';
	}

	public function name(): string {
		return __( 'Elementor and Gutenberg', 'webgram-core' );
	}

	public function description(): string {
		return __( 'Webgram widgets, blocks and shortcodes for homepage sections and product layouts. Elementor stays optional.', 'webgram-core' );
	}

	public function dependencies(): array {
		return [];
	}

	public function phase(): int {
		return 5;
	}

	public function is_implemented(): bool {
		return true;
	}

	public function registry(): Registry {
		return $this->registry ??= new Registry();
	}

	public function boot(): void {
		( new Sections() )->register();
		( new ProductParts() )->register();
		( new Testimonials() )->register();
		( new Shortcodes( $this->registry() ) )->register();
		if ( Helpers::bool( $this->settings()->get( 'blocks', true ) ) ) {
			( new Blocks( $this->registry() ) )->register();
		}
		if ( Helpers::bool( $this->settings()->get( 'elementor', true ) ) && ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) ) {
			( new Elementor\Loader( $this->registry() ) )->register();
		}
		add_action( 'webgram_core/register_assets', [ $this, 'register_module_assets' ] );
	}

	public function register_module_assets( \Webgram\Core\Support\Assets $assets ): void {
		$assets->style( 'webgram-core-sections', 'css/sections.css' );
	}

	public function settings_fields(): array {
		return [
			[ 'id' => 'elementor', 'label' => __( 'Register Elementor widgets', 'webgram-core' ), 'type' => 'checkbox', 'default' => true, 'description' => __( 'Only applies when Elementor is active.', 'webgram-core' ) ],
			[ 'id' => 'blocks', 'label' => __( 'Register Gutenberg blocks', 'webgram-core' ), 'type' => 'checkbox', 'default' => true ],
		];
	}

	public function render( string $id, array $args = [] ): string {
		return $this->registry()->render( $id, $args );
	}
}
