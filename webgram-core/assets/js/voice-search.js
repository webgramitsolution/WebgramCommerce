/* Webgram Core voice search: Web Speech API mic button. Hidden when the browser has no SpeechRecognition. */
(function () {
	'use strict';
	var Core = window.WebgramCore;
	if (!Core) { return; }
	var cfg = (Core.config && Core.config.voice) || {};
	var i18n = cfg.i18n || {};
	var Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
	var supported = cfg.engine === 'web_speech' && typeof Recognition === 'function';

	function targetInput(btn) {
		var id = btn.getAttribute('data-wgc-voice');
		var el = id ? document.getElementById(id) : null;
		if (!el) { var form = btn.closest('form, [data-wgc-assistant-form]'); el = form ? form.querySelector('input[type="search"], input[type="text"], textarea') : null; }
		return el;
	}

	function init(root) {
		(root || document).querySelectorAll('[data-wgc-voice]').forEach(function (btn) {
			if (btn._wgcInit) { return; }
			btn._wgcInit = true;
			if (!supported) { btn.hidden = true; return; }
			btn.hidden = false;
			var rec = null;
			btn.addEventListener('click', function () {
				var input = targetInput(btn);
				if (!input) { return; }
				if (rec) { rec.stop(); return; }
				rec = new Recognition();
				rec.lang = cfg.lang || 'en-IN';
				rec.interimResults = cfg.interim !== false;
				rec.continuous = false;
				rec.maxAlternatives = 1;
				var finalText = '';
				btn.classList.add('is-listening');
				btn.setAttribute('aria-label', i18n.stop || 'Stop');
				input.setAttribute('placeholder', i18n.listening || 'Listening');
				rec.onresult = function (e) {
					var interim = '';
					for (var i = e.resultIndex; i < e.results.length; i++) {
						if (e.results[i].isFinal) { finalText += e.results[i][0].transcript; } else { interim += e.results[i][0].transcript; }
					}
					input.value = (finalText || interim).trim();
					input.dispatchEvent(new Event('input', { bubbles: true }));
				};
				rec.onerror = function (e) {
					if (typeof Core.toast === 'function') { Core.toast(e.error === 'not-allowed' ? i18n.denied : i18n.error); }
				};
				rec.onend = function () {
					btn.classList.remove('is-listening');
					btn.setAttribute('aria-label', i18n.start || 'Search by voice');
					rec = null;
					if (finalText.trim()) {
						input.value = finalText.trim();
						input.dispatchEvent(new Event('input', { bubbles: true }));
						Core.emit('voice:result', { input: input, text: finalText.trim() });
						Core.track('voice_search', 'search', 0, { length: finalText.trim().length });
						if (cfg.autoSubmit) {
							var form = input.closest('form');
							if (form && form.getAttribute('role') === 'search') { form.requestSubmit ? form.requestSubmit() : form.submit(); }
							else { input.dispatchEvent(new CustomEvent('wgc:voice-submit', { bubbles: true })); }
						}
					}
				};
				try { rec.start(); } catch (err) { rec = null; }
			});
		});
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }
	document.addEventListener('wg:content-updated', function () { init(); });
	document.addEventListener('wgc:assistant-ready', function (e) { init(e.detail && e.detail.root); });
})();
