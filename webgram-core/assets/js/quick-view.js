/* Webgram Core quick view modal. */
(function () {
	'use strict';
	var core = window.WebgramCore;
	var modal = document.querySelector('[data-wgc-quick-view-modal]');
	if (!core || !modal) return;
	var content = modal.querySelector('[data-wgc-quick-view-content]');
	var lastFocus = null;

	function open() { lastFocus = document.activeElement; modal.hidden = false; document.body.classList.add('wgc-modal-open'); }
	function close() { modal.hidden = true; content.innerHTML = ''; document.body.classList.remove('wgc-modal-open'); if (lastFocus && lastFocus.focus) lastFocus.focus(); }

	function load(id) {
		content.innerHTML = '<div class="wgc-quick-view__loading">' + ((core.config.i18n && core.config.i18n.loading) || 'Loading') + '...</div>';
		open();
		core.ajax('quick_view', { product_id: id }).then(function (json) {
			content.innerHTML = json.data.html;
			var $ = window.jQuery;
			if ($) {
				var form = $(content).find('form.variations_form');
				if (form.length && $.fn.wc_variation_form) form.wc_variation_form();
			}
			document.dispatchEvent(new CustomEvent('wg:content-updated', { detail: { root: content } }));
			var first = content.querySelector('a, button, input, select');
			if (first) first.focus();
		}).catch(function (err) { content.innerHTML = '<p class="wgc-quick-view__error">' + err.message + '</p>'; });
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-quick-view]');
		if (btn) { e.preventDefault(); load(btn.dataset.wgcQuickView); return; }
		if (e.target.closest('[data-wgc-quick-view-close]')) { e.preventDefault(); close(); return; }
		var thumb = e.target.closest('[data-wgc-qv-thumb]');
		if (thumb && modal.contains(thumb)) {
			var main = modal.querySelector('[data-wgc-qv-main]');
			if (main) { main.src = thumb.dataset.wgcQvThumb; main.removeAttribute('srcset'); }
			modal.querySelectorAll('[data-wgc-qv-thumb]').forEach(function (t) { t.classList.toggle('is-active', t === thumb); });
		}
	});
	document.addEventListener('keydown', function (e) { if (modal.hidden) return; if (e.key === 'Escape') { close(); return; } core.trapFocus(modal, e); });
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', function () { if (!modal.hidden) setTimeout(close, 400); });
	}
})();
