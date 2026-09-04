import { qs, qsa, register } from './dom.js';

/** Cookie helpers (no external library). */
export function setCookie(name, value, days) {
	let expires = '';
	if (days > 0) { const d = new Date(); d.setTime(d.getTime() + days * 864e5); expires = `; expires=${d.toUTCString()}`; }
	document.cookie = `${name}=${encodeURIComponent(value)}${expires}; path=/; SameSite=Lax${window.location.protocol === 'https:' ? '; Secure' : ''}`;
}
export function getCookie(name) {
	const m = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&')}=([^;]*)`));
	return m ? decodeURIComponent(m[1]) : null;
}

register('bottom-nav', (nav) => {
	const here = window.location.href.replace(/\/$/, '');
	qsa('a[href]', nav).forEach((a) => { if (a.href.replace(/\/$/, '') === here) a.classList.add('is-current'); });
	if (nav.dataset.hideOnScroll !== '1') return;
	let last = window.scrollY;
	let ticking = false;
	window.addEventListener('scroll', () => {
		if (ticking) return;
		ticking = true;
		requestAnimationFrame(() => {
			const y = window.scrollY;
			nav.classList.toggle('is-hidden', y > last && y > 120);
			last = y;
			ticking = false;
		});
	}, { passive: true });
});

register('back-to-top', (btn) => {
	const offset = parseInt(btn.dataset.offset || '400', 10);
	const update = () => { const on = window.scrollY > offset; btn.hidden = !on && !btn.classList.contains('is-visible'); btn.classList.toggle('is-visible', on); if (on) btn.hidden = false; };
	window.addEventListener('scroll', update, { passive: true });
	btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
	update();
});

register('banner', (banner) => {
	const key = banner.dataset.key || 'wg_banner';
	if (getCookie(key) === '1' || sessionStorage.getItem(key) === '1') return;
	banner.hidden = false;
	const close = qs('[data-wg-banner-close]', banner);
	if (close) close.addEventListener('click', () => {
		banner.hidden = true;
		const days = parseInt(banner.dataset.days || '0', 10);
		if (days > 0) setCookie(key, '1', days); else sessionStorage.setItem(key, '1');
	});
});

register('preloader', (el) => {
	const done = () => el.classList.add('is-done');
	if (document.readyState === 'complete') done(); else window.addEventListener('load', done);
	setTimeout(done, 4000);
});
