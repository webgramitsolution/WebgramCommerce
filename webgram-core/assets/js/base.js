/* Webgram Core shared front-end helpers. Exposed as window.WebgramCore. No jQuery dependency. */
(function () {
	'use strict';

	var cfg = window.webgramCore || {};

	function wcAjaxUrl(action) {
		if (cfg.wcAjax) {
			return cfg.wcAjax.replace('%%endpoint%%', 'webgram_' + action);
		}
		return (cfg.ajaxUrl || '/wp-admin/admin-ajax.php') + '?action=webgram_' + action;
	}

	function ajax(action, data) {
		var body = new FormData();
		body.append('nonce', cfg.nonce || '');
		Object.keys(data || {}).forEach(function (k) {
			var v = data[k];
			if (Array.isArray(v)) {
				v.forEach(function (item) { body.append(k + '[]', item); });
			} else if (v !== undefined && v !== null) {
				body.append(k, v);
			}
		});
		return fetch(wcAjaxUrl(action), { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (json) {
				if (!json || json.success === false) {
					throw new Error((json && json.message) || cfg.i18n && cfg.i18n.error || 'Request failed');
				}
				return json;
			});
	}

	function rest(path, options) {
		options = options || {};
		var headers = Object.assign({ 'X-WP-Nonce': cfg.restNonce || '' }, options.headers || {});
		if (options.body && typeof options.body !== 'string' && !(options.body instanceof FormData)) {
			options.body = JSON.stringify(options.body);
			headers['Content-Type'] = 'application/json';
		}
		return fetch((cfg.restUrl || '/wp-json/webgram/v1/') + path.replace(/^\//, ''), Object.assign({ credentials: 'same-origin' }, options, { headers: headers }))
			.then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw new Error((j && j.message) || 'Request failed'); } return j; }); });
	}

	var listeners = {};
	function on(event, fn) { (listeners[event] = listeners[event] || []).push(fn); }
	function emit(event, detail) {
		(listeners[event] || []).forEach(function (fn) { fn(detail); });
		document.dispatchEvent(new CustomEvent('wg:' + event, { detail: detail }));
	}

	/* Analytics: track(event, objectType, objectId, meta) queues events; the Analytics module flushes them in batches. */
	var queue = [];
	var flushTimer = null;
	function flush(useBeacon) {
		if (!queue.length || !cfg.analytics || !cfg.analytics.enabled) { queue = []; return; }
		var batch = queue.splice(0, 20);
		var url = (cfg.restUrl || '/wp-json/webgram/v1/') + 'events';
		var body = JSON.stringify({ events: batch, _wpnonce: cfg.restNonce || '' });
		if (useBeacon && navigator.sendBeacon) { navigator.sendBeacon(url, new Blob([body], { type: 'application/json' })); return; }
		fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.restNonce || '' }, body: body, keepalive: true }).catch(function () {});
	}
	function track(event, objectType, objectId, meta) {
		if (!event || !cfg.analytics || !cfg.analytics.enabled) { return; }
		if (cfg.analytics.sample && Math.random() * 100 > cfg.analytics.sample) { return; }
		queue.push({ event: String(event).slice(0, 48), object_type: objectType ? String(objectType).slice(0, 24) : '', object_id: parseInt(objectId, 10) || 0, meta: meta && typeof meta === 'object' ? meta : {} });
		clearTimeout(flushTimer);
		flushTimer = setTimeout(function () { flush(false); }, 1500);
	}
	on('track', function (d) { if (d && d.event) { track(d.event, d.object_type, d.object_id, d.meta || Object.keys(d).reduce(function (m, k) { if (['event', 'object_type', 'object_id'].indexOf(k) === -1) { m[k] = d[k]; } return m; }, {})); } });
	window.addEventListener('pagehide', function () { flush(true); });

	/* Keeps Tab and Shift+Tab inside an open dialog. Call from a keydown handler while the dialog is visible. */
	function trapFocus(container, e) {
		if (!container || e.key !== 'Tab') { return; }
		var items = Array.prototype.filter.call(container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'), function (el) { return el.offsetParent !== null || el === document.activeElement; });
		if (!items.length) { e.preventDefault(); return; }
		var first = items[0], last = items[items.length - 1];
		if (e.shiftKey && (document.activeElement === first || !container.contains(document.activeElement))) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && (document.activeElement === last || !container.contains(document.activeElement))) { e.preventDefault(); first.focus(); }
	}
	window.WebgramCore = { config: cfg, ajax: ajax, rest: rest, on: on, emit: emit, track: track, trapFocus: trapFocus };
})();
