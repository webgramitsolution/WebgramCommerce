/* Webgram Theme product bundle: gallery, sticky gallery release, swatches, quantity stepper, mobile sticky bar,
 * accordions, copy link. Loaded only on single product pages. */
import { qs, qsa, register, scan, emit } from './modules/dom.js';
import './modules/product-card.js';

const cfg = (window.webgramData && window.webgramData.product) || {};

/* Gallery */
register('gallery', (gallery) => {
	const slides = qsa('.wg-gallery__slide', gallery);
	const thumbs = qsa('.wg-gallery__thumb', gallery);
	const thumbsBox = qs('[data-wg-gallery-thumbs]', gallery);
	const dots = qs('.wg-gallery__dots', gallery);
	if (!slides.length) return;
	let index = 0;
	let timer = null;
	let interacted = false;

	if (dots) slides.forEach((s, i) => { const d = document.createElement('span'); if (i === 0) d.classList.add('is-active'); dots.appendChild(d); });
	if (cfg.zoom && matchMedia('(hover: hover)').matches) gallery.classList.add('wg-gallery--zoom');

	const go = (i, user = false) => {
		index = (i + slides.length) % slides.length;
		slides.forEach((s, k) => s.classList.toggle('is-active', k === index));
		thumbs.forEach((t, k) => { t.classList.toggle('is-active', k === index); t.setAttribute('aria-selected', k === index ? 'true' : 'false'); if (k === index && thumbsBox) t.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' }); });
		if (dots) qsa('span', dots).forEach((d, k) => d.classList.toggle('is-active', k === index));
		const active = slides[index];
		const embed = qs('[data-wg-embed]', active);
		if (embed && !embed.dataset.loaded) {
			embed.dataset.loaded = '1';
			const url = embed.dataset.wgEmbed;
			let src = url;
			const yt = url.match(/(?:youtu\.be\/|v=|shorts\/)([\w-]{6,})/);
			const vm = url.match(/vimeo\.com\/(\d+)/);
			if (yt) src = `https://www.youtube-nocookie.com/embed/${yt[1]}?autoplay=1&rel=0`;
			else if (vm) src = `https://player.vimeo.com/video/${vm[1]}?autoplay=1`;
			embed.innerHTML = `<iframe src="${src}" allow="autoplay; fullscreen" allowfullscreen title="Product video"></iframe>`;
		}
		qsa('video', gallery).forEach((v) => { if (!active.contains(v)) v.pause(); });
		if (user) { interacted = true; stop(); }
		emit('gallery-change', { index });
	};
	const stop = () => { clearInterval(timer); timer = null; };
	const start = () => {
		if (!cfg.autoSlide || interacted || slides.length < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
		stop();
		timer = setInterval(() => { if (!slides[index].classList.contains('wg-gallery__slide--video')) go(index + 1); }, Math.max(1000, cfg.interval || 3000));
	};

	thumbs.forEach((t) => t.addEventListener('click', () => go(parseInt(t.dataset.index, 10), true)));
	const prev = qs('[data-wg-gallery-prev]', gallery);
	const next = qs('[data-wg-gallery-next]', gallery);
	if (prev) prev.addEventListener('click', () => go(index - 1, true));
	if (next) next.addEventListener('click', () => go(index + 1, true));
	const tp = qs('[data-wg-thumbs-prev]', gallery);
	const tn = qs('[data-wg-thumbs-next]', gallery);
	if (tp && thumbsBox) tp.addEventListener('click', () => thumbsBox.scrollBy({ left: -200, top: -200, behavior: 'smooth' }));
	if (tn && thumbsBox) tn.addEventListener('click', () => thumbsBox.scrollBy({ left: 200, top: 200, behavior: 'smooth' }));
	if (cfg.pauseOnHover) { gallery.addEventListener('mouseenter', stop); gallery.addEventListener('mouseleave', () => { if (!interacted) start(); }); }
	gallery.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') go(index - 1, true); if (e.key === 'ArrowRight') go(index + 1, true); });

	// Swipe on touch
	let x0 = null;
	const main = qs('.wg-gallery__main', gallery);
	main.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; }, { passive: true });
	main.addEventListener('touchend', (e) => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; x0 = null; if (Math.abs(dx) > 40) go(dx < 0 ? index + 1 : index - 1, true); }, { passive: true });

	// Zoom origin follows the cursor
	main.addEventListener('mousemove', (e) => {
		const a = e.target.closest('.wg-gallery__zoom');
		if (!a) return;
		const r = a.getBoundingClientRect();
		a.style.setProperty('--wg-zoom-x', `${((e.clientX - r.left) / r.width) * 100}%`);
		a.style.setProperty('--wg-zoom-y', `${((e.clientY - r.top) / r.height) * 100}%`);
		const img = qs('img', a);
		if (img) { img.style.setProperty('--wg-zoom-x', `${((e.clientX - r.left) / r.width) * 100}%`); img.style.setProperty('--wg-zoom-y', `${((e.clientY - r.top) / r.height) * 100}%`); }
	});

	// Lightbox
	let box = null;
	const openBox = (i) => {
		if (!box) {
			box = document.createElement('div');
			box.className = 'wg-lightbox';
			box.setAttribute('role', 'dialog');
			box.setAttribute('aria-modal', 'true');
			box.innerHTML = `<button type="button" class="wg-lightbox__close" aria-label="Close">${qs('.wg-icon--close') ? qs('.wg-icon--close').outerHTML : '&times;'}</button><button type="button" class="wg-lightbox__prev" aria-label="Previous">${prev ? prev.innerHTML : '&lsaquo;'}</button><img alt=""><button type="button" class="wg-lightbox__next" aria-label="Next">${next ? next.innerHTML : '&rsaquo;'}</button>`;
			document.body.appendChild(box);
			qs('.wg-lightbox__close', box).addEventListener('click', () => { box.hidden = true; });
			qs('.wg-lightbox__prev', box).addEventListener('click', () => openBox(index - 1));
			qs('.wg-lightbox__next', box).addEventListener('click', () => openBox(index + 1));
			box.addEventListener('click', (e) => { if (e.target === box) box.hidden = true; });
			document.addEventListener('keydown', (e) => { if (box.hidden) return; if (e.key === 'Escape') box.hidden = true; if (e.key === 'ArrowLeft') openBox(index - 1); if (e.key === 'ArrowRight') openBox(index + 1); });
		}
		let k = (i + slides.length) % slides.length;
		let tries = 0;
		while (!slides[k].dataset.full && tries < slides.length) { k = (k + 1) % slides.length; tries += 1; }
		if (!slides[k].dataset.full) return;
		go(k, true);
		qs('img', box).src = slides[k].dataset.full;
		box.hidden = false;
	};
	gallery.addEventListener('click', (e) => {
		const a = e.target.closest('[data-wg-lightbox]');
		if (!a) return;
		e.preventDefault();
		openBox(index);
	});

	// Variation image: WooCommerce swaps the main image src; keep it on the first slide.
	document.addEventListener('wg:variation-image', (e) => {
		const src = e.detail && e.detail.src;
		const first = qs('.wg-gallery__slide[data-index="0"] img', gallery);
		if (!first) return;
		if (!first.dataset.original) { first.dataset.original = first.src; first.dataset.originalSrcset = first.getAttribute('srcset') || ''; }
		if (src) { first.src = src; first.removeAttribute('srcset'); slides[0].dataset.full = e.detail.full || src; }
		else { first.src = first.dataset.original; if (first.dataset.originalSrcset) first.setAttribute('srcset', first.dataset.originalSrcset); }
		go(0);
	});

	start();
});

