<?php
namespace Webgram\Core\Migrations;

use Webgram\Core\Modules\AiAssistant\ConversationRepository;
use Webgram\Core\Modules\AiAssistant\MessageRepository;
use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/** 0.7.0: AI assistant conversation tables (idempotent through dbDelta). */
final class V0_7_0 {

	public function __construct( private Plugin $plugin ) {}

	public function run(): void {
		( new ConversationRepository() )->install();
		( new MessageRepository() )->install();
	}
}
