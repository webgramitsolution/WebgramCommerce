<?php
/**
 * Quick view modal shell; content is injected by JS.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div id="wgc-quick-view" class="<?php echo esc_attr( Helpers::css_class( 'modal', 'wgc-modal--quick-view wg-modal--quick-view' ) ); ?>" data-wgc-quick-view-modal role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Quick view', 'webgram-core' ); ?>" hidden>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__backdrop' ) ); ?>" data-wgc-quick-view-close></div>
	<div class="<?php echo esc_attr( Helpers::css_class( 'modal__dialog' ) ); ?>">
		<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'modal__close' ) ); ?>" data-wgc-quick-view-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
		<div class="<?php echo esc_attr( Helpers::css_class( 'quick-view__content' ) ); ?>" data-wgc-quick-view-content></div>
	</div>
</div>
