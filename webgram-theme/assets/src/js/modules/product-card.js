import { qs, qsa, register, emit } from './dom.js';

/**
 * Product card: hover slideshow (gallery images load on first hover, never on page load), dot row on touch,
 * variation chips that update price, image and add-to-cart target without a reload.
 */
register('product-card', (card) => {
	const media = qs('.wg-card__media', card);
	const mainImg = qs('.wg-card__img', card);
	let gallery = [];
	try { gallery = JSON.parse(card.dataset.gallery || '[]'); } catch (e) { gallery = []; }

	// Hover slideshow / swap
	if (media && mainImg && gallery.length) {
		const interval = Math.max(500, parseInt(card.dataset.interval || '1200', 10));
		const slideshow = card.classList.contains('wg-card--hover-slideshow');
		let built = false;
		let slides = [];
		let index = -1;
		let timer = null;
		const dots = qs('.wg-card__dots', card);

		const build = () => {
			if (built) return;
			built = true;
			gallery.forEach((src, i) => {
				const img = document.createElement('img');
				img.src = src;
				img.alt = '';
				img.className = 'wg-card__slide';
				img.decoding = 'async';
				media.appendChild(img);
				slides.push(img);
				if (dots) { const d = document.createElement('span'); dots.appendChild(d); }
			});
			if (dots) { const d = document.createElement('span'); d.className = 'is-active'; dots.insertBefore(d, dots.firstChild); }
		};
		const show = (i) => {
			slides.forEach((s, k) => s.classList.toggle('is-active', k === i));
			if (dots) qsa('span', dots).forEach((d, k) => d.classList.toggle('is-active', k === i + 1));
			index = i;
		};
		const stop = () => { clearTimeout(timer); timer = null; show(-1); };
		const start = () => {
			build();
			if (!slides.length) return;
			show(0);
			if (!slideshow) return;
			// Cycle gallery images, then back to the main image (-1), while hovering.
			timer = setTimeout(function cycle() { show(index + 1 >= slides.length ? -1 : index + 1); timer = setTimeout(cycle, interval); }, interval);
		};
		media.addEventListener('mouseenter', start);
		media.addEventListener('mouseleave', stop);
		// Touch: swipe left/right through the dots
		let x0 = null;
		media.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; build(); }, { passive: true });
		media.addEventListener('touchend', (e) => {
			if (x0 === null || !slides.length) return;
			const dx = e.changedTouches[0].clientX - x0;
			x0 = null;
			if (Math.abs(dx) < 30) return;
			const next = dx < 0 ? (index + 1 >= slides.length ? -1 : index + 1) : (index - 1 < -1 ? slides.length - 1 : index - 1);
			show(next);
		}, { passive: true });
	}

	// Variation chips
	const chips = qs('[data-wg-chips]', card);
	const dataEl = qs('[data-wg-variations]', card);
	if (!chips || !dataEl) return;
	let variations = [];
	try { variations = JSON.parse(dataEl.textContent || '[]'); } catch (e) { return; }
	const attribute = chips.dataset.attribute;
	const price = qs('[data-wg-price]', card);
	const cartBtn = qs('.wg-card__cart', card);
	const buyBtn = qs('.wg-card__buy', card);
	const originalPrice = price ? price.innerHTML : '';
	const originalSrc = mainImg ? mainImg.getAttribute('src') : '';
	const originalSrcset = mainImg ? mainImg.getAttribute('srcset') : '';
	const cartHref = cartBtn ? cartBtn.getAttribute('href') : '';

	const findVariation = (value) => variations.find((v) => v.attrs && (v.attrs[attribute] === value || v.attrs[attribute] === ''));

	const apply = (value) => {
		const v = findVariation(value);
		qsa('.wg-chip', chips).forEach((c) => {
			const on = c.dataset.value === value;
			c.classList.toggle('is-selected', on);
			c.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		if (!v) return;
		if (price) price.innerHTML = v.price || originalPrice;
		if (mainImg && v.image) { mainImg.src = v.image; mainImg.removeAttribute('srcset'); }
		else if (mainImg) { mainImg.src = originalSrc; if (originalSrcset) mainImg.setAttribute('srcset', originalSrcset); }
		if (cartBtn) {
			// Turn the "Select options" link into an AJAX add-to-cart for the chosen variation. WooCommerce accepts a
			// variation id as product_id and resolves the parent itself.
			cartBtn.classList.add('ajax_add_to_cart', 'add_to_cart_button');
			cartBtn.classList.remove('product_type_variable');
			cartBtn.dataset.product_id = String(v.id);
			cartBtn.dataset.variation_id = String(v.id);
			cartBtn.dataset.quantity = '1';
			Object.keys(v.attrs || {}).forEach((k) => { cartBtn.dataset[k] = v.attrs[k]; });
			cartBtn.setAttribute('href', v.stock ? '?add-to-cart=' + v.id : cartHref);
			cartBtn.classList.toggle('disabled', !v.stock);
			cartBtn.setAttribute('aria-disabled', v.stock ? 'false' : 'true');
		}
		if (buyBtn) {
			buyBtn.dataset.variationId = String(v.id);
			if (buyBtn.dataset.buyNowUrl) {
				const url = new URL(buyBtn.dataset.buyNowUrl, window.location.origin);
				url.searchParams.set('variation_id', String(v.id));
				Object.keys(v.attrs || {}).forEach((k) => url.searchParams.set(k, v.attrs[k]));
				buyBtn.href = url.toString();
			}
		}
		qsa('.wg-chip[data-value]', chips).forEach((c) => {
			const cv = findVariation(c.dataset.value);
			c.classList.toggle('is-unavailable', !!cv && !cv.stock);
		});
		emit('variation-selected', { card, variation: v });
	};

	chips.addEventListener('click', (e) => {
		const chip = e.target.closest('.wg-chip[data-value]');
		if (!chip) return;
		e.preventDefault();
		apply(chip.dataset.value);
	});
	const first = qs('.wg-chip.is-selected[data-value]', chips) || qs('.wg-chip[data-value]', chips);
	if (first) apply(first.dataset.value);
});
