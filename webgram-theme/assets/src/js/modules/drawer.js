import { qs, qsa, register } from './dom.js';

const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])';
let openDrawer = null;
let lastFocus = null;

function overlay() { return qs('.wg-overlay'); }

export function open(drawer) {
	if (openDrawer && openDrawer !== drawer) close();
	lastFocus = document.activeElement;
	drawer.hidden = false;
	overlay() && (overlay().hidden = false);
	requestAnimationFrame(() => {
		drawer.classList.add('is-open');
		overlay() && overlay().classList.add('is-open');
	});
	document.body.classList.add('wg-drawer-open');
	openDrawer = drawer;
	qsa(`[aria-controls="${drawer.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'true'));
	const first = qs(FOCUSABLE, drawer);
	first && first.focus();
	document.addEventListener('keydown', onKey);
}

export function close() {
	if (!openDrawer) return;
	const drawer = openDrawer;
	drawer.classList.remove('is-open');
	overlay() && overlay().classList.remove('is-open');
	document.body.classList.remove('wg-drawer-open');
	qsa(`[aria-controls="${drawer.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'false'));
	setTimeout(() => { drawer.hidden = true; if (overlay()) overlay().hidden = true; }, 260);
	openDrawer = null;
	document.removeEventListener('keydown', onKey);
	lastFocus && lastFocus.focus && lastFocus.focus();
}

function onKey(e) {
	if (e.key === 'Escape') { close(); return; }
	if (e.key !== 'Tab' || !openDrawer) return;
	const items = qsa(FOCUSABLE, openDrawer);
	if (!items.length) return;
	const first = items[0], last = items[items.length - 1];
	if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
	else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
}

register('drawer', (drawer) => {
	// Menu | Categories tabs.
	qsa('[data-wg-tab]', drawer).forEach((tab) => {
		tab.addEventListener('click', () => {
			qsa('[data-wg-tab]', drawer).forEach((t) => { const on = t === tab; t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
			qsa('.wg-drawer__pane', drawer).forEach((p) => { const on = p.id === tab.getAttribute('aria-controls'); p.classList.toggle('is-active', on); p.hidden = !on; });
		});
	});
});

document.addEventListener('click', (e) => {
	const toggle = e.target.closest('[data-wg-toggle]');
	if (toggle) {
		const target = toggle.dataset.wgToggle;
		const drawer = target === 'mobile-menu' ? qs('#wg-mobile-menu') : qs(`[data-wg-drawer="${target}"]`);
		if (drawer) { e.preventDefault(); openDrawer === drawer ? close() : open(drawer); }
		return;
	}
	if (e.target.closest('[data-wg-close="drawer"]')) { e.preventDefault(); close(); }
});
