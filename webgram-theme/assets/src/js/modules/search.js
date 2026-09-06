import { qs, qsa, register } from './dom.js';

/** Live search: debounced fetch to wc-ajax webgram_live_search, rendered without templates. Overlay open/close. */
const cfg = window.webgramData || {};
const i18n = cfg.i18n || {};

function endpoint() {
	if (cfg.wcAjax) return cfg.wcAjax.replace('%%endpoint%%', 'webgram_live_search');
	return `${cfg.ajaxUrl || '/wp-admin/admin-ajax.php'}?action=webgram_live_search`;
}
function esc(s) { return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }

function render(data, term) {
	let html = '';
	if (data.products && data.products.length) {
		html += `<span class="wg-search__group-title">${esc(i18n.products || 'Products')}</span><ul>`;
		data.products.forEach((p) => {
			html += `<li><a class="wg-search__product" href="${esc(p.url)}" role="option"><img src="${esc(p.image)}" alt="" loading="lazy"><span><span class="wg-search__product-title">${esc(p.title)}</span><span class="wg-search__product-price">${esc(p.price)}</span></span></a></li>`;
		});
		html += '</ul>';
	}
	if (data.categories && data.categories.length) {
		html += `<span class="wg-search__group-title">${esc(i18n.categories || 'Categories')}</span><ul>`;
		data.categories.forEach((c) => { html += `<li><a class="wg-search__cat" href="${esc(c.url)}" role="option">${esc(c.name)}<small>${esc(c.count)}</small></a></li>`; });
		html += '</ul>';
	}
	if (data.posts && data.posts.length) {
		html += `<span class="wg-search__group-title">${esc(i18n.posts || 'Articles')}</span><ul>`;
		data.posts.forEach((p) => { html += `<li><a class="wg-search__post" href="${esc(p.url)}" role="option">${esc(p.title)}</a></li>`; });
		html += '</ul>';
	}
	if (!html) return `<div class="wg-search__empty">${esc(i18n.noResults || 'No results found')}</div>`;
	if (data.view_all) html += `<a class="wg-search__all" href="${esc(data.view_all)}">${esc(i18n.viewAll || 'View all results')} "${esc(term)}"</a>`;
	return html;
}

register('search', (form) => {
	const input = qs('.wg-search__input', form);
	const results = qs('.wg-search__results', form);
	const live = qs('[data-wg-live]', form);
	const popular = qs('[data-wg-popular]', form);
	const clear = qs('.wg-search__clear', form);
	if (!input) return;
	const min = Math.max(1, parseInt((cfg.search && cfg.search.minChars) || 2, 10));
	let timer;
	let controller;
	let lastTerm = '';

	const show = () => { if (results) { results.hidden = false; input.setAttribute('aria-expanded', 'true'); } };
	const hide = () => { if (results) { results.hidden = true; input.setAttribute('aria-expanded', 'false'); } };
	const toggleClear = () => { if (clear) clear.hidden = !input.value; };

	const query = (term) => {
		if (controller) controller.abort();
		controller = new AbortController();
		live.innerHTML = `<div class="wg-search__loading">${esc(i18n.searching || 'Searching')}...</div>`;
		show();
		const url = new URL(endpoint(), window.location.origin);
		url.searchParams.set('s', term);
		url.searchParams.set('nonce', cfg.nonce || '');
		fetch(url.toString(), { credentials: 'same-origin', signal: controller.signal })
			.then((r) => r.json())
			.then((json) => { if (json && json.success) { live.innerHTML = render(json.data, term); if (popular) popular.hidden = true; } })
			.catch(() => {});
	};

	input.addEventListener('input', () => {
		toggleClear();
		if (!results || form.dataset.live !== '1') return;
		const term = input.value.trim();
		clearTimeout(timer);
		if (term.length < min) { live.innerHTML = ''; if (popular) { popular.hidden = false; show(); } else hide(); return; }
		if (term === lastTerm) return;
		lastTerm = term;
		timer = setTimeout(() => query(term), 250);
	});
	input.addEventListener('focus', () => { if (results && (live.innerHTML || popular)) show(); });
	if (clear) clear.addEventListener('click', () => { input.value = ''; lastTerm = ''; live.innerHTML = ''; toggleClear(); input.focus(); if (popular) { popular.hidden = false; } else hide(); });
	document.addEventListener('click', (e) => { if (!form.contains(e.target)) hide(); });
	form.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') { hide(); return; }
		if (!results || results.hidden) return;
		if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
			const options = qsa('[role="option"], .wg-search__all', results);
			if (!options.length) return;
			e.preventDefault();
			let i = options.findIndex((o) => o.classList.contains('is-focused'));
			i = e.key === 'ArrowDown' ? Math.min(options.length - 1, i + 1) : Math.max(0, i - 1);
			options.forEach((o) => o.classList.remove('is-focused'));
			options[i].classList.add('is-focused');
			options[i].focus();
		}
	});
	toggleClear();
});

register('search-overlay', (overlay) => {
	const input = qs('.wg-search__input', overlay);
	let last = null;
	const open = () => { overlay.hidden = false; requestAnimationFrame(() => overlay.classList.add('is-open')); last = document.activeElement; if (input) setTimeout(() => input.focus(), 200); qsa('[aria-controls="wg-search-overlay"]').forEach((b) => b.setAttribute('aria-expanded', 'true')); };
	const close = () => { overlay.classList.remove('is-open'); setTimeout(() => { overlay.hidden = true; }, 260); qsa('[aria-controls="wg-search-overlay"]').forEach((b) => b.setAttribute('aria-expanded', 'false')); if (last && last.focus) last.focus(); };
	document.addEventListener('click', (e) => {
		if (e.target.closest('[data-wg-toggle="search-overlay"]')) { e.preventDefault(); overlay.hidden ? open() : close(); return; }
		if (e.target.closest('[data-wg-close="search-overlay"]')) { e.preventDefault(); close(); return; }
		if (!overlay.hidden && !overlay.contains(e.target)) close();
	});
	document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !overlay.hidden) close(); });
});
