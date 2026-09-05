/**
 * Scroll-snap carousel for section rows (product rows on mobile, categories overflow, testimonials, Instagram).
 * Modes via data-wg-carousel: "mobile" (only below 768px), "snap" (always), "dots" (always, with dot row),
 * "overflow" (arrows only when content overflows). Markup stays a plain grid or flex row; JS adds arrows and dots.
 * data-wg-carousel-autoplay holds a delay in milliseconds; it only advances where the row actually scrolls,
 * so a section that is a grid on desktop and a swipe row on smaller screens auto plays only on the smaller screens.
 */
import { register, qsa } from './dom.js';

const mq = window.matchMedia('(max-width: 767.98px)');
const still = window.matchMedia('(prefers-reduced-motion: reduce)');

register('carousel', (root) => {
	const mode = root.dataset.wgCarousel || 'mobile';
	const track = root.querySelector('.products, .wg-carousel__track, [data-wg-carousel-track]') || root;
	// A row that is its own track gets a wrapper, so the arrows and dots do not scroll along inside it.
	const host = track === root ? document.createElement('div') : root;
	let arrows = null;
	let dots = null;

	const chrome = (el) => el.classList.contains('wg-carousel__arrow') || el.classList.contains('wg-carousel__arrows') || el.classList.contains('wg-carousel__dots');
	const items = () => Array.from(track.children).filter((el) => el.nodeType === 1 && !chrome(el));
	const active = () => mode !== 'mobile' || mq.matches;
	const overflows = () => track.scrollWidth > track.clientWidth + 4;

	const scrollBy = (dir) => {
		const first = items()[0];
		const step = first ? first.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
		track.scrollBy({ left: dir * step * (document.dir === 'rtl' ? -1 : 1), behavior: 'smooth' });
	};

	const build = () => {
		if (arrows) return;
		if (host !== root) {
			host.className = 'wg-carousel wg-carousel--row';
			root.parentNode.insertBefore(host, root);
			host.appendChild(root);
		}
		host.classList.add('wg-carousel');
		arrows = document.createElement('div');
		arrows.className = 'wg-carousel__arrows';
		arrows.innerHTML = '<button type="button" class="wg-carousel__arrow wg-carousel__arrow--prev" aria-label="Previous"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button><button type="button" class="wg-carousel__arrow wg-carousel__arrow--next" aria-label="Next"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></button>';
		host.appendChild(arrows);
		arrows.querySelector('.wg-carousel__arrow--prev').addEventListener('click', () => scrollBy(-1));
		arrows.querySelector('.wg-carousel__arrow--next').addEventListener('click', () => scrollBy(1));
		if (mode === 'dots') {
			dots = document.createElement('div');
			dots.className = 'wg-carousel__dots';
			host.appendChild(dots);
		}
		track.addEventListener('scroll', update, { passive: true });
	};

	const update = () => {
		if (!arrows) return;
		const on = active() && overflows();
		host.classList.toggle('is-carousel', on);
		root.classList.toggle('is-carousel', on);
		arrows.hidden = !on;
		if (dots) {
			const list = items();
			const perView = Math.max(1, Math.round(track.clientWidth / (list[0] ? list[0].getBoundingClientRect().width + 16 : track.clientWidth)));
			const pages = Math.max(1, Math.ceil(list.length / perView));
			const page = Math.min(pages - 1, Math.round(track.scrollLeft / Math.max(1, track.clientWidth)));
			dots.hidden = !on || pages < 2;
			if (dots.children.length !== pages) {
				dots.innerHTML = '';
				for (let i = 0; i < pages; i++) {
					const b = document.createElement('button');
					b.type = 'button';
					b.className = 'wg-carousel__dot';
					b.setAttribute('aria-label', `Go to slide ${i + 1}`);
					b.addEventListener('click', () => track.scrollTo({ left: i * track.clientWidth, behavior: 'smooth' }));
					dots.appendChild(b);
				}
			}
			Array.from(dots.children).forEach((d, i) => d.classList.toggle('is-active', i === page));
		}
		const max = track.scrollWidth - track.clientWidth - 2;
		arrows.querySelector('.wg-carousel__arrow--prev').disabled = Math.abs(track.scrollLeft) < 2;
		arrows.querySelector('.wg-carousel__arrow--next').disabled = Math.abs(track.scrollLeft) >= max;
		if (delay && !timer && canPlay()) play();
		if (delay && timer && !canPlay()) stop();
	};

	const delay = Math.max(0, parseInt(root.dataset.wgCarouselAutoplay || '0', 10));
	let timer = null;
	let held = false;
	let seen = true;

	const canPlay = () => delay > 0 && !held && seen && !still.matches && !document.hidden && active() && overflows();

	const stop = () => { if (timer) { clearInterval(timer); timer = null; } };

	const play = () => {
		stop();
		if (canPlay()) timer = setInterval(step, delay);
	};

	function step() {
		if (!canPlay()) { stop(); return; }
		const end = track.scrollWidth - track.clientWidth - 2;
		if (Math.abs(track.scrollLeft) >= end) {
			track.scrollTo({ left: 0, behavior: 'smooth' });
		} else {
			scrollBy(1);
		}
	}

	const hold = () => { held = true; stop(); };
	const release = () => { held = false; play(); };

	const autoplay = () => {
		if (!delay) return;
		host.addEventListener('pointerenter', hold);
		host.addEventListener('pointerleave', release);
		host.addEventListener('focusin', hold);
		host.addEventListener('focusout', release);
		track.addEventListener('pointerdown', hold);
		track.addEventListener('pointerup', release);
		track.addEventListener('pointercancel', release);
		document.addEventListener('visibilitychange', play);
		still.addEventListener('change', play);
		if ('IntersectionObserver' in window) {
			seen = false;
			new IntersectionObserver((entries) => { seen = entries[0].isIntersecting; play(); }, { threshold: 0.2 }).observe(host);
		}
	};

	build();
	autoplay();
	update();
	window.addEventListener('resize', update, { passive: true });
	mq.addEventListener('change', update);
	qsa('img', track).forEach((img) => { if (!img.complete) img.addEventListener('load', update, { once: true }); });
});
