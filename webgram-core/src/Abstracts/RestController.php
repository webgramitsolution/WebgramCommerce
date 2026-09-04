<?php
namespace Webgram\Core\Abstracts;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Base REST controller under the webgram/v1 namespace.
 * Every route must declare an explicit permission callback; there is no default that returns true.
 */
abstract class RestController {

	public const NAMESPACE = 'webgram/v1';

	abstract public function register_routes(): void;

	protected function route( string $path, array $args ): void {
		foreach ( (array) $args as $definition ) {
			if ( empty( $definition['permission_callback'] ) ) {
				_doing_it_wrong( static::class, 'Every Webgram REST route needs a permission_callback.', '0.1.0' );
				return;
			}
		}
		register_rest_route( self::NAMESPACE, $path, $args );
	}

	/** Public read endpoints that only expose already-public data. */
	protected function allow_public(): callable {
		return '__return_true';
	}

	protected function require_capability( string $cap ): callable {
		return static fn() => current_user_can( $cap );
	}

	protected function require_login(): callable {
		return static fn() => is_user_logged_in();
	}

	/** Frontend nonce check for unauthenticated write endpoints (chat, events, track order). */
	protected function require_nonce( string $action = 'wp_rest' ): callable {
		return static function ( WP_REST_Request $request ) use ( $action ) {
			$nonce = $request->get_header( 'X-WP-Nonce' ) ?: $request->get_param( '_wpnonce' );
			return (bool) wp_verify_nonce( (string) $nonce, $action );
		};
	}

	protected function ok( mixed $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( [ 'success' => true, 'data' => $data ], $status );
	}

	protected function fail( string $code, string $message, int $status = 400, array $data = [] ): WP_Error {
		return new WP_Error( 'webgram_' . $code, $message, array_merge( [ 'status' => $status ], $data ) );
	}
}
