<?php
/**
 * Back to top button.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<button class="wg-back-to-top wg-back-to-top--<?php echo esc_attr( (string) webgram_option( 'back_to_top_position' ) ); ?> <?php echo esc_attr( implode( ' ', array_map( static fn( $d ) => 'wg-show-' . sanitize_html_class( (string) $d ), (array) webgram_option( 'back_to_top_devices' ) ) ) ); ?>" type="button" data-wg-component="back-to-top" data-offset="<?php echo (int) webgram_option( 'back_to_top_offset' ); ?>" aria-label="<?php esc_attr_e( 'Back to top', 'webgram' ); ?>" hidden><?php webgram_icon( 'arrow-up' ); ?></button>
