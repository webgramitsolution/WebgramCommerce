<?php
namespace Webgram\Core\Rest;

use Webgram\Core\Abstracts\RestController;
use Webgram\Core\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Collects REST controllers from booted modules and registers them under webgram/v1.
 * Modules register controllers with: add_filter( 'webgram_core/rest_controllers', fn( $c ) => [ ...$c, new MyController() ] );
 */
final class Router {

	public function __construct( private Container $container ) {}

	public function register_routes(): void {
		$controllers = (array) apply_filters( 'webgram_core/rest_controllers', [ new SystemController() ] );

		foreach ( $controllers as $controller ) {
			if ( $controller instanceof RestController ) {
				$controller->register_routes();
			}
		}
	}
}
