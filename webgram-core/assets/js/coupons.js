/* Webgram Core coupons: copy code with toast. */
(function () {
	'use strict';
	function toast(text) {
		var el = document.createElement('div');
		el.className = 'wgc-toast wg-toast';
		el.textContent = text;
		document.body.appendChild(el);
		requestAnimationFrame(function () { el.classList.add('is-visible'); });
		setTimeout(function () { el.classList.remove('is-visible'); setTimeout(function () { el.remove(); }, 300); }, 1800);
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-copy]');
		if (!btn) return;
		var code = btn.dataset.wgcCopy;
		var done = function () { toast(btn.dataset.copied || 'Code copied'); btn.classList.add('is-copied'); setTimeout(function () { btn.classList.remove('is-copied'); }, 1500); if (window.WebgramCore) { window.WebgramCore.track('coupon_copy', 'coupon', 0, { code: String(code || '').slice(0, 40) }); } };
		if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done).catch(done);
		else { var ta = document.createElement('textarea'); ta.value = code; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch (err) {} ta.remove(); done(); }
	});
	if (window.WebgramCore) window.WebgramCore.toast = toast;

	/* Apply from the product box: applied at once with items in the cart, remembered for the next add otherwise. */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-apply]');
		if (!btn || !window.WebgramCore) return;
		var box = btn.closest('[data-wgc-coupon]');
		var msg = box ? box.querySelector('[data-wgc-coupon-message]') : null;
		btn.disabled = true;
		box && box.classList.remove('is-error', 'is-applied', 'is-pending');
		window.WebgramCore.ajax('coupon_apply', { code: btn.dataset.wgcApply }).then(function (res) {
			var d = res.data || {};
			if (box) { box.classList.add(d.state === 'pending' ? 'is-pending' : 'is-applied'); }
			if (msg) { msg.textContent = d.message || ''; }
			if (d.state === 'applied') { btn.textContent = btn.dataset.applied || 'Applied'; }
			toast(d.message || '');
			if (d.fragments && window.jQuery) { window.jQuery(document.body).trigger('wc_fragment_refresh'); }
			document.dispatchEvent(new CustomEvent('wgc:coupon-applied', { detail: d }));
		}).catch(function (err) {
			box && box.classList.add('is-error');
			if (msg) { msg.textContent = err.message || ''; }
			toast(err.message || '');
		}).then(function () { btn.disabled = false; });
	});

	/* Cart page: WooCommerce replaces the cart markup after quantity changes, so refresh the offer progress bar too. */
	if (window.jQuery && window.WebgramCore) {
		window.jQuery(document.body).on('updated_wc_div updated_cart_totals', function () {
			var bars = document.querySelectorAll('[data-wgc-progress]');
			if (!bars.length) return;
			window.WebgramCore.ajax('coupon_progress', {}).then(function (res) {
				var html = res.data && res.data.html;
				if (!html) return;
				bars.forEach(function (bar) { var tpl = document.createElement('div'); tpl.innerHTML = html; if (tpl.firstElementChild) bar.replaceWith(tpl.firstElementChild); });
			}).catch(function () {});
		});
	}
})();
