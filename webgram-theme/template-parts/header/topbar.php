<?php
/**
 * Announcement bar. Phase 1 turns this into a scrolling multi-message bar driven by the header builder.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_text = (string) webgram_option( 'topbar_text' );
if ( '' === $webgram_text ) {
	return;
}
?>
<div class="wg-topbar" data-wg-component="topbar">
	<div class="wg-container wg-topbar__inner">
		<p class="wg-topbar__text"><?php echo wp_kses_post( $webgram_text ); ?></p>
	</div>
</div>
