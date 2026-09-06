<?php
namespace Webgram\Core\Modules\AiAssistant\Rest;

use Webgram\Core\Abstracts\RestController;
use Webgram\Core\Modules\AiAssistant\Module;
use Webgram\Core\Modules\AiAssistant\Session;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/** POST /assistant/message and GET /assistant/conversation, both nonce protected and session bound. */
final class AssistantController extends RestController {

	public function __construct( private Module $module ) {}

	public function register_routes(): void {
		$this->route(
			'/assistant/message',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'message' ],
					'permission_callback' => $this->require_nonce( 'wp_rest' ),
					'args'                => [ 'message' => [ 'type' => 'string', 'required' => true ] ],
				],
			]
		);
		$this->route(
			'/assistant/conversation',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'conversation' ],
					'permission_callback' => $this->require_nonce( 'wp_rest' ),
				],
			]
		);
	}

	public function message( WP_REST_Request $request ) {
		$text    = (string) $request->get_param( 'message' );
		$user_id = get_current_user_id();
		$guard   = $this->module->assistant()->guard( $text, $user_id );
		if ( ! $guard['ok'] ) {
			return $this->fail( $guard['code'], $guard['message'], 'rate_limited' === $guard['code'] ? 429 : ( 'login_required' === $guard['code'] ? 401 : 400 ) );
		}
		$session = Session::start();
		return $this->ok( $this->module->assistant()->reply( $text, $session, $user_id ) );
	}

	public function conversation( WP_REST_Request $request ) {
		$session = Session::current();
		return $this->ok( [ 'messages' => '' === $session ? [] : $this->module->assistant()->history( $session ), 'greeting' => $this->module->greeting(), 'suggestions' => $this->module->suggested_questions() ] );
	}
}
