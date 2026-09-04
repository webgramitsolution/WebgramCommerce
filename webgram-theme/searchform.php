<?php
/**
 * Site search form.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;

$webgram_id = wp_unique_id( 'wg-search-' );
?>
<form role="search" method="get" class="wg-search wg-search--inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="wg-sr-only" for="<?php echo esc_attr( $webgram_id ); ?>"><?php esc_html_e( 'Search', 'webgram' ); ?></label>
	<input id="<?php echo esc_attr( $webgram_id ); ?>" class="wg-search__input" type="search" name="s" placeholder="<?php esc_attr_e( 'Search', 'webgram' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
	<button class="wg-btn wg-btn--primary" type="submit"><?php esc_html_e( 'Search', 'webgram' ); ?></button>
</form>
