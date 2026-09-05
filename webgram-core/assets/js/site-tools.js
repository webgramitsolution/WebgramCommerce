/* Webgram Core Site Tools: promo popup, cookie notice, age gate, maintenance countdown. No jQuery. */
(function () {
	'use strict';

	function setCookie(name, value, days) {
		var expires = '';
		if (days > 0) { var d = new Date(); d.setTime(d.getTime() + days * 864e5); expires = '; expires=' + d.toUTCString(); }
		document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
	}
	function getCookie(name) {
		var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
		return m ? decodeURIComponent(m[1]) : null;
	}

	/* Popups: several may exist; each has its own trigger, frequency and device list. One opens at a time. */
	var popupOpen = null;
	var lastFocus = null;
	function deviceMatches(list) {
		if (!list) return true;
		var w = window.innerWidth;
		var device = w < 768 ? 'mobile' : (w < 992 ? 'tablet' : 'desktop');
		return list.split(',').indexOf(device) !== -1;
	}
	document.querySelectorAll('[data-wgc-popup]').forEach(function (popup) {
		var key = popup.dataset.key || 'wg_popup_seen';
		var freq = popup.dataset.frequency || 'days';
		var days = Math.max(1, parseInt(popup.dataset.days || '1', 10));
		var seen = freq === 'session' ? sessionStorage.getItem(key) : getCookie(key);
		var trigger = popup.dataset.trigger || 'delay';
		if (!deviceMatches(popup.dataset.devices)) return;
		if (freq !== 'always' && seen && trigger !== 'click') return;
		var shown = false;
		var show = function () {
			if (shown && trigger !== 'click') return;
			if (popupOpen && popupOpen !== popup) return;
			shown = true;
			popupOpen = popup;
			lastFocus = document.activeElement;
			popup.hidden = false;
			document.body.classList.add('wgc-popup-open');
			var focusable = popup.querySelector('[data-wgc-popup-close]:not(.wgc-popup__backdrop), a, button:not([data-wgc-popup-close]), input');
			if (focusable) focusable.focus();
		};
		var close = function () {
			popup.hidden = true;
			popupOpen = null;
			document.body.classList.remove('wgc-popup-open');
			if (freq === 'session') sessionStorage.setItem(key, '1');
			else if (freq !== 'always') setCookie(key, '1', days);
			if (lastFocus && lastFocus.focus) lastFocus.focus();
		};
		popup.querySelectorAll('[data-wgc-popup-close]').forEach(function (b) { b.addEventListener('click', close); });
		document.addEventListener('keydown', function (e) {
			if (popup.hidden) return;
			if (e.key === 'Escape') { close(); return; }
			if (e.key === 'Tab' && window.WebgramCore && window.WebgramCore.trapFocus) window.WebgramCore.trapFocus(popup, e);
		});
		if (trigger === 'click') {
			var selector = popup.dataset.selector;
			if (!selector) return;
			document.addEventListener('click', function (e) {
				var hit = null;
				try { hit = e.target.closest(selector); } catch (err) { hit = null; }
				if (hit) { e.preventDefault(); show(); }
			});
		} else if (trigger === 'load') {
			show();
		} else if (trigger === 'delay') {
			setTimeout(show, Math.max(0, parseInt(popup.dataset.delay || '5', 10)) * 1000);
		} else if (trigger === 'scroll') {
			var depth = parseInt(popup.dataset.scroll || '40', 10);
			window.addEventListener('scroll', function onScroll() {
				var max = document.documentElement.scrollHeight - window.innerHeight;
				if (max > 0 && (window.scrollY / max) * 100 >= depth) { show(); window.removeEventListener('scroll', onScroll); }
			}, { passive: true });
		} else {
			document.addEventListener('mouseout', function (e) { if (!e.relatedTarget && e.clientY <= 0) show(); });
		}
	});

	/* Floating blocks: optional show after scrolling. */
	document.querySelectorAll('[data-wgc-floating]').forEach(function (stack) {
		var after = parseInt(stack.dataset.scroll || '0', 10);
		if (document.querySelector('.wg-back-to-top')) stack.classList.add('wgc-floating--has-back-to-top');
		if (after <= 0) return;
		var update = function () { stack.hidden = window.scrollY < after; };
		window.addEventListener('scroll', update, { passive: true });
		update();
	});

	/* Cookie notice */
	var cookie = document.querySelector('[data-wgc-cookie]');
	if (cookie) {
		if (!getCookie('wg_cookie_ok')) cookie.hidden = false;
		cookie.querySelectorAll('[data-wgc-cookie-choice]').forEach(function (b) {
			b.addEventListener('click', function () {
				setCookie('wg_cookie_ok', b.dataset.wgcCookieChoice, parseInt(cookie.dataset.days || '180', 10));
				cookie.hidden = true;
				document.dispatchEvent(new CustomEvent('wg:cookie-consent', { detail: { choice: b.dataset.wgcCookieChoice } }));
			});
		});
	}

	/* Age gate */
	var age = document.querySelector('[data-wgc-age]');
	if (age) {
		if (getCookie('wg_age_ok')) { age.remove(); } else {
			document.body.classList.add('wgc-age-locked');
			var pass = function () { setCookie('wg_age_ok', '1', parseInt(age.dataset.days || '30', 10)); age.remove(); document.body.classList.remove('wgc-age-locked'); };
			var fail = function () { window.location.href = age.dataset.redirect || 'https://www.google.com'; };
			var yes = age.querySelector('[data-wgc-age-yes]');
			var no = age.querySelector('[data-wgc-age-no]');
			var form = age.querySelector('[data-wgc-age-form]');
			if (yes) yes.addEventListener('click', pass);
			if (no) no.addEventListener('click', fail);
			if (form) form.addEventListener('submit', function (e) {
				e.preventDefault();
				var dob = new Date(form.querySelector('[name="dob"]').value);
				var min = parseInt(age.dataset.min || '18', 10);
				var now = new Date();
				var years = now.getFullYear() - dob.getFullYear() - ((now.getMonth() < dob.getMonth() || (now.getMonth() === dob.getMonth() && now.getDate() < dob.getDate())) ? 1 : 0);
				if (!isNaN(dob.getTime()) && years >= min) pass(); else { var err = age.querySelector('[data-wgc-age-error]'); if (err) err.hidden = false; }
			});
		}
	}

	/* Countdown */
	var cd = document.querySelector('[data-wgc-countdown]');
	if (cd) {
		var target = parseInt(cd.dataset.wgcCountdown, 10) * 1000;
		var tick = function () {
			var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
			var units = { days: Math.floor(diff / 86400), hours: Math.floor((diff % 86400) / 3600), minutes: Math.floor((diff % 3600) / 60), seconds: diff % 60 };
			Object.keys(units).forEach(function (u) { var el = cd.querySelector('[data-unit="' + u + '"]'); if (el) el.textContent = String(units[u]).padStart(2, '0'); });
		};
		tick();
		setInterval(tick, 1000);
	}
})();
