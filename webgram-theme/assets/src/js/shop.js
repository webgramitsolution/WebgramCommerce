/* Webgram Theme shop bundle: AJAX filtering, sorting and pagination, load more, infinite scroll, grid/list toggle,
 * collapsible filter widgets. Loaded only on shop archives. */
import { qs, qsa, scan, emit } from './modules/dom.js';
import { setCookie } from './modules/misc.js';

const cfg = window.webgramData || {};
const shopCfg = cfg.shop || {};
const i18n = cfg.i18n || {};

function shopMain() { return qs('[data-wg-shop-main]'); }

function swap(url, { push = true, append = false } = {}) {
	const main = shopMain();
	if (!main) { window.location.href = url; return Promise.resolve(); }
	const list = qs('[data-wg-products]', main);
	if (list) list.classList.add('is-loading');
	return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
		.then((r) => r.text())
		.then((html) => {
			const doc = new DOMParser().parseFromString(html, 'text/html');
			const next = qs('[data-wg-shop-main]', doc);
			if (!next) { window.location.href = url; return; }
			if (append && list) {
				const nextList = qs('[data-wg-products]', next);
				if (nextList) qsa('li', nextList).forEach((li) => list.appendChild(li));
				const oldMore = qs('[data-wg-load-more]', main);
				const newMore = qs('[data-wg-load-more]', next);
				if (oldMore) { if (newMore) oldMore.replaceWith(newMore); else oldMore.remove(); }
			} else {
				main.innerHTML = next.innerHTML;
				const top = qs('.wg-shop').getBoundingClientRect().top + window.scrollY - (parseInt(getComputedStyle(document.documentElement).getPropertyValue('--wg-header-height'), 10) || 0) - 16;
				window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
			}
			const sidebar = qs('.wg-filters, .wg-drawer--filters .wg-drawer__body');
			const nextSidebar = qs('.wg-filters, .wg-drawer--filters .wg-drawer__body', doc);
			if (sidebar && nextSidebar && !append) sidebar.innerHTML = nextSidebar.innerHTML;
			if (push) window.history.pushState({ wgShop: true }, '', url);
			const title = qs('title', doc);
			if (title) document.title = title.textContent;
			scan(main);
			if (sidebar) scan(sidebar);
			emit('content-updated', { root: main });
			if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
		})
		.catch(() => { window.location.href = url; })
		.then(() => { const l = qs('[data-wg-products]', main); if (l) l.classList.remove('is-loading'); });
}

function isShopUrl(a) {
	if (!a || !a.href || a.target === '_blank' || a.hasAttribute('download')) return false;
	const u = new URL(a.href, window.location.href);
	if (u.origin !== window.location.origin) return false;
	return u.pathname === window.location.pathname || /\/page\/\d+/.test(u.pathname) || u.search.includes('filter_') || u.search.includes('orderby') || u.search.includes('min_price') || a.closest('.wg-filters, .wg-drawer--filters, .woocommerce-pagination, .wg-subcats, .woocommerce-widget-layered-nav, .widget_product_categories, .wg-shop__more');
}

if (shopCfg.ajax) {
	document.addEventListener('click', (e) => {
		const a = e.target.closest('a');
		if (!a || !isShopUrl(a) || e.ctrlKey || e.metaKey) return;
		if (a.closest('.wg-card')) return;
		if (a.closest('[data-wg-load-more]')) return;
		e.preventDefault();
		swap(a.href);
		const drawer = qs('#wg-filters.is-open');
		if (drawer) { const c = qs('[data-wg-close="drawer"]', drawer); if (c) c.click(); }
	});
	document.addEventListener('change', (e) => {
		const select = e.target.closest('.woocommerce-ordering select');
		if (!select) return;
		e.preventDefault();
		const url = new URL(window.location.href);
		url.searchParams.set('orderby', select.value);
		url.searchParams.delete('paged');
		url.pathname = url.pathname.replace(/\/page\/\d+\/?$/, '/');
		swap(url.toString());
	});
	document.addEventListener('submit', (e) => {
		const form = e.target.closest('.wg-filters form, .wg-drawer--filters form');
		if (!form || form.method.toLowerCase() !== 'get') return;
		e.preventDefault();
		const url = new URL(form.action || window.location.href, window.location.href);
		new FormData(form).forEach((v, k) => url.searchParams.set(k, v));
		swap(url.toString());
	});
	window.addEventListener('popstate', (e) => { if (e.state && e.state.wgShop) swap(window.location.href, { push: false }); });
}

// Load more and infinite scroll
document.addEventListener('click', (e) => {
	const btn = e.target.closest('[data-wg-load-more] a');
	if (!btn) return;
	e.preventDefault();
	const wrap = btn.closest('[data-wg-load-more]');
	wrap.classList.add('is-loading');
	btn.textContent = `${i18n.loading || 'Loading'}...`;
	swap(btn.href, { push: false, append: true });
});
const observeMore = () => {
	const wrap = qs('[data-wg-load-more][data-mode="infinite"]');
	if (!wrap || !('IntersectionObserver' in window) || wrap.dataset.observed) return;
	wrap.dataset.observed = '1';
	const io = new IntersectionObserver((entries) => {
		if (entries.some((en) => en.isIntersecting) && !wrap.classList.contains('is-loading')) {
			const a = qs('a', wrap);
			if (a) a.click();
		}
	}, { rootMargin: '400px 0px' });
	io.observe(qs('.wg-shop__more-sentinel', wrap) || wrap);
};
observeMore();
document.addEventListener('wg:content-updated', observeMore);

// Grid / list toggle
document.addEventListener('click', (e) => {
	const btn = e.target.closest('[data-wg-view]');
	if (!btn) return;
	const view = btn.dataset.wgView;
	setCookie('wg_shop_view', view, 30);
	qsa('[data-wg-view]').forEach((b) => { b.classList.toggle('is-active', b === btn); b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
	document.body.classList.toggle('wg-shop-view-list', view === 'list');
	document.body.classList.toggle('wg-shop-view-grid', view === 'grid');
	if (shopCfg.ajax) swap(window.location.href, { push: false }); else window.location.reload();
});

// Collapsible filter widgets and the mobile sidebar toggle
document.addEventListener('click', (e) => {
	const title = e.target.closest('.wg-filters .wg-widget__title');
	if (title) { title.closest('.wg-widget').classList.toggle('is-collapsed'); return; }
	const mobile = e.target.closest('[data-wg-toggle="filters-mobile"]');
	if (mobile) {
		const sidebar = qs('.wg-filters');
		if (!sidebar) return;
		const open = sidebar.style.display !== 'block';
		sidebar.style.display = open ? 'block' : '';
		mobile.setAttribute('aria-expanded', open ? 'true' : 'false');
	}
});
