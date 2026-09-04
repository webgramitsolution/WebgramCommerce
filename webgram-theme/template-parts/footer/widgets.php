<?php
/**
 * Footer widget columns.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_columns = max( 2, min( 4, (int) webgram_option( 'footer_columns' ) ) );
$webgram_active  = false;
for ( $webgram_i = 1; $webgram_i <= $webgram_columns; $webgram_i++ ) {
	if ( is_active_sidebar( 'footer-' . $webgram_i ) ) {
		$webgram_active = true;
		break;
	}
}
if ( ! $webgram_active ) {
	return;
}
?>
<div class="wg-footer__widgets">
	<div class="wg-container wg-footer__grid wg-footer__grid--<?php echo esc_attr( (string) $webgram_columns ); ?>">
		<?php for ( $webgram_i = 1; $webgram_i <= $webgram_columns; $webgram_i++ ) : ?>
			<div class="wg-footer__col">
				<?php dynamic_sidebar( 'footer-' . $webgram_i ); ?>
			</div>
		<?php endfor; ?>
	</div>
</div>
