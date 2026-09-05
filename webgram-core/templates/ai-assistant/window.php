<?php
/**
 * Chat window shell (filled by assistant.js). $args: inline (bool), name, avatar, color, consent.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;

$wgc_inline = ! empty( $args['inline'] );
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'assistant', $wgc_inline ? 'wgc-assistant--inline' : 'wgc-assistant--floating' ) ); ?>" data-wgc-assistant<?php echo $wgc_inline ? ' data-inline="1"' : ' hidden'; ?> role="<?php echo $wgc_inline ? 'region' : 'dialog'; ?>" aria-label="<?php echo esc_attr( $args['name'] ); ?>"<?php echo $args['color'] ? ' style="--wgc-assistant-color:' . esc_attr( $args['color'] ) . '"' : ''; ?>>
	<div class="<?php echo esc_attr( Helpers::css_class( 'assistant__head' ) ); ?>">
		<span class="<?php echo esc_attr( Helpers::css_class( 'assistant__avatar' ) ); ?>">
			<?php if ( $args['avatar'] ) : ?>
				<img src="<?php echo esc_url( $args['avatar'] ); ?>" alt="" width="40" height="40">
			<?php else : ?>
				<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="12" rx="3"/><path d="M12 8V4M8 4h8M9 14h.01M15 14h.01"/></svg>
			<?php endif; ?>
		</span>
		<span class="<?php echo esc_attr( Helpers::css_class( 'assistant__title' ) ); ?>">
			<strong><?php echo esc_html( $args['name'] ); ?></strong>
			<span class="<?php echo esc_attr( Helpers::css_class( 'assistant__status' ) ); ?>"><i aria-hidden="true"></i><?php esc_html_e( 'Online', 'webgram-core' ); ?></span>
		</span>
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'assistant__btn' ) ); ?>" data-wgc-assistant-mute aria-pressed="false" aria-label="<?php esc_attr_e( 'Mute notification sound', 'webgram-core' ); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg></button>
		<?php if ( ! $wgc_inline ) : ?>
			<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'assistant__btn' ) ); ?>" data-wgc-assistant-close aria-label="<?php esc_attr_e( 'Close chat', 'webgram-core' ); ?>">&times;</button>
		<?php endif; ?>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'assistant__consent' ) ); ?>" data-wgc-assistant-consent <?php echo $args['consent'] ? '' : 'hidden'; ?>>
		<p><?php echo esc_html( $args['consent'] ); ?></p>
		<button type="button" class="wg-btn wg-btn--primary wg-btn--sm" data-wgc-assistant-agree><?php esc_html_e( 'Start chatting', 'webgram-core' ); ?></button>
	</div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'assistant__messages' ) ); ?>" data-wgc-assistant-messages aria-live="polite"></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'assistant__suggestions' ) ); ?>" data-wgc-assistant-suggestions></div>
	<form class="<?php echo esc_attr( Helpers::css_class( 'assistant__form' ) ); ?>" data-wgc-assistant-form>
		<label class="wg-sr-only" for="wgc-assistant-input-<?php echo $wgc_inline ? 'inline' : 'float'; ?>"><?php esc_html_e( 'Your message', 'webgram-core' ); ?></label>
		<input type="text" id="wgc-assistant-input-<?php echo $wgc_inline ? 'inline' : 'float'; ?>" class="<?php echo esc_attr( Helpers::css_class( 'assistant__input' ) ); ?>" maxlength="1000" autocomplete="off" placeholder="<?php esc_attr_e( 'Ask about products, offers or your order', 'webgram-core' ); ?>" data-wgc-assistant-input>
		<?php do_action( 'webgram_core/assistant/input', 'wgc-assistant-input-' . ( $wgc_inline ? 'inline' : 'float' ) ); ?>
		<button type="submit" class="<?php echo esc_attr( Helpers::css_class( 'assistant__send' ) ); ?>" aria-label="<?php esc_attr_e( 'Send', 'webgram-core' ); ?>"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></button>
	</form>
</div>
