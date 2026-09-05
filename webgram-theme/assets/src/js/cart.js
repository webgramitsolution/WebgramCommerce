/* Webgram Theme cart bundle: slide cart drawer (open after add to cart, quantity and remove via wc-ajax
 * webgram_cart_update, coupon apply), toast fallback. Loaded on every page when WooCommerce is active. */
import { qs, qsa, register, emit } from './modules/dom.js';
import { open, close } from './modules/drawer.js';

const cfg = window.webgramData || {};
const cartCfg = cfg.cart || {};
const i18n = cfg.i18n || {};

function toast(text, link) {
	const el = document.createElement('div');
	el.className = 'wg-toast';
	el.textContent = text;
	if (link) { const a = document.createElement('a'); a.href = link.href; a.textContent = link.text; a.className = 'wg-toast__link'; el.appendChild(a); }
	document.body.appendChild(el);
	requestAnimationFrame(() => el.classList.add('is-visible'));
	setTimeout(() => { el.classList.remove('is-visible'); setTimeout(() => el.remove(), 300); }, 2600);
}
if (window.WebgramCore && !window.WebgramCore.toast) window.WebgramCore.toast = toast;

function applyFragments(fragments) {
	if (!fragments) return;
	Object.keys(fragments).forEach((selector) => {
		qsa(selector).forEach((el) => {
			const tpl = document.createElement('template');
			tpl.innerHTML = fragments[selector].trim();
			const next = tpl.content.firstElementChild;
			if (next) el.replaceWith(next);
		});
	});
	const drawer = qs('#wg-slide-cart');
	if (drawer) emit('content-updated', { root: drawer });
	qsa('.wg-cart-count').forEach((c) => { c.dataset.count = c.textContent.trim(); });
}

function endpoint() {
	if (cfg.wcAjax) return cfg.wcAjax.replace('%%endpoint%%', 'webgram_cart_update');
	return `${cfg.ajaxUrl || '/wp-admin/admin-ajax.php'}?action=webgram_cart_update`;
}

let pending = null;
function update(key, qty) {
	const drawer = qs('#wg-slide-cart');
	if (drawer) drawer.classList.add('is-loading');
	const body = new FormData();
	body.append('nonce', cfg.nonce || '');
	body.append('key', key);
	body.append('qty', String(qty));
	pending = fetch(endpoint(), { method: 'POST', body, credentials: 'same-origin' })
		.then((r) => r.json())
		.then((json) => {
			if (!json || !json.success) { toast((json && json.message) || i18n.error || 'Error'); return; }
			applyFragments(json.fragments);
			if (json.message) toast(json.message);
			if (window.jQuery) window.jQuery(document.body).trigger('wc_fragments_refreshed');
			emit('cart-updated', json);
		})
		.catch(() => toast(i18n.error || 'Error'))
		.then(() => { const d = qs('#wg-slide-cart'); if (d) d.classList.remove('is-loading'); });
	return pending;
}

register('slide-cart', () => {});

