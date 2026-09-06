<?php
/**
 * Full screen reel viewer shell (filled by reels.js): vertical swipe feed on mobile, centered 9:16 on desktop.
 *
 * @package Webgram\Core
 */

defined( 'ABSPATH' ) || exit;

use Webgram\Core\Support\Helpers;
?>
<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer' ) ); ?>" data-wgc-reel-viewer hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Reels', 'webgram-core' ); ?>">
	<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__backdrop' ) ); ?>" data-wgc-reel-close></div>
	<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__close' ) ); ?>" data-wgc-reel-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button>
	<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__nav', 'is-prev' ) ); ?>" data-wgc-reel-prev aria-label="<?php esc_attr_e( 'Previous reel', 'webgram-core' ); ?>">&lsaquo;</button>
	<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__nav', 'is-next' ) ); ?>" data-wgc-reel-next aria-label="<?php esc_attr_e( 'Next reel', 'webgram-core' ); ?>">&rsaquo;</button>
	<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__feed' ) ); ?>" data-wgc-reel-feed></div>
	<template data-wgc-reel-template>
		<section class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__item' ) ); ?>">
			<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__stage' ) ); ?>" data-stage></div>
			<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__side' ) ); ?>">
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__btn' ) ); ?>" data-mute aria-label="<?php esc_attr_e( 'Unmute', 'webgram-core' ); ?>" aria-pressed="false"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path class="wgc-reel__mute-on" d="M23 9l-6 6M17 9l6 6"/><path class="wgc-reel__mute-off" d="M15.54 8.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg></button>
				<button type="button" class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__btn' ) ); ?>" data-products aria-label="<?php esc_attr_e( 'Products in this reel', 'webgram-core' ); ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg><span data-products-count></span></button>
			</div>
			<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__caption' ) ); ?>" data-caption></div>
			<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__sheet' ) ); ?>" data-sheet hidden>
				<div class="<?php echo esc_attr( Helpers::css_class( 'reel-viewer__sheet-head' ) ); ?>"><strong><?php esc_html_e( 'Shop this reel', 'webgram-core' ); ?></strong><button type="button" data-sheet-close aria-label="<?php esc_attr_e( 'Close', 'webgram-core' ); ?>">&times;</button></div>
				<div data-sheet-list></div>
			</div>
		</section>
	</template>
</div>
