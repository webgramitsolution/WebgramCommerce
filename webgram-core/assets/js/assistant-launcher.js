/* Webgram Core assistant launcher (tiny): loads the chat bundle on first click, then opens the window. */
(function () {
	'use strict';
	var Core = window.WebgramCore;
	if (!Core) { return; }
	var cfg = (Core.config && Core.config.assistant) || {};
	var loading = null;

	function load() {
		if (window.WebgramAssistant) { return Promise.resolve(); }
		if (loading) { return loading; }
		loading = new Promise(function (resolve, reject) {
			var s = document.createElement('script');
			s.src = cfg.bundle;
			s.async = true;
			s.onload = resolve;
			s.onerror = reject;
			document.head.appendChild(s);
		});
		return loading;
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-assistant-open]');
		if (!btn) { return; }
		e.preventDefault();
		btn.classList.add('is-loading');
		load().then(function () {
			btn.classList.remove('is-loading');
			if (window.WebgramAssistant) { window.WebgramAssistant.open(); }
		}).catch(function () { btn.classList.remove('is-loading'); });
	});

	if (document.querySelector('[data-wgc-assistant][data-inline]')) { load(); }
	window.WebgramAssistantLoad = load;
})();
