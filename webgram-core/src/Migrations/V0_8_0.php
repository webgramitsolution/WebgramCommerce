<?php
namespace Webgram\Core\Migrations;

use Webgram\Core\Modules\Analytics\EventRepository;
use Webgram\Core\Modules\Invoice\SequenceRepository;
use Webgram\Core\Modules\Notifications\Log;
use Webgram\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/** 0.8.0: analytics events, invoice sequence and notification log tables (idempotent through dbDelta). */
final class V0_8_0 {

	public function __construct( private Plugin $plugin ) {}

	public function run(): void {
		( new EventRepository() )->install();
		( new SequenceRepository() )->install();
		( new Log() )->install();
	}
}
