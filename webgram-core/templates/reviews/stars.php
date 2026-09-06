<?php
/**
 * Star row. $args: rating (float), size (int px), label (string, optional).
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_rating = (float) ( $args['rating'] ?? 0 );
$wgc_size   = (int) ( $args['size'] ?? 16 );
?>
<span class="<?php echo esc_attr( Helpers::css_class( 'stars' ) ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating */ __( 'Rated %s out of 5', 'webgram-core' ), number_format_i18n( $wgc_rating, 1 ) ) ); ?>">
	<?php for ( $wgc_i = 1; $wgc_i <= 5; $wgc_i++ ) : ?>
		<?php $wgc_fill = min( 1, max( 0, $wgc_rating - ( $wgc_i - 1 ) ) ); ?>
		<svg viewBox="0 0 24 24" width="<?php echo (int) $wgc_size; ?>" height="<?php echo (int) $wgc_size; ?>" aria-hidden="true" class="<?php echo esc_attr( Helpers::css_class( 'stars__star', $wgc_fill >= 1 ? 'is-full' : ( $wgc_fill > 0 ? 'is-half' : '' ) ) ); ?>"><defs><linearGradient id="wgc-star-<?php echo (int) $wgc_i; ?>-<?php echo esc_attr( md5( (string) $wgc_rating ) ); ?>"><stop offset="<?php echo esc_attr( (string) ( $wgc_fill * 100 ) ); ?>%" stop-color="currentColor"/><stop offset="<?php echo esc_attr( (string) ( $wgc_fill * 100 ) ); ?>%" stop-color="var(--wgc-star-empty, #e5e7eb)"/></linearGradient></defs><path fill="url(#wgc-star-<?php echo (int) $wgc_i; ?>-<?php echo esc_attr( md5( (string) $wgc_rating ) ); ?>)" d="M12 2.5l2.95 6.1 6.7.9-4.9 4.7 1.2 6.7L12 17.7l-5.95 3.2 1.2-6.7-4.9-4.7 6.7-.9z"/></svg>
	<?php endfor; ?>
</span>
