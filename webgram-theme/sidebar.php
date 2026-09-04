<?php
/**
 * Blog sidebar, rendered only for sidebar layouts.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

if ( ! in_array( webgram_layout(), [ 'sidebar-left', 'sidebar-right' ], true ) || ! is_active_sidebar( 'sidebar-blog' ) ) {
	return;
}
?>
<aside id="secondary" class="wg-sidebar wg-sidebar--blog">
	<?php dynamic_sidebar( 'sidebar-blog' ); ?>
</aside>
