import { qs, qsa, register } from './dom.js';

/** Marquee: duplicates the message set until the track is at least twice the container width, then sets the animation duration from px/s. */
register('marquee', (el) => {
	const track = qs('.wg-marquee__track', el);
	if (!track) return;
	const mode = el.dataset.mode || 'marquee';

	if (mode === 'slide') {
		const items = qsa('.wg-marquee__item', track);
		if (items.length < 2) return;
		let i = 0;
		const interval = Math.max(1000, parseInt(el.dataset.interval || '4000', 10));
		setInterval(() => {
			items[i].classList.remove('is-active');
			i = (i + 1) % items.length;
			items[i].classList.add('is-active');
		}, interval);
		return;
	}
	if (mode !== 'marquee') return;

	const originals = qsa('.wg-marquee__item', track);
	const speed = Math.max(10, parseInt(el.dataset.speed || '50', 10));
	let setWidth = 0;

	const build = () => {
		qsa('.is-clone', track).forEach((c) => c.remove());
		const gap = parseFloat(getComputedStyle(track).gap) || 0;
		setWidth = originals.reduce((w, item) => w + item.offsetWidth + gap, 0);
		if (!setWidth) return;
		const containerWidth = el.offsetWidth;
		// Enough copies so that one full set can scroll out while the track still covers the container.
		const copies = Math.max(1, Math.ceil(containerWidth / setWidth)) + 1;
		for (let c = 0; c < copies; c += 1) {
			originals.forEach((item) => {
				const clone = item.cloneNode(true);
				clone.classList.add('is-clone');
				clone.setAttribute('aria-hidden', 'true');
				if (clone.tagName === 'A') clone.setAttribute('tabindex', '-1');
				track.appendChild(clone);
			});
		}
		track.style.setProperty('--wg-marquee-half', `${setWidth}px`);
		el.style.setProperty('--wg-marquee-duration', `${setWidth / speed}s`);
	};
	build();
	let timer;
	window.addEventListener('resize', () => { clearTimeout(timer); timer = setTimeout(build, 150); });
	if (document.fonts && document.fonts.ready) document.fonts.ready.then(build);
});
