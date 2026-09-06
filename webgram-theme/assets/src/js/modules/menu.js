import { qs, qsa, register } from './dom.js';

/**
 * Desktop navigation: hover intent, keyboard (Enter/Space open, Escape close, arrows move), touch toggle.
 * Also handles the vertical categories dropdown, account dropdown focus, and mobile accordion toggles.
 */
const HOVER_DELAY = 120;

function closeAll(nav, except) {
	qsa('li.is-open', nav).forEach((li) => {
		if (li === except || (except && li.contains(except))) return;
		li.classList.remove('is-open');
		const link = qs(':scope > a[aria-expanded]', li);
		if (link) link.setAttribute('aria-expanded', 'false');
	});
}
function openItem(li) {
	li.classList.add('is-open');
	const link = qs(':scope > a[aria-expanded]', li);
	if (link) link.setAttribute('aria-expanded', 'true');
}

register('menu', (nav) => {
	let timer;
	const items = qsa(':scope > ul > li.has-children', nav);
	items.forEach((li) => {
		li.addEventListener('mouseenter', () => { clearTimeout(timer); timer = setTimeout(() => { closeAll(nav, li); openItem(li); }, HOVER_DELAY); });
		li.addEventListener('mouseleave', () => { clearTimeout(timer); timer = setTimeout(() => closeAll(nav), HOVER_DELAY); });
		const link = qs(':scope > a', li);
		if (!link) return;
		link.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
				if (!li.classList.contains('is-open') || e.key === 'ArrowDown') {
					e.preventDefault();
					closeAll(nav, li);
					openItem(li);
					const first = qs('.wg-nav__sub a, .wg-mega a', li);
					if (e.key === 'ArrowDown' && first) first.focus();
				}
			}
		});
		// Touch: first tap opens, second follows the link.
		link.addEventListener('touchend', (e) => {
			if (!li.classList.contains('is-open')) { e.preventDefault(); closeAll(nav, li); openItem(li); }
		}, { passive: false });
	});
	nav.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') { closeAll(nav); const open = qs('li.is-open > a', nav); if (open) open.focus(); }
		if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
			const top = e.target.closest('.wg-nav > li');
			if (!top || e.target.closest('.wg-nav__sub, .wg-mega')) return;
			e.preventDefault();
			const sib = e.key === 'ArrowRight' ? top.nextElementSibling : top.previousElementSibling;
			const a = sib && qs(':scope > a', sib);
			if (a) { closeAll(nav); a.focus(); }
		}
	});
	nav.addEventListener('focusout', (e) => { if (!nav.contains(e.relatedTarget)) closeAll(nav); });

	// Current item by URL (menus are cached without current classes).
	const here = window.location.href.replace(/\/$/, '').split('#')[0];
	qsa('a[href]', nav).forEach((a) => {
		const href = a.href.replace(/\/$/, '').split('#')[0];
		if (href && href === here) { a.classList.add('is-current'); a.setAttribute('aria-current', 'page'); }
	});
});

document.addEventListener('click', (e) => {
	if (!e.target.closest('.wg-menu')) qsa('.wg-menu li.is-open').forEach((li) => { li.classList.remove('is-open'); const a = qs(':scope > a[aria-expanded]', li); if (a) a.setAttribute('aria-expanded', 'false'); });
	const toggle = e.target.closest('.wg-nav__toggle');
	if (toggle) {
		e.preventDefault();
		const li = toggle.closest('li');
		const open = !li.classList.contains('is-open');
		li.classList.toggle('is-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	}
});

register('vmenu', (el) => {
	const btn = qs('.wg-vmenu__toggle', el);
	const panel = qs('.wg-vmenu__panel', el);
	if (!btn || !panel) return;
	const set = (open) => { panel.hidden = !open; btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
	btn.addEventListener('click', () => set(panel.hidden));
	document.addEventListener('click', (e) => { if (!el.contains(e.target)) set(false); });
	el.addEventListener('keydown', (e) => { if (e.key === 'Escape') { set(false); btn.focus(); } });
});

register('account', (el) => {
	const link = qs('a[aria-haspopup]', el);
	if (!link) return;
	link.addEventListener('keydown', (e) => {
		if (e.key === 'Enter' || e.key === ' ') {
			if (!el.classList.contains('is-open')) { e.preventDefault(); el.classList.add('is-open'); link.setAttribute('aria-expanded', 'true'); const first = qs('.wg-account__menu a', el); if (first) first.focus(); }
		}
	});
	el.addEventListener('keydown', (e) => { if (e.key === 'Escape') { el.classList.remove('is-open'); link.setAttribute('aria-expanded', 'false'); link.focus(); } });
	el.addEventListener('focusout', (e) => { if (!el.contains(e.relatedTarget)) { el.classList.remove('is-open'); link.setAttribute('aria-expanded', 'false'); } });
});
