/* Webgram Core wishlist and compare: toggle buttons, header counts, compare bar, page rows, share copy. */
(function () {
	'use strict';

	var Core = window.WebgramCore;
	if (!Core) { return; }
	var cfg = Core.config || {};

	function toast(msg) {
		if (!msg) { return; }
		if (typeof Core.toast === 'function') { Core.toast(msg); return; }
		var el = document.querySelector('[data-wgc-toast]');
		if (!el) {
			el = document.createElement('div');
			el.className = 'wgc-toast wg-toast';
			el.setAttribute('data-wgc-toast', '');
			el.setAttribute('role', 'status');
			document.body.appendChild(el);
		}
		el.textContent = msg;
		el.classList.add('is-visible');
		clearTimeout(el._t);
		el._t = setTimeout(function () { el.classList.remove('is-visible'); }, 2400);
	}

	function applyFragments(fragments) {
		if (!fragments) { return; }
		Object.keys(fragments).forEach(function (selector) {
			document.querySelectorAll(selector).forEach(function (node) {
				var tpl = document.createElement('div');
				tpl.innerHTML = fragments[selector];
				if (tpl.firstElementChild) { node.replaceWith(tpl.firstElementChild); }
			});
		});
	}

	function setCount(list, count) {
		document.querySelectorAll('.wg-' + list + '-count').forEach(function (el) {
			el.textContent = count;
			el.setAttribute('data-count', count);
		});
	}

	function syncButtons(list, ids) {
		var i18n = (cfg[list] && cfg[list].i18n) || {};
		document.querySelectorAll('[data-wgc-' + list + ']').forEach(function (btn) {
			if (btn.getAttribute('data-op') === 'remove') { return; }
			var id = parseInt(btn.getAttribute('data-wgc-' + list), 10);
			var active = ids.indexOf(id) !== -1;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-pressed', active ? 'true' : 'false');
			var label = active ? i18n.remove : i18n.add;
			if (label) { btn.setAttribute('aria-label', label); btn.setAttribute('title', label); }
			var text = btn.querySelector('.wgc-' + list + '-btn__text');
			if (text) {
				text.textContent = active ? (list === 'wishlist' ? 'Saved' : 'Comparing') : (list === 'wishlist' ? 'Wishlist' : (i18n.compare || 'Compare'));
			}
		});
	}

	function toggle(list, id, op, btn) {
		var data = cfg[list] || {};
		if (list === 'wishlist' && data.requireLogin) {
			toast(data.i18n && data.i18n.login);
			if (data.loginUrl) { window.location.href = data.loginUrl; }
			return;
		}
		if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
		Core.ajax(list + '_toggle', { product_id: id, op: op || '' }).then(function (res) {
			var d = res.data || {};
			if (d.login && data.loginUrl) { window.location.href = data.loginUrl; return; }
			cfg[list] = Object.assign({}, data, { ids: d.ids || [] });
			syncButtons(list, d.ids || []);
			setCount(list, d.count || 0);
			applyFragments(d.fragments);
			if (op === 'remove' || (!d.added && !d.full)) {
				var row = document.querySelector('[data-wgc-' + list + '-row="' + id + '"], [data-wgc-' + list + '-col="' + id + '"]');
				if (row && row.closest('[data-wgc-' + list + '-page]')) {
					if (row.tagName === 'TH') { window.location.reload(); return; }
					row.remove();
					var page = document.querySelector('[data-wgc-' + list + '-page]');
					if (page && !page.querySelector('tbody tr')) { window.location.reload(); }
				}
			}
			toast(d.message);
			Core.emit(list + ':changed', d);
		}).catch(function (err) {
			toast(err.message);
		}).then(function () {
			if (btn) { btn.classList.remove('is-loading'); btn.disabled = false; }
		});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-wishlist], [data-wgc-compare]');
		if (btn) {
			e.preventDefault();
			var list = btn.hasAttribute('data-wgc-wishlist') ? 'wishlist' : 'compare';
			toggle(list, parseInt(btn.getAttribute('data-wgc-' + list), 10), btn.getAttribute('data-op') || '', btn);
			return;
		}
		var copy = e.target.closest('[data-wgc-copy]');
		if (copy && copy.closest('[data-wgc-wishlist-page]')) {
			e.preventDefault();
			var value = copy.getAttribute('data-wgc-copy');
			var done = function () { toast(copy.getAttribute('data-copied') || 'Copied'); };
			if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(value).then(done, done); } else { window.prompt('', value); done(); }
			return;
		}
		var link = e.target.closest('[data-wgc-list-link]');
		if (link && link.getAttribute('href') === '#') {
			e.preventDefault();
			toast(cfg.i18n && cfg.i18n.error);
		}
	});

	var highlight = document.querySelector('[data-wgc-compare-highlight]');
	if (highlight) {
		highlight.addEventListener('change', function () {
			var page = highlight.closest('[data-wgc-compare-page]');
			if (page) { page.classList.toggle('is-highlight', highlight.checked); }
		});
	}

	['wishlist', 'compare'].forEach(function (list) {
		if (cfg[list] && cfg[list].ids) { syncButtons(list, cfg[list].ids); }
	});
	document.addEventListener('wg:content-updated', function () {
		['wishlist', 'compare'].forEach(function (list) {
			if (cfg[list] && cfg[list].ids) { syncButtons(list, cfg[list].ids); }
		});
	});
})();
