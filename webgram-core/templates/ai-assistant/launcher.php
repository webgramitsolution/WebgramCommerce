<?php
/**
 * Floating chat launcher. $args: position, color, name.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'assistant-launcher', 'wgc-assistant-launcher--' . ( 'left' === $args['position'] ? 'left' : 'right' ) ) ); ?>" data-wgc-assistant-open<?php echo $args['color'] ? ' style="--wgc-assistant-color:' . esc_attr( $args['color'] ) . '"' : ''; ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: assistant name */ __( 'Chat with %s', 'webgram-core' ), $args['name'] ?: __( 'our assistant', 'webgram-core' ) ) ); ?>" aria-haspopup="dialog">
	<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><path d="M8 12h.01M12 12h.01M16 12h.01"/></svg>
	<span class="<?php echo esc_attr( Helpers::css_class( 'assistant-launcher__dot' ) ); ?>" aria-hidden="true"></span>
</button>