/* Sticky gallery: release when the summary column is shorter than the gallery. */
register('product', (product) => {
	const col = qs('[data-wg-sticky-col]', product);
	const summary = qs('.wg-product__summary-col', product);
	if (!col || !summary || !cfg.sticky) { if (col) col.classList.add('is-static'); return; }
	const check = () => col.classList.toggle('is-static', window.innerWidth < 992 || summary.offsetHeight <= col.offsetHeight + 40);
	check();
	if ('ResizeObserver' in window) new ResizeObserver(check).observe(summary);
	window.addEventListener('resize', check);

	// Accordions on mobile for stacked sections
	qsa('[data-wg-accordion]', product).forEach((section, i) => {
		const title = qs('.wg-product__section-title', section);
		if (!title) return;
		if (window.innerWidth < 992 && i > 0) section.classList.add('is-collapsed');
		title.addEventListener('click', () => { if (window.innerWidth < 992) section.classList.toggle('is-collapsed'); });
	});

	// Copy link
	product.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-wg-copy]');
		if (!btn || !navigator.clipboard) return;
		navigator.clipboard.writeText(btn.dataset.wgCopy).then(() => { btn.classList.add('is-copied'); setTimeout(() => btn.classList.remove('is-copied'), 1600); });
	});
});

/* Swatches drive the hidden WooCommerce selects. */
register('swatches', (wrap) => {
	const form = wrap.closest('form.variations_form');
	if (!form) return;
	qsa('.wg-swatches__group', wrap).forEach((group) => {
		const select = qs(`select[name="${group.dataset.attribute}"]`, form);
		if (!select) return;
		const current = qs('[data-wg-swatch-current]', group);
		const sync = () => {
			qsa('.wg-swatch', group).forEach((s) => {
				const on = s.dataset.value === select.value;
				s.classList.toggle('is-selected', on);
				s.setAttribute('aria-checked', on ? 'true' : 'false');
				const opt = qsa('option', select).find((o) => o.value === s.dataset.value);
				s.classList.toggle('is-disabled', !opt || opt.disabled);
				if (on && current) current.textContent = s.querySelector('.wg-swatch__name').textContent;
			});
		};
		group.addEventListener('click', (e) => {
			const swatch = e.target.closest('.wg-swatch');
			if (!swatch || swatch.classList.contains('is-disabled')) return;
			select.value = swatch.dataset.value;
			select.dispatchEvent(new Event('change', { bubbles: true }));
			sync();
		});
		select.addEventListener('change', sync);
		sync();
		if (!select.value && qsa('.wg-swatch:not(.is-out-of-stock)', group).length === qsa('.wg-swatch', group).length && qsa('.wg-swatch', group).length === 1) {
			qs('.wg-swatch', group).click();
		}
	});
	// Price and image update from WooCommerce's variation events (bridged in modules/woocommerce.js).
	document.addEventListener('wg:found_variation', (e) => {
		const v = e.detail && e.detail[0];
		if (!v) return;
		const price = qs('[data-wg-product-price]');
		if (price && v.price_html) price.innerHTML = v.price_html;
		const bar = qs('[data-wg-bar-price]');
		if (bar && v.price_html) bar.innerHTML = v.price_html;
		document.dispatchEvent(new CustomEvent('wg:variation-image', { detail: v.image && v.image.src ? { src: v.image.src, full: v.image.full_src } : {} }));
	});
	document.addEventListener('wg:reset_data', () => document.dispatchEvent(new CustomEvent('wg:variation-image', { detail: {} })));
});

