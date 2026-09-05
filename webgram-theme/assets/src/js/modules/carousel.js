/**
 * Scroll-snap carousel for section rows (product rows on mobile, categories overflow, testimonials, Instagram).
 * Modes via data-wg-carousel: "mobile" (only below 768px), "snap" (always), "dots" (always, with dot row),
 * "overflow" (arrows only when content overflows). Markup stays a plain grid or flex row; JS adds arrows and dots.
 */
import { register, qsa } from './dom.js';

const mq = window.matchMedia('(max-width: 767.98px)');

register('carousel', (root) => {
	const mode = root.dataset.wgCarousel || 'mobile';
	const track = root.querySelector('.products, .wg-carousel__track, [data-wg-carousel-track]') || root;
	let arrows = null;
	let dots = null;

	const items = () => Array.from(track.children).filter((el) => el.nodeType === 1 && !el.classList.contains('wg-carousel__arrow'));
	const active = () => mode !== 'mobile' || mq.matches;
	const overflows = () => track.scrollWidth > track.clientWidth + 4;

	const scrollBy = (dir) => {
		const first = items()[0];
		const step = first ? first.getBoundingClientRect().width + 16 : track.clientWidth * 0.8;
		track.scrollBy({ left: dir * step * (document.dir === 'rtl' ? -1 : 1), behavior: 'smooth' });
	};

	const build = () => {
		if (arrows) return;
		root.classList.add('wg-carousel');
		arrows = document.createElement('div');
		arrows.className = 'wg-carousel__arrows';
		arrows.innerHTML = '<button type="button" class="wg-carousel__arrow wg-carousel__arrow--prev" aria-label="Previous"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button><button type="button" class="wg-carousel__arrow wg-carousel__arrow--next" aria-label="Next"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></button>';
		root.appendChild(arrows);
		arrows.querySelector('.wg-carousel__arrow--prev').addEventListener('click', () => scrollBy(-1));
		arrows.querySelector('.wg-carousel__arrow--next').addEventListener('click', () => scrollBy(1));
		if (mode === 'dots') {
			dots = document.createElement('div');
			dots.className = 'wg-carousel__dots';
			root.appendChild(dots);
		}
		track.addEventListener('scroll', update, { passive: true });
	};

	const update = () => {
		if (!arrows) return;
		const on = active() && overflows();
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
	};

	build();
	update();
	window.addEventListener('resize', update, { passive: true });
	mq.addEventListener('change', update);
	qsa('img', track).forEach((img) => { if (!img.complete) img.addEventListener('load', update, { once: true }); });
});
