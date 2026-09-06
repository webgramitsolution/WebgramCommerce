<?php
/**
 * Contact cards: Call, Buy on chat, Ask for bulk quote. $args: values, chat_url, tel, product_id.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_v = $args['values'];
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'contact-cards' ) ); ?>">
	<?php if ( $wgc_v['show_call'] ) : ?>
		<a class="<?php echo esc_attr( Helpers::css_class( 'contact-card' ) ); ?>" href="<?php echo esc_attr( $args['tel'] ); ?>">
			<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.81.37 1.6.72 2.34a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.74-1.29a2 2 0 0 1 2.11-.45c.74.35 1.53.6 2.34.72A2 2 0 0 1 22 16.92z"/></svg>
			<span><small><?php echo esc_html( $wgc_v['call_label'] ); ?></small><strong><?php echo esc_html( $wgc_v['phone'] ); ?></strong></span>
		</a>
	<?php endif; ?>
	<?php if ( $wgc_v['show_chat'] && $args['chat_url'] ) : ?>
		<a class="<?php echo esc_attr( Helpers::css_class( 'contact-card' ) ); ?>" href="<?php echo esc_url( $args['chat_url'] ); ?>" target="_blank" rel="noopener">
			<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
			<span><small><?php esc_html_e( 'Chat with us', 'webgram-core' ); ?></small><strong><?php echo esc_html( $wgc_v['chat_label'] ); ?></strong></span>
		</a>
	<?php endif; ?>
	<?php if ( $wgc_v['show_bulk'] ) : ?>
		<?php do_action( 'webgram/product/bulk_inquiry_modal' ); ?>
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'contact-card' ) ); ?>" data-wgc-bulk-open data-product-id="<?php echo (int) $args['product_id']; ?>">
			<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
			<span><small><?php esc_html_e( 'Buying in bulk?', 'webgram-core' ); ?></small><strong><?php echo esc_html( $wgc_v['bulk_label'] ); ?></strong></span>
		</button>
	<?php endif; ?>
</div>