document.addEventListener('click', (e) => {
	const item = e.target.closest('.wg-cart__item');
	if (!item) return;
	const key = item.dataset.key;
	const input = qs('[data-wg-cart-input]', item);
	if (e.target.closest('[data-wg-cart-remove]')) { e.preventDefault(); item.classList.add('is-removing'); update(key, 0); return; }
	if (e.target.closest('[data-wg-cart-plus]') && input) { e.preventDefault(); const max = parseFloat(input.max) || Infinity; input.value = Math.min(max, (parseFloat(input.value) || 0) + 1); update(key, input.value); return; }
	if (e.target.closest('[data-wg-cart-minus]') && input) { e.preventDefault(); input.value = Math.max(0, (parseFloat(input.value) || 0) - 1); update(key, input.value); }
});
document.addEventListener('change', (e) => {
	const input = e.target.closest('[data-wg-cart-input]');
	if (!input) return;
	const item = input.closest('.wg-cart__item');
	update(item.dataset.key, Math.max(0, parseFloat(input.value) || 0));
});
document.addEventListener('submit', (e) => {
	const form = e.target.closest('[data-wg-cart-coupon]');
	if (!form) return;
	e.preventDefault();
	const field = qs('[name="coupon_code"]', form);
	const code = (field.value || '').trim();
	if (!code) return;
	if (!cartCfg.url) { window.location.href = `?coupon_code=${encodeURIComponent(code)}`; return; }
	// WooCommerce has no public AJAX for coupons outside the cart page: post to the cart page, read its notices, then refresh fragments.
	const body = new FormData();
	body.append('coupon_code', code);
	body.append('apply_coupon', '1');
	form.classList.add('is-loading');
	fetch(cartCfg.url, { method: 'POST', body, credentials: 'same-origin' })
		.then((r) => r.text())
		.then((html) => {
			const doc = new DOMParser().parseFromString(html, 'text/html');
			const error = qs('.woocommerce-error li, .woocommerce-error, .wc-block-components-notice-banner.is-error', doc);
			const ok = qs('.woocommerce-message, .wc-block-components-notice-banner.is-success', doc);
			const text = (error || ok) ? (error || ok).textContent.trim().replace(/\s+/g, ' ') : (i18n.applied || 'Coupon applied');
			form.classList.toggle('has-error', !!error);
			field.setAttribute('aria-invalid', error ? 'true' : 'false');
			toast(text);
			if (!error) { field.value = ''; }
			if (window.jQuery) { window.jQuery(document.body).trigger('wc_fragment_refresh'); } else if (!error) { window.location.reload(); }
		})
		.catch(() => toast(i18n.error || 'Error'))
		.then(() => form.classList.remove('is-loading'));
});

// Cart page: quantity steppers and automatic update through WooCommerce's own cart script (no extra endpoint).
let cartPageTimer = null;
function requestCartPageUpdate() {
	clearTimeout(cartPageTimer);
	cartPageTimer = setTimeout(() => {
		const btn = qs('.woocommerce-cart-form [name="update_cart"]');
		if (!btn) return;
		btn.disabled = false;
		if (window.jQuery) { window.jQuery(btn).trigger('click'); } else { btn.click(); }
	}, 500);
}
document.addEventListener('click', (e) => {
	const btn = e.target.closest('.woocommerce-cart-form .wg-qty__btn');
	if (!btn) return;
	const input = qs('input.qty', btn.closest('.wg-qty'));
	if (!input) return;
	const step = parseFloat(input.step) || 1;
	const min = parseFloat(input.min) || 0;
	const max = parseFloat(input.max) || Infinity;
	const dir = btn.classList.contains('wg-qty__btn--plus') ? 1 : -1;
	input.value = Math.min(max, Math.max(min, (parseFloat(input.value) || 0) + dir * step));
	input.dispatchEvent(new Event('change', { bubbles: true }));
});
document.addEventListener('change', (e) => {
	if (e.target.matches('.woocommerce-cart-form input.qty')) requestCartPageUpdate();
});

// Open the drawer after add to cart (or toast), based on the theme setting.
document.addEventListener('wg:added_to_cart', (e) => {
	const args = e.detail || [];
	const fragments = args[0];
	if (fragments) applyFragments(fragments);
	const drawer = qs('#wg-slide-cart');
	if (cartCfg.afterAdd === 'drawer' && drawer) { open(drawer); return; }
	if (cartCfg.afterAdd === 'toast') toast(i18n.addedToCart || 'Added to cart', cartCfg.url ? { href: cartCfg.url, text: i18n.viewCart || 'View cart' } : null);
});
document.addEventListener('wg:wc_fragments_refreshed', () => { const d = qs('#wg-slide-cart'); if (d) emit('content-updated', { root: d }); });
document.addEventListener('click', (e) => {
	// Header cart icon: prevent navigation while the drawer exists.
	const trigger = e.target.closest('[data-wg-toggle="slide-cart"]');
	if (!trigger || !qs('#wg-slide-cart')) return;
	e.preventDefault();
});
export { close };
