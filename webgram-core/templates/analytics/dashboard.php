<?php
/**
 * Analytics dashboard. $args: days, cards, tables, events (repository), counts, installed, sample.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Modules\Analytics\Reports;

$wgc_base = admin_url( 'admin.php?page=' . Reports::SLUG );
?>
<div class="wrap wgc-admin wgc-analytics">
	<h1><?php esc_html_e( 'Webgram Analytics', 'webgram-core' ); ?></h1>
	<p class="description"><?php echo esc_html( sprintf( /* translators: %d: percent */ __( 'First party events stored on this site. Sampling: %d%% of visitors. No IP addresses are stored.', 'webgram-core' ), (int) $args['sample'] ) ); ?></p>
	<?php if ( ! $args['installed'] ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'The events table does not exist yet. Deactivate and activate Webgram Core, or enable the Analytics module again, to create it.', 'webgram-core' ); ?></p></div>
	<?php endif; ?>
	<p>
		<a class="button <?php echo 7 === $args['days'] ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $wgc_base ); ?>"><?php esc_html_e( 'Last 7 days', 'webgram-core' ); ?></a>
		<a class="button <?php echo 30 === $args['days'] ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'days', 30, $wgc_base ) ); ?>"><?php esc_html_e( 'Last 30 days', 'webgram-core' ); ?></a>
	</p>
	<div class="wgc-cards">
		<?php foreach ( $args['cards'] as [ $wgc_label, $wgc_value, $wgc_event ] ) : ?>
			<div class="wgc-card">
				<span class="wgc-card__label"><?php echo esc_html( $wgc_label ); ?></span>
				<strong class="wgc-card__value"><?php echo esc_html( is_int( $wgc_value ) ? number_format_i18n( $wgc_value ) : (string) $wgc_value ); ?></strong>
				<?php if ( $wgc_event ) : ?>
					<?php $wgc_series = Reports::series( $args['events']->daily( $wgc_event, $args['days'] ), $args['days'], gmdate( 'Y-m-d' ) ); ?>
					<svg class="wgc-card__bars" viewBox="0 0 <?php echo (int) ( count( $wgc_series ) * 6 ); ?> 32" preserveAspectRatio="none" aria-hidden="true">
						<?php foreach ( $wgc_series as $wgc_i => $wgc_pt ) : ?>
							<rect x="<?php echo (int) ( $wgc_i * 6 ); ?>" y="<?php echo (int) ( 32 - max( 1, $wgc_pt['pct'] * 0.32 ) ); ?>" width="4" height="<?php echo (int) max( 1, $wgc_pt['pct'] * 0.32 ); ?>" rx="1"><title><?php echo esc_html( $wgc_pt['day'] . ': ' . $wgc_pt['n'] ); ?></title></rect>
						<?php endforeach; ?>
					</svg>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="wgc-tables">
		<?php foreach ( $args['tables'] as $wgc_event => $wgc_title ) : ?>
			<?php $wgc_rows = $args['events']->top( $wgc_event, 'reel_play' === $wgc_event ? 'reel' : 'product', $args['days'] ); ?>
			<div class="wgc-table">
				<h2><?php echo esc_html( $wgc_title ); ?></h2>
				<?php if ( ! $wgc_rows ) : ?>
					<p class="description"><?php esc_html_e( 'No data yet.', 'webgram-core' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<tbody>
							<?php foreach ( $wgc_rows as $wgc_row ) : ?>
								<?php $wgc_post = $wgc_row['object_id'] ? get_post( $wgc_row['object_id'] ) : null; ?>
								<tr>
									<td><?php echo $wgc_post ? '<a href="' . esc_url( (string) get_edit_post_link( $wgc_post ) ) . '">' . esc_html( $wgc_post->post_title ) . '</a>' : '#' . (int) $wgc_row['object_id']; ?></td>
									<td class="wgc-num"><?php echo esc_html( number_format_i18n( $wgc_row['n'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<h2><?php esc_html_e( 'All events', 'webgram-core' ); ?></h2>
	<table class="widefat striped" style="max-width:520px">
		<tbody>
			<?php arsort( $args['counts'] ); ?>
			<?php foreach ( $args['counts'] as $wgc_event => $wgc_n ) : ?>
				<tr><td><code><?php echo esc_html( $wgc_event ); ?></code></td><td class="wgc-num"><?php echo esc_html( number_format_i18n( $wgc_n ) ); ?></td></tr>
			<?php endforeach; ?>
			<?php if ( ! $args['counts'] ) : ?>
				<tr><td colspan="2" class="description"><?php esc_html_e( 'No events in this period.', 'webgram-core' ); ?></td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
