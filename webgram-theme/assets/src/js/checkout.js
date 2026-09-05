/* Webgram Theme checkout bundle: coupon apply inside the summary, login/register toggle, password visibility. */
import { qs, qsa, register } from './modules/dom.js';

const i18n = (window.webgramData && window.webgramData.i18n) || {};

// Coupon inside the order summary: uses WooCommerce's own apply_coupon endpoint and refreshes the checkout.
document.addEventListener('click', (e) => {
	const btn = e.target.closest('[data-wg-apply-coupon]');
	if (!btn) return;
	const wrap = btn.closest('[data-wg-checkout-coupon]');
	const input = qs('input', wrap);
	const code = (input.value || '').trim();
	const params = window.wc_checkout_params;
	if (!code || !params || !window.jQuery) return;
	btn.disabled = true;
	const url = params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon');
	const body = new FormData();
	body.append('security', params.apply_coupon_nonce);
	body.append('coupon_code', code);
	fetch(url, { method: 'POST', body, credentials: 'same-origin' })
		.then((r) => r.text())
		.then((html) => {
			const $ = window.jQuery;
			$('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
			if (html) $(wrap).before(html);
			$(document.body).trigger('update_checkout', { update_shipping_method: false });
			input.value = '';
		})
		.then(() => { btn.disabled = false; });
});
document.addEventListener('keydown', (e) => {
	if (e.key === 'Enter' && e.target.closest('[data-wg-checkout-coupon] input')) { e.preventDefault(); qs('[data-wg-apply-coupon]', e.target.closest('[data-wg-checkout-coupon]')).click(); }
});

// Login / Signup segmented toggle, remembered for the session.
register('login', (wrap) => {
	const setTab = (tab) => {
		qsa('[data-wg-login-tab]', wrap).forEach((b) => { const on = b.dataset.wgLoginTab === tab; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
		qsa('[data-wg-login-pane]', wrap).forEach((p) => p.classList.toggle('is-active', p.dataset.wgLoginPane === tab));
		try { sessionStorage.setItem('wg_login_tab', tab); } catch (err) {}
	};
	wrap.addEventListener('click', (e) => { const b = e.target.closest('[data-wg-login-tab]'); if (b) setTab(b.dataset.wgLoginTab); });
	const hasError = qs('.woocommerce-error');
	if (!hasError && wrap.dataset.tab === 'login') { try { const saved = sessionStorage.getItem('wg_login_tab'); if (saved && qs(`[data-wg-login-pane="${saved}"]`, wrap)) setTab(saved); } catch (err) {} }
	if (hasError && qs('.woocommerce-error li[data-id^="webgram"], .woocommerce-error li[data-id="reg_email"]')) setTab('register');
});

// Password visibility toggle.
document.addEventListener('click', (e) => {
	const eye = e.target.closest('[data-wg-eye]');
	if (!eye) return;
	const input = qs('input', eye.parentNode);
	if (!input) return;
	const show = input.type === 'password';
	input.type = show ? 'text' : 'password';
	eye.setAttribute('aria-label', show ? (i18n.hidePassword || 'Hide password') : (i18n.showPassword || 'Show password'));
	eye.classList.toggle('is-on', show);
});
