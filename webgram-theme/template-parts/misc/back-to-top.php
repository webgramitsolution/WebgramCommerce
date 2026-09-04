<?php
/**
 * Back to top button.
 *
 * @package Webgram
 */

defined( 'ABSPATH' ) || exit;
?>
<button class="wg-back-to-top" type="button" data-wg-component="back-to-top" data-offset="<?php echo (int) webgram_option( 'back_to_top_offset' ); ?>" aria-label="<?php esc_attr_e( 'Back to top', 'webgram' ); ?>" hidden><?php webgram_icon( 'arrow-up' ); ?></button>
