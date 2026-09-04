<?php
/**
 * Numeric pagination.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

the_posts_pagination(
	[
		'class'     => 'wg-pagination',
		'mid_size'  => 1,
		'prev_text' => '<span class="wg-sr-only">' . esc_html__( 'Previous page', 'webgram' ) . '</span>' . webgram_icon( 'chevron-left', '', false ),
		'next_text' => '<span class="wg-sr-only">' . esc_html__( 'Next page', 'webgram' ) . '</span>' . webgram_icon( 'chevron-right', '', false ),
	]
);
