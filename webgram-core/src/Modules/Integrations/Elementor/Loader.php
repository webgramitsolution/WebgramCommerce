<?php
namespace Webgram\Core\Modules\Integrations\Elementor;

use Webgram\Core\Modules\Integrations\Registry;

defined( 'ABSPATH' ) || exit;

/** Registers the "Webgram" categories and one SectionWidget per definition when Elementor is active. */
final class Loader {

	public function __construct( private Registry $registry ) {}

	public function register(): void {
		add_action( 'elementor/elements/categories_registered', [ $this, 'categories' ] );
		add_action( 'elementor/widgets/register', [ $this, 'widgets' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
	}

	public function categories( $manager ): void {
		$manager->add_category( 'webgram', [ 'title' => __( 'Webgram', 'webgram-core' ), 'icon' => 'eicon-woocommerce' ] );
		$manager->add_category( 'webgram-product', [ 'title' => __( 'Webgram Product Layout', 'webgram-core' ), 'icon' => 'eicon-single-product' ] );
	}

	public function widgets( $manager ): void {
		foreach ( $this->registry->all() as $def ) {
			$manager->register( new SectionWidget( [], [ 'wgc_def' => $def ] ) );
		}
	}

	public function editor_styles(): void {
		wp_add_inline_style( 'elementor-editor', '.elementor-panel .elementor-element .icon .eicon-woocommerce{color:#a0181f}' );
	}
}
