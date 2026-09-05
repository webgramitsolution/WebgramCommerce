<?php
namespace Webgram\Core\Modules\Notifications\Rest;

use Webgram\Core\Abstracts\RestController;
use Webgram\Core\Modules\Notifications\Log;
use Webgram\Core\Modules\Notifications\Module;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/** Meta webhook (verification and status updates) plus admin test and template sync endpoints. */
final class WhatsAppController extends RestController {

	public function __construct( private Module $module, private Log $log ) {}

	public function register_routes(): void {
		$this->route( '/whatsapp/webhook', [
			[ 'methods' => 'GET', 'callback' => [ $this, 'verify' ], 'permission_callback' => $this->allow_public() ],
			[ 'methods' => 'POST', 'callback' => [ $this, 'receive' ], 'permission_callback' => $this->allow_public() ],
		] );
		$this->route( '/whatsapp/test', [ [ 'methods' => 'POST', 'callback' => [ $this, 'test' ], 'permission_callback' => $this->require_capability( 'manage_woocommerce' ) ] ] );
		$this->route( '/whatsapp/templates/sync', [ [ 'methods' => 'POST', 'callback' => [ $this, 'sync' ], 'permission_callback' => $this->require_capability( 'manage_woocommerce' ) ] ] );
	}

	/** Pure: X-Hub-Signature-256 check. */
	public static function verify_signature( string $body, string $header, string $app_secret ): bool {
		if ( '' === $app_secret || ! str_starts_with( $header, 'sha256=' ) ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', $body, $app_secret ), substr( $header, 7 ) );
	}

	/**
	 * Pure: statuses from a webhook payload.
	 *
	 * @return array<int, array{id: string, status: string, error_code: string, error_message: string}>
	 */
	public static function parse_statuses( array $payload ): array {
		$out = [];
		foreach ( (array) ( $payload['entry'] ?? [] ) as $entry ) {
			foreach ( (array) ( $entry['changes'] ?? [] ) as $change ) {
				foreach ( (array) ( $change['value']['statuses'] ?? [] ) as $status ) {
					if ( ! is_array( $status ) || empty( $status['id'] ) ) {
						continue;
					}
					$out[] = [
						'id'            => (string) $status['id'],
						'status'        => strtolower( (string) ( $status['status'] ?? '' ) ),
						'error_code'    => (string) ( $status['errors'][0]['code'] ?? '' ),
						'error_message' => (string) ( $status['errors'][0]['title'] ?? $status['errors'][0]['message'] ?? '' ),
					];
				}
			}
		}
		return $out;
	}

	public function verify( WP_REST_Request $request ) {
		$token = (string) $this->module->settings()->get( 'wa_verify_token', '' );
		if ( 'subscribe' === $request->get_param( 'hub_mode' ) && '' !== $token && hash_equals( $token, (string) $request->get_param( 'hub_verify_token' ) ) ) {
			return new \WP_REST_Response( (int) $request->get_param( 'hub_challenge' ) ?: (string) $request->get_param( 'hub_challenge' ), 200 );
		}
		return $this->fail( 'forbidden', 'Verification failed', 403 );
	}

	public function receive( WP_REST_Request $request ) {
		$secret = (string) $this->module->settings()->get( 'wa_app_secret', '' );
		$secret = '' === $secret ? '' : \webgram_core()->crypto()->decrypt( $secret );
		if ( ! self::verify_signature( (string) $request->get_body(), (string) $request->get_header( 'X-Hub-Signature-256' ), $secret ) ) {
			return $this->fail( 'forbidden', 'Invalid signature', 403 );
		}
		$updated = 0;
		foreach ( self::parse_statuses( (array) $request->get_json_params() ) as $s ) {
			$map = [ 'sent' => 'sent', 'delivered' => 'delivered', 'read' => 'read', 'failed' => 'failed' ];
			if ( isset( $map[ $s['status'] ] ) && $this->log->set_status_by_provider_id( $s['id'], $map[ $s['status'] ], $s['error_code'], $s['error_message'] ) ) {
				$updated++;
			}
		}
		return $this->ok( [ 'updated' => $updated ] );
	}

	public function test( WP_REST_Request $request ) {
		return $this->ok( $this->module->whatsapp()->test() );
	}

	public function sync( WP_REST_Request $request ) {
		$result = $this->module->sync_templates();
		return is_wp_error( $result ) ? $this->fail( 'meta', $result->get_error_message() ) : $this->ok( [ 'count' => count( $result ) ] );
	}
}
