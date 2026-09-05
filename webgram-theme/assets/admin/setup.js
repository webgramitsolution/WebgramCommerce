/* Webgram setup wizard: runs the chosen steps one by one through admin-ajax and shows progress. */
(function () {
	'use strict';
	var root = document.querySelector('[data-wg-setup]');
	var cfg = window.webgramSetup;
	if (!root || !cfg) return;
	var start = root.querySelector('[data-wg-setup-start]');
	var log = root.querySelector('[data-wg-setup-log]');
	var finish = root.querySelector('[data-wg-setup-finish]');
	var demoSteps = ['settings', 'images', 'products', 'posts', 'core', 'pages', 'menus', 'widgets'];

	function choice(name) { var el = root.querySelector('[data-wg-setup-choice="' + name + '"]'); return !!(el && el.checked); }
	function plan() {
		var steps = ['woocommerce', 'core'];
		if (choice('elementor')) steps.push('elementor');
		if (choice('child')) steps.push('child');
		if (choice('demo')) demoSteps.forEach(function (s) { steps.push('demo:' + s); });
		return steps;
	}
	function line(text, cls) {
		var p = document.createElement('p');
		p.className = 'wg-setup__line' + (cls ? ' is-' + cls : '');
		p.textContent = text;
		log.appendChild(p);
		log.scrollTop = log.scrollHeight;
	}
	function setStatus(step, text) {
		var key = step.indexOf('demo:') === 0 ? 'demo' : step;
		var cell = root.querySelector('[data-wg-setup-row="' + key + '"] [data-wg-setup-status]');
		if (cell) cell.textContent = text;
	}
	function run(step, last) {
		var body = new FormData();
		body.append('action', cfg.action);
		body.append('nonce', cfg.nonce);
		body.append('step', step);
		if (last) body.append('last', '1');
		setStatus(step, cfg.i18n.running + '...');
		return fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (json) {
				var d = (json && json.data) || {};
				if (!json || !json.success) { throw new Error(d.message || cfg.i18n.failed); }
				setStatus(step, d.state === 'skipped' ? cfg.i18n.skipped : cfg.i18n.done);
				line(d.message || step, d.state === 'skipped' ? 'skipped' : 'done');
			});
	}
	start.addEventListener('click', function () {
		var steps = plan();
		var i = 0;
		start.disabled = true;
		log.hidden = false;
		log.innerHTML = '';
		root.querySelectorAll('[data-wg-setup-choice]').forEach(function (c) { c.disabled = true; });
		(function next() {
			if (i >= steps.length) { line(cfg.i18n.finish, 'done'); finish.hidden = false; return; }
			var step = steps[i];
			i += 1;
			run(step, i === steps.length).then(next).catch(function (err) {
				setStatus(step, cfg.i18n.failed);
				line(err.message || cfg.i18n.failed, 'failed');
				line(cfg.i18n.stopped, 'failed');
				start.disabled = false;
				root.querySelectorAll('[data-wg-setup-choice]').forEach(function (c) { c.disabled = false; });
			});
		})();
	});
})();
