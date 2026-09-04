<?php
/**
 * Empty state for archives and search.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wg-empty">
	<h1 class="wg-empty__title"><?php esc_html_e( 'Nothing found', 'webgram' ); ?></h1>
	<?php if ( is_search() ) : ?>
		<p class="wg-empty__text"><?php esc_html_e( 'No results matched your search. Try different words or fewer filters.', 'webgram' ); ?></p>
	<?php else : ?>
		<p class="wg-empty__text"><?php esc_html_e( 'There is nothing here yet.', 'webgram' ); ?></p>
	<?php endif; ?>
	<?php get_search_form(); ?>
</div>
