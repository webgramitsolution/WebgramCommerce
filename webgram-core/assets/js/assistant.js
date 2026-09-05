/* Webgram Core assistant chat window: history, messages, product carousel, suggestion chips, typing state, retry, mic. */
(function () {
	'use strict';
	var Core = window.WebgramCore;
	if (!Core || window.WebgramAssistant) { return; }
	var cfg = (Core.config && Core.config.assistant) || {};
	var i18n = cfg.i18n || {};
	var muted = false;
	try { muted = localStorage.getItem('wgc_assistant_muted') === '1'; } catch (err) { muted = false; }

	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
	function linkify(text) { return esc(text).replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>').replace(/\n/g, '<br>'); }
	function time(d) { d = d ? new Date(d.replace(' ', 'T') + (d.indexOf('Z') === -1 && d.indexOf('T') === -1 ? 'Z' : '')) : new Date(); if (isNaN(d.getTime())) { d = new Date(); } return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
	function track(event, extra) { Core.emit('track', Object.assign({ event: event, object_type: 'assistant' }, extra || {})); }
	function beep() {
		if (muted) { return; }
		try { var ctx = new (window.AudioContext || window.webkitAudioContext)(); var o = ctx.createOscillator(); var g = ctx.createGain(); o.frequency.value = 880; g.gain.value = 0.05; o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.12); } catch (err) {}
	}

	function Window(root) {
		this.root = root;
		this.list = root.querySelector('[data-wgc-assistant-messages]');
		this.chips = root.querySelector('[data-wgc-assistant-suggestions]');
		this.form = root.querySelector('[data-wgc-assistant-form]');
		this.input = root.querySelector('[data-wgc-assistant-input]');
		this.consent = root.querySelector('[data-wgc-assistant-consent]');
		this.loaded = false;
		this.busy = false;
		var self = this;
		var agreed = false;
		try { agreed = localStorage.getItem('wgc_assistant_consent') === '1'; } catch (err) {}
		if (this.consent && (agreed || !cfg.consent)) { this.consent.hidden = true; }
		root.addEventListener('click', function (e) {
			if (e.target.closest('[data-wgc-assistant-agree]')) { try { localStorage.setItem('wgc_assistant_consent', '1'); } catch (err) {} self.consent.hidden = true; self.input.focus(); return; }
			if (e.target.closest('[data-wgc-assistant-close]')) { WebgramAssistant.close(); return; }
			var mute = e.target.closest('[data-wgc-assistant-mute]');
			if (mute) { muted = !muted; try { localStorage.setItem('wgc_assistant_muted', muted ? '1' : '0'); } catch (err) {} mute.setAttribute('aria-pressed', muted ? 'true' : 'false'); mute.classList.toggle('is-muted', muted); return; }
			var chip = e.target.closest('[data-wgc-chip]');
			if (chip) { self.send(chip.getAttribute('data-wgc-chip')); return; }
			var retry = e.target.closest('[data-wgc-retry]');
			if (retry) { var text = retry.getAttribute('data-wgc-retry'); retry.closest('.wgc-assistant__msg').remove(); self.send(text); return; }
			var card = e.target.closest('[data-wgc-assistant-product]');
			if (card) { track('chat_product_click', { product_id: card.getAttribute('data-wgc-assistant-product') }); }
			var add = e.target.closest('[data-wgc-assistant-add]');
			if (add) { track('chat_add_to_cart', { product_id: add.getAttribute('data-wgc-assistant-add') }); }
		});
		this.form.addEventListener('submit', function (e) { e.preventDefault(); self.send(self.input.value); });
		this.input.addEventListener('wgc:voice-submit', function () { self.send(self.input.value); });
		document.dispatchEvent(new CustomEvent('wgc:assistant-ready', { detail: { root: root } }));
	}

	Window.prototype.scroll = function () { this.list.scrollTop = this.list.scrollHeight; };

	Window.prototype.bubble = function (role, html, when, extraClass) {
		var el = document.createElement('div');
		el.className = 'wgc-assistant__msg wg-assistant__msg is-' + role + (extraClass ? ' ' + extraClass : '');
		el.innerHTML = '<div class="wgc-assistant__bubble">' + html + '</div><time class="wgc-assistant__time">' + esc(time(when)) + '</time>';
		this.list.appendChild(el);
		this.scroll();
		return el;
	};

	Window.prototype.products = function (products) {
		if (!products || !products.length) { return ''; }
		return '<div class="wgc-assistant__products">' + products.map(function (p) {
			return '<div class="wgc-assistant__product"><a href="' + esc(p.url) + '" data-wgc-assistant-product="' + p.id + '"><img src="' + esc(p.image) + '" alt="" loading="lazy"><strong>' + esc(p.title) + '</strong><span class="wgc-assistant__price">' + (p.price_html || esc(p.price)) + '</span></a>' +
				(p.add_to_cart_url ? '<a class="wg-btn wg-btn--primary wg-btn--sm" href="' + esc(p.add_to_cart_url) + '" data-wgc-assistant-add="' + p.id + '" data-product_id="' + p.id + '">' + esc(i18n.addToCart || 'Add to cart') + '</a>' : '<a class="wg-btn wg-btn--outline wg-btn--sm" href="' + esc(p.url) + '">' + esc(i18n.view || 'View') + '</a>') + '</div>';
		}).join('') + '</div>';
	};

	Window.prototype.suggest = function (list) {
		this.chips.innerHTML = (list || []).map(function (s) { return '<button type="button" class="wgc-assistant__chip wg-chip" data-wgc-chip="' + esc(s) + '">' + esc(s) + '</button>'; }).join('');
	};

	Window.prototype.load = function () {
		var self = this;
		if (this.loaded) { return Promise.resolve(); }
		this.loaded = true;
		return Core.rest('assistant/conversation').then(function (json) {
			var d = json.data || {};
			self.list.innerHTML = '';
			self.bubble('assistant', linkify(d.greeting || cfg.greeting || ''));
			(d.messages || []).forEach(function (m) { self.bubble(m.role === 'user' ? 'user' : 'assistant', linkify(m.content) + self.products(m.products), m.time); });
			self.suggest(d.suggestions || cfg.suggestions || []);
		}).catch(function () {
			self.bubble('assistant', linkify(cfg.greeting || ''));
			self.suggest(cfg.suggestions || []);
		});
	};

	Window.prototype.send = function (text) {
		var self = this;
		text = (text || '').trim();
		if (!text || this.busy) { return; }
		if (this.consent && !this.consent.hidden) { this.consent.classList.add('is-shake'); setTimeout(function () { self.consent.classList.remove('is-shake'); }, 400); return; }
		this.busy = true;
		this.input.value = '';
		this.suggest([]);
		this.bubble('user', esc(text));
		var typing = this.bubble('assistant', '<span class="wgc-assistant__typing" aria-label="' + esc(i18n.typing || 'Typing') + '"><i></i><i></i><i></i></span>', null, 'is-typing');
		track('chat_message');
		Core.rest('assistant/message', { method: 'POST', body: { message: text } }).then(function (json) {
			var d = json.data || {};
			typing.remove();
			self.bubble('assistant', linkify(d.message || '') + self.products(d.products));
			self.suggest(d.suggestions || []);
			beep();
		}).catch(function (err) {
			typing.remove();
			var msg = err && err.message ? err.message : (i18n.error || 'Error');
			if (/log in/i.test(msg) && cfg.loginUrl) { msg += ' <a href="' + esc(cfg.loginUrl) + '">' + esc(i18n.view || 'Login') + '</a>'; }
			self.bubble('assistant', esc(msg) + ' <button type="button" class="wgc-assistant__retry" data-wgc-retry="' + esc(text) + '">' + esc(i18n.retry || 'Retry') + '</button>', null, 'is-error');
		}).then(function () { self.busy = false; self.input.focus(); });
	};

	var windows = [];
	function init() {
		document.querySelectorAll('[data-wgc-assistant]').forEach(function (root) {
			if (root._wgcWin) { return; }
			root._wgcWin = new Window(root);
			windows.push(root._wgcWin);
			if (root.hasAttribute('data-inline')) { root._wgcWin.load(); }
		});
	}

	window.WebgramAssistant = {
		open: function () {
			init();
			var root = document.querySelector('[data-wgc-assistant]:not([data-inline])');
			if (!root) { return; }
			root.hidden = false;
			document.body.classList.add('wgc-assistant-open');
			root._wgcWin.load().then(function () { root._wgcWin.input.focus(); });
			track('chat_open');
		},
		close: function () {
			var root = document.querySelector('[data-wgc-assistant]:not([data-inline])');
			if (root) { root.hidden = true; document.body.classList.remove('wgc-assistant-open'); }
			var launcher = document.querySelector('[data-wgc-assistant-open]');
			if (launcher) { launcher.focus(); }
		}
	};
	document.addEventListener('keydown', function (e) {
		if (!document.body.classList.contains('wgc-assistant-open')) { return; }
		if (e.key === 'Escape') { window.WebgramAssistant.close(); return; }
		var root = document.querySelector('[data-wgc-assistant]:not([data-inline])');
		if (root && window.WebgramCore) { window.WebgramCore.trapFocus(root, e); }
	});
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
