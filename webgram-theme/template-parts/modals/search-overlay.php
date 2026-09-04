<?php
/**
 * Full-width search overlay opened by the search toggle element and the bottom navbar.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wg-search-overlay" class="wg-search-overlay" data-wg-component="search-overlay" hidden>
	<div class="wg-container wg-search-overlay__inner">
		<?php webgram_part( 'header/search-form', [ 'id' => 'wg-search-overlay-input', 'style' => 'pill', 'min_width' => 0 ] ); ?>
		<button class="wg-icon-btn wg-icon-btn--no-label wg-search-overlay__close" type="button" data-wg-close="search-overlay"><?php webgram_icon( 'close' ); ?><span class="wg-sr-only"><?php esc_html_e( 'Close search', 'webgram' ); ?></span></button>
	</div>
</div>
