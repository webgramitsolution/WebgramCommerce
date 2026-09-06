/* Webgram Core slider: boots Swiper on [data-wgc-slider] with the JSON config printed by the renderer. */
(function () {
	'use strict';

	function init(root) {
		(root || document).querySelectorAll('[data-wgc-slider]').forEach(function (el) {
			if (el._wgcSwiper || typeof window.Swiper !== 'function') { return; }
			var cfg = {};
			try { cfg = JSON.parse(el.getAttribute('data-wgc-slider') || '{}'); } catch (err) { cfg = {}; }
			var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var options = {
				effect: cfg.effect === 'fade' ? 'fade' : 'slide',
				fadeEffect: { crossFade: true },
				loop: !!cfg.loop,
				speed: cfg.speed || 700,
				autoplay: cfg.autoplay && !reduced ? cfg.autoplay : false,
				a11y: { enabled: true },
				keyboard: { enabled: true, onlyInViewport: true },
				watchOverflow: true,
				on: {
					init: function (sw) { animate(sw); },
					slideChangeTransitionStart: function (sw) { animate(sw); }
				}
			};
			if (cfg.pagination) { options.pagination = { el: el.querySelector('.swiper-pagination'), clickable: true }; }
			if (cfg.navigation) { options.navigation = { nextEl: el.querySelector('.swiper-button-next'), prevEl: el.querySelector('.swiper-button-prev') }; }
			el._wgcSwiper = new window.Swiper(el, options);
		});
	}

	function animate(sw) {
		sw.slides.forEach(function (slide) { slide.classList.remove('is-animated'); });
		var active = sw.slides[sw.activeIndex];
		if (active) { requestAnimationFrame(function () { active.classList.add('is-animated'); }); }
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }
	document.addEventListener('wg:content-updated', function () { init(); });
	if (window.jQuery) { jQuery(window).on('elementor/frontend/init', function () { setTimeout(function () { init(); }, 0); }); }
})();
