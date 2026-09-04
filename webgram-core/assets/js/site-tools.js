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

	/* Promo popup */
	var popup = document.querySelector('[data-wgc-popup]');
	if (popup) {
		var key = popup.dataset.key || 'wg_popup_seen';
		var freq = popup.dataset.frequency || 'day';
		var seen = freq === 'session' ? sessionStorage.getItem(key) : getCookie(key);
		if (freq === 'always' || !seen) {
			var shown = false;
			var show = function () {
				if (shown) return;
				shown = true;
				popup.hidden = false;
				document.body.classList.add('wgc-popup-open');
				var focusable = popup.querySelector('[data-wgc-popup-close]');
				if (focusable) focusable.focus();
			};
			var close = function () {
				popup.hidden = true;
				document.body.classList.remove('wgc-popup-open');
				if (freq === 'session') sessionStorage.setItem(key, '1');
				else if (freq !== 'always') setCookie(key, '1', freq === 'week' ? 7 : 1);
			};
			popup.querySelectorAll('[data-wgc-popup-close]').forEach(function (b) { b.addEventListener('click', close); });
			document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !popup.hidden) close(); });
			var trigger = popup.dataset.trigger || 'delay';
			if (trigger === 'delay') {
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
		}
	}

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
