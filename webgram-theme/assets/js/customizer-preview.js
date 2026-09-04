/* Live preview: updates CSS variables for token settings without a page refresh. */
(function (api) {
	'use strict';
	if (!api) return;
	var map = window.webgramTokenMap || {};
	var root = document.documentElement;

	Object.keys(map).forEach(function (setting) {
		api(setting, function (value) {
			value.bind(function (v) {
				var suffix = (setting === 'container_width' || setting === 'button_radius') ? 'px' : '';
				root.style.setProperty(map[setting], v + suffix);
			});
		});
	});

	api('font_size_base', function (v) { v.bind(function (px) { root.style.setProperty('--wg-font-size-base', px + 'px'); }); });
	api('heading_weight', function (v) { v.bind(function (w) { root.style.setProperty('--wg-fw-heading', w); }); });
	api('topbar_text', function (v) { v.bind(function (t) { var el = document.querySelector('.wg-topbar__text'); if (el) el.textContent = t; }); });
	api('footer_copyright', function (v) { v.bind(function (t) { var el = document.querySelector('.wg-footer__copyright'); if (el) el.textContent = t; }); });
	api('blogname', function (v) { v.bind(function (t) { var el = document.querySelector('.wg-header__title'); if (el) el.textContent = t; }); });
})(window.wp && window.wp.customize);
