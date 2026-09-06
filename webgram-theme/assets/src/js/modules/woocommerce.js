import { emit, qsa } from './dom.js';

/**
 * The only place theme JS touches jQuery: WooCommerce fires its cart events on the jQuery event bus.
 * Re-broadcast them as native events so the rest of the theme and Core stay jQuery-free.
 */
export function bridgeWooEvents() {
	if (!window.jQuery) return;
	const $ = window.jQuery;
	['added_to_cart', 'removed_from_cart', 'wc_fragments_refreshed', 'updated_wc_div', 'updated_cart_totals', 'wc_cart_emptied', 'applied_coupon', 'removed_coupon'].forEach((name) => {
		$(document.body).on(name, (e, ...args) => emit(name, args));
	});
	// Variation form events are bound on the form itself.
	$(document).on('found_variation reset_data', 'form.variations_form', (e, ...args) => emit(e.type, args));
	$(document.body).on('wc_fragments_refreshed added_to_cart removed_from_cart', () => {
		const count = qsa('.wg-cart-count')[0];
		if (count) count.dataset.count = count.textContent.trim();
	});
}
