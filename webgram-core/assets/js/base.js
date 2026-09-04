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

	window.WebgramCore = { config: cfg, ajax: ajax, rest: rest, on: on, emit: emit };
})();
