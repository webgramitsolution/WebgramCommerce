<?php
namespace Webgram\Core\Modules\Analytics;

use Webgram\Core\Admin\ModulesPage;

defined( 'ABSPATH' ) || exit;

/** Webgram > Analytics: cards and 7 or 30 day tables with inline SVG bars (no charting dependency). */
final class Reports {

	public const SLUG = 'webgram-core-analytics';

	public function __construct( private Module $module, private EventRepository $events ) {}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ], 30 );
	}

	public function menu(): void {
		add_submenu_page( ModulesPage::parent_slug(), __( 'Analytics', 'webgram-core' ), __( 'Analytics', 'webgram-core' ), 'manage_woocommerce', self::SLUG, [ $this, 'render' ] );
	}

	/** Pure: bar heights (0 to 100) for a date series over N days ending today. */
	public static function series( array $daily, int $days, string $today ): array {
		$out = [];
		$max = max( 1, $daily ? max( $daily ) : 0 );
		$end = strtotime( $today . ' 00:00:00 UTC' ) ?: time();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$day   = gmdate( 'Y-m-d', $end - $i * DAY_IN_SECONDS );
			$n     = (int) ( $daily[ $day ] ?? 0 );
			$out[] = [ 'day' => $day, 'n' => $n, 'pct' => (int) round( $n * 100 / $max ) ];
		}
		return $out;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$days   = isset( $_GET['days'] ) && '30' === $_GET['days'] ? 30 : 7; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$counts = $this->events->counts( $days );
		$rate   = (array) apply_filters( 'webgram_core/analytics/notification_rate', [ 'sent' => 0, 'delivered' => 0 ], $days );
		$cards  = [
			[ __( 'Assistant conversations', 'webgram-core' ), (int) ( $counts['chat_open'] ?? 0 ), 'chat_open' ],
			[ __( 'Reel plays', 'webgram-core' ), (int) ( $counts['reel_play'] ?? 0 ), 'reel_play' ],
			[ __( 'Wishlist adds', 'webgram-core' ), (int) ( $counts['wishlist_add'] ?? 0 ), 'wishlist_add' ],
			[ __( 'Product views', 'webgram-core' ), (int) ( $counts['product_view'] ?? 0 ), 'product_view' ],
			[ __( 'Notification delivery', 'webgram-core' ), $rate['sent'] ? round( $rate['delivered'] * 100 / $rate['sent'] ) . '%' : '-', '' ],
		];
		$tables = [
			'reel_play'        => __( 'Most played reels', 'webgram-core' ),
			'product_view'     => __( 'Most viewed products', 'webgram-core' ),
			'chat_product_click' => __( 'Products clicked in chat', 'webgram-core' ),
			'wishlist_add'     => __( 'Most wishlisted', 'webgram-core' ),
		];
		\webgram_core()->view( 'analytics/dashboard', [ 'days' => $days, 'cards' => $cards, 'tables' => $tables, 'events' => $this->events, 'counts' => $counts, 'installed' => $this->events->exists(), 'sample' => (int) $this->module->settings()->get( 'sample', 100 ) ] );
	}
}
