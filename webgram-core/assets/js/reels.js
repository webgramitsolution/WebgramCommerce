/* Webgram Core reels: muted autoplay in viewport, mute toggle, full screen viewer with product sheet and add to cart. */
(function () {
	'use strict';
	var Core = window.WebgramCore;
	if (!Core) { return; }
	var cfg = (Core.config && Core.config.reels) || {};
	var i18n = cfg.i18n || {};
	var mobile = window.matchMedia('(max-width: 767.98px)');

	function track(event, reel, extra) {
		Core.emit('track', Object.assign({ event: event, object_type: 'reel', object_id: reel && reel.id }, extra || {}));
		if (Core.track) { Core.track(event, 'reel', reel && reel.id, extra || {}); }
	}

	function toast(msg) { if (msg && typeof Core.toast === 'function') { Core.toast(msg); } }

	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }

	function addToCart(id, btn) {
		var url = Core.config.wcAjax ? Core.config.wcAjax.replace('%%endpoint%%', 'add_to_cart') : '';
		if (!url) { return; }
		var body = new FormData();
		body.append('product_id', id);
		body.append('quantity', 1);
		if (btn) { btn.disabled = true; }
		fetch(url, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (json) {
			if (json && json.error) { if (json.product_url) { window.location.href = json.product_url; } return; }
			if (window.jQuery) { jQuery(document.body).trigger('added_to_cart', [json.fragments, json.cart_hash, jQuery(btn)]); }
			else { document.dispatchEvent(new CustomEvent('wg:added_to_cart', { detail: json })); }
			toast(i18n.added);
			track('reel_add_to_cart', null, { product_id: id });
		}).catch(function () {}).then(function () { if (btn) { btn.disabled = false; } });
	}

	/* Cards: autoplay muted when in viewport (one at a time on mobile, all visible on desktop). */
	var playing = [];
	var observer = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			var video = entry.target;
			if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
				if (mobile.matches) { playing.forEach(function (v) { if (v !== video) { v.pause(); } }); playing = [video]; } else if (playing.indexOf(video) === -1) { playing.push(video); }
				var p = video.play(); if (p && p.catch) { p.catch(function () {}); }
				if (!video._wgcTracked) { video._wgcTracked = true; track('reel_impression', JSON.parse(video.closest('[data-wgc-reel]').getAttribute('data-wgc-reel'))); }
			} else { video.pause(); }
		});
	}, { threshold: [0, 0.6] }) : null;

	function initRow(row) {
		if (row._wgcInit) { return; }
		row._wgcInit = true;
		if (row.getAttribute('data-autoplay') === '1' && observer) {
			row.querySelectorAll('video').forEach(function (v) { observer.observe(v); });
		}
		row.addEventListener('click', function (e) {
			var mute = e.target.closest('[data-wgc-reel-mute]');
			if (mute) {
				var video = mute.closest('[data-wgc-reel]').querySelector('video');
				if (video) { video.muted = !video.muted; mute.setAttribute('aria-pressed', video.muted ? 'false' : 'true'); mute.setAttribute('aria-label', video.muted ? i18n.unmute : i18n.mute); mute.classList.toggle('is-unmuted', !video.muted); }
				return;
			}
			var add = e.target.closest('[data-wgc-reel-add]');
			if (add) { e.preventDefault(); addToCart(add.getAttribute('data-wgc-reel-add'), add); return; }
			var link = e.target.closest('[data-wgc-reel-product]');
			if (link) { track('reel_product_click', null, { product_id: link.getAttribute('data-wgc-reel-product') }); return; }
			var open = e.target.closest('[data-wgc-reel-open]');
			if (open) {
				e.preventDefault();
				var cards = Array.prototype.slice.call(row.querySelectorAll('[data-wgc-reel]'));
				var reels = cards.map(function (c) { return JSON.parse(c.getAttribute('data-wgc-reel')); });
				openViewer(reels, cards.indexOf(open.closest('[data-wgc-reel]')));
			}
		});
	}

	/* Viewer */
	var viewer = null, feed = null, tpl = null, state = { reels: [], index: 0, muted: true, opener: null };

	function stageHtml(reel) {
		var e = reel.embed || {};
		if (e.type === 'video') { return '<video src="' + esc(e.src) + '" poster="' + esc(reel.poster) + '" playsinline loop preload="metadata" ' + (state.muted ? 'muted' : '') + '></video>'; }
		if (e.type === 'iframe') { return '<iframe src="' + esc(e.src) + '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy" title="' + esc(reel.title) + '"></iframe>'; }
		return '<img src="' + esc(reel.poster) + '" alt="">';
	}

	function sheetHtml(reel) {
		return (reel.products || []).map(function (p) {
			return '<div class="wgc-reel-sheet__item"><a href="' + esc(p.url) + '" data-wgc-reel-product="' + p.id + '"><img src="' + esc(p.image) + '" alt="" width="56" height="56"><span><strong>' + esc(p.name) + '</strong><span class="wgc-reel__price">' + p.price_html + '</span></span></a>' +
				(p.add ? '<button type="button" class="wg-btn wg-btn--primary wg-btn--sm" data-wgc-reel-add="' + p.id + '">' + esc((Core.config.i18n && Core.config.i18n.add) || 'ADD') + '</button>' : '<a class="wg-btn wg-btn--outline wg-btn--sm" href="' + esc(p.url) + '">' + esc(i18n.viewProduct || 'View') + '</a>') + '</div>';
		}).join('') + (reel.cta && reel.cta.text ? '<a class="wg-btn wg-btn--secondary wg-btn--block wgc-reel-sheet__cta" href="' + esc(reel.cta.url || '#') + '">' + esc(reel.cta.text) + '</a>' : '');
	}

	function build() {
		feed.innerHTML = '';
		state.reels.forEach(function (reel, i) {
			var node = tpl.content.firstElementChild.cloneNode(true);
			node.setAttribute('data-index', i);
			node.querySelector('[data-stage]').innerHTML = stageHtml(reel);
			node.querySelector('[data-caption]').innerHTML = '<strong>' + esc(reel.title) + '</strong>' + (reel.caption ? '<p>' + esc(reel.caption) + '</p>' : '');
			node.querySelector('[data-sheet-list]').innerHTML = sheetHtml(reel);
			node.querySelector('[data-products-count]').textContent = (reel.products || []).length || '';
			if (!(reel.products || []).length) { node.querySelector('[data-products]').hidden = true; }
			feed.appendChild(node);
		});
	}

	function show(i, viaSwipe) {
		state.index = (i + state.reels.length) % state.reels.length;
		var items = feed.children;
		Array.prototype.forEach.call(items, function (item, k) {
			var v = item.querySelector('video');
			var active = k === state.index;
			item.classList.toggle('is-active', active);
			if (v) { v.muted = state.muted; if (active) { var p = v.play(); if (p && p.catch) { p.catch(function () {}); } } else { v.pause(); } }
			var f = item.querySelector('iframe');
			if (f && !active && f.getAttribute('src')) { f.setAttribute('data-src', f.getAttribute('src')); f.removeAttribute('src'); }
			if (f && active && !f.getAttribute('src') && f.getAttribute('data-src')) { f.setAttribute('src', f.getAttribute('data-src')); }
		});
		if (!viaSwipe) { var target = items[state.index]; if (target) { feed.scrollTo({ top: target.offsetTop, behavior: 'smooth' }); } }
		track('reel_play', state.reels[state.index]);
		var v = items[state.index] && items[state.index].querySelector('video');
		if (v && !v._wgcEnded) { v._wgcEnded = true; v.addEventListener('timeupdate', function onT() { if (v.duration && v.currentTime >= v.duration - 0.3) { track('reel_complete', state.reels[state.index]); v.removeEventListener('timeupdate', onT); } }); }
	}

	function openViewer(reels, index) {
		viewer = viewer || document.querySelector('[data-wgc-reel-viewer]');
		if (!viewer) { return; }
		feed = viewer.querySelector('[data-wgc-reel-feed]');
		tpl = viewer.querySelector('[data-wgc-reel-template]');
		state.reels = reels; state.opener = document.activeElement;
		playing.forEach(function (v) { v.pause(); });
		build();
		viewer.hidden = false;
		document.body.classList.add('wgc-reel-open');
		show(index);
		viewer.querySelector('[data-wgc-reel-close]').focus();
		document.addEventListener('keydown', onKey);
	}

	function closeViewer() {
		if (!viewer) { return; }
		viewer.hidden = true;
		feed.innerHTML = '';
		document.body.classList.remove('wgc-reel-open');
		document.removeEventListener('keydown', onKey);
		if (state.opener && state.opener.focus) { state.opener.focus(); }
	}

	function onKey(e) {
		if (e.key === 'Escape') { closeViewer(); return; }
		if (e.key === 'Tab') { window.WebgramCore.trapFocus(viewer, e); return; }
		if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { show(state.index + 1); }
		if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { show(state.index - 1); }
	}

	document.addEventListener('click', function (e) {
		if (!viewer || viewer.hidden) { return; }
		if (e.target.closest('[data-wgc-reel-close]')) { closeViewer(); return; }
		if (e.target.closest('[data-wgc-reel-prev]')) { show(state.index - 1); return; }
		if (e.target.closest('[data-wgc-reel-next]')) { show(state.index + 1); return; }
		var mute = e.target.closest('[data-mute]');
		if (mute) { state.muted = !state.muted; mute.setAttribute('aria-pressed', state.muted ? 'false' : 'true'); mute.classList.toggle('is-unmuted', !state.muted); feed.querySelectorAll('video').forEach(function (v) { v.muted = state.muted; }); return; }
		var products = e.target.closest('[data-products]');
		if (products) { var sheet = products.closest('section').querySelector('[data-sheet]'); sheet.hidden = !sheet.hidden; return; }
		if (e.target.closest('[data-sheet-close]')) { e.target.closest('[data-sheet]').hidden = true; return; }
		var add = e.target.closest('[data-wgc-reel-add]');
		if (add) { e.preventDefault(); addToCart(add.getAttribute('data-wgc-reel-add'), add); return; }
		var link = e.target.closest('[data-wgc-reel-product]');
		if (link) { track('reel_product_click', null, { product_id: link.getAttribute('data-wgc-reel-product') }); }
	});

	/* Vertical swipe feed on mobile: snap scrolling drives the active reel. */
	document.addEventListener('scroll', function (e) {
		if (!feed || !viewer || viewer.hidden || e.target !== feed) { return; }
		clearTimeout(feed._t);
		feed._t = setTimeout(function () {
			var i = Math.round(feed.scrollTop / Math.max(1, feed.clientHeight));
			if (i !== state.index && feed.children[i]) { show(i, true); }
		}, 80);
	}, true);

	function init() { document.querySelectorAll('[data-wgc-reels]').forEach(initRow); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
	document.addEventListener('wg:content-updated', init);
})();
