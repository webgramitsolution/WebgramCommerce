<?php
namespace Webgram\Core\Modules\Integrations;

defined( 'ABSPATH' ) || exit;

/** [webgram_{id} ...] for every definition that renders through a callable and has no shortcode of its own. */
final class Shortcodes {

	public function __construct( private Registry $registry ) {}

	public function register(): void {
		add_action( 'init', [ $this, 'add' ], 20 );
	}

	public function add(): void {
		foreach ( $this->registry->all() as $id => $def ) {
			if ( ! empty( $def['render'] ) && ! shortcode_exists( 'webgram_' . $id ) ) {
				add_shortcode( 'webgram_' . $id, fn( $atts ) => $this->registry->render( $id, (array) $atts ) );
			}
		}
	}
}
