import { qs, qsa, register } from './dom.js';

/** Header: writes heights to CSS variables; sticky group gets is-stuck (shadow, shrink) and is-hidden (hide on scroll down). */
register('header', (header) => {
	const cfg = (window.webgramData && window.webgramData.sticky) || {};
	const root = document.documentElement;

	const visiblePane = () => qsa('.wg-header__device', header).find((d) => d.offsetParent !== null) || header;
	const measure = () => {
		const pane = visiblePane();
		const main = qs('.wg-header__row--main', pane);
		const top = qs('.wg-header__row--top', pane);
		root.style.setProperty('--wg-header-height', `${main ? main.offsetHeight : header.offsetHeight}px`);
		root.style.setProperty('--wg-topbar-height', `${top ? top.offsetHeight : 0}px`);
		const adminBar = qs('#wpadminbar');
		root.style.setProperty('--wg-sticky-offset', adminBar && getComputedStyle(adminBar).position === 'fixed' ? `${adminBar.offsetHeight}px` : '0px');
	};
	measure();
	if ('ResizeObserver' in window) new ResizeObserver(measure).observe(header);
	window.addEventListener('resize', measure);

	if (!cfg.enabled) return;
	if (!cfg.shadow) document.body.classList.add('wg-sticky-no-shadow');
	if (cfg.shrink) document.body.classList.add('wg-sticky-shrink');
	if (!cfg.mobile) document.body.classList.add('wg-no-sticky-mobile');

	let lastY = window.scrollY;
	let ticking = false;
	const update = () => {
		const y = window.scrollY;
		qsa('.wg-header__sticky', header).forEach((group) => {
			const offset = parseInt(getComputedStyle(root).getPropertyValue('--wg-sticky-offset'), 10) || 0;
			const stuck = group.getBoundingClientRect().top <= offset + 0.5 && y > 4;
			group.classList.toggle('is-stuck', stuck);
			if (cfg.hideOnScroll) {
				const down = y > lastY && y > group.offsetHeight + 80;
				group.classList.toggle('is-hidden', stuck && down);
			}
		});
		header.classList.toggle('is-stuck', y > 4);
		lastY = y;
		ticking = false;
	};
	window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
	update();
});