/* Quantity stepper */
register('qty', (qty) => {
	const input = qs('input.qty', qty);
	if (!input) return;
	const step = parseFloat(input.step) || 1;
	const clamp = (v) => { const min = parseFloat(input.min) || 0; const max = parseFloat(input.max) || Infinity; return Math.min(max, Math.max(min, v)); };
	qty.addEventListener('click', (e) => {
		const btn = e.target.closest('.wg-qty__btn');
		if (!btn) return;
		const dir = btn.classList.contains('wg-qty__btn--plus') ? 1 : -1;
		input.value = clamp((parseFloat(input.value) || 0) + dir * step);
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
});

/* Mobile sticky bar mirrors the real form buttons. */
register('product-bar', (bar) => {
	const form = qs('form.cart');
	if (!form) return;
	const addBtn = qs('.single_add_to_cart_button', form);
	const buyBtn = qs('.wg-buy-now', form);
	const barBuy = qs('[data-wg-bar-buy]', bar);
	if (buyBtn && barBuy) barBuy.hidden = false;
	qs('[data-wg-bar-cart]', bar).addEventListener('click', () => { if (addBtn && !addBtn.disabled) addBtn.click(); else form.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
	if (barBuy) barBuy.addEventListener('click', () => { if (buyBtn && !buyBtn.disabled) buyBtn.click(); else form.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
	const update = () => { const r = form.getBoundingClientRect(); const show = r.bottom < 0 || r.top > window.innerHeight; bar.hidden = false; bar.classList.toggle('is-visible', show && window.innerWidth < 992); };
	window.addEventListener('scroll', update, { passive: true });
	window.addEventListener('resize', update);
	update();
});
