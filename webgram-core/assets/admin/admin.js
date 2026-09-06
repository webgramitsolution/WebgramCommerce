/* Webgram Core admin: media picker for image fields, color picker when available. */
(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.wgc-image-select');
		if (btn && window.wp && wp.media) {
			e.preventDefault();
			var wrap = btn.closest('.wgc-image-field');
			var frame = wp.media({ title: btn.textContent, multiple: false, library: { type: 'image' } });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
				wrap.querySelector('input[type="hidden"]').value = att.id;
				var img = wrap.querySelector('img');
				img.src = url;
				img.hidden = false;
				wrap.querySelector('.wgc-image-remove').hidden = false;
			});
			frame.open();
			return;
		}
		var rm = e.target.closest('.wgc-image-remove');
		if (rm) {
			e.preventDefault();
			var w = rm.closest('.wgc-image-field');
			w.querySelector('input[type="hidden"]').value = '';
			w.querySelector('img').hidden = true;
			rm.hidden = true;
		}
	});

	if (window.jQuery && jQuery.fn.wpColorPicker) {
		jQuery('.wgc-color-field').wpColorPicker();
	}
})();
/* Simple table repeater used by the product panel (add / remove rows, reindex names). */
(function () {
	'use strict';
	document.addEventListener('click', function (e) {
		var add = e.target.closest('.wgc-row-add');
		if (add) {
			e.preventDefault();
			var table = add.closest('.options_group').querySelector('[data-wgc-repeater] tbody');
			var last = table.lastElementChild;
			var row = last.cloneNode(true);
			var idx = table.children.length;
			row.querySelectorAll('input').forEach(function (i) { i.value = ''; i.name = i.name.replace(/\[\d+\]/, '[' + idx + ']'); });
			table.appendChild(row);
			return;
		}
		var rm = e.target.closest('.wgc-row-remove');
		if (rm) {
			e.preventDefault();
			var tbody = rm.closest('tbody');
			if (tbody.children.length > 1) rm.closest('tr').remove(); else rm.closest('tr').querySelectorAll('input').forEach(function (i) { i.value = ''; });
		}
	});
})();
/* Slider slides repeater: add, remove, reorder, collapse; media buttons reuse the image-field handler above. */
(function () {
	'use strict';
	var wrap = document.querySelector('[data-wgc-slides]');
	if (!wrap) { return; }
	var list = wrap.querySelector('[data-wgc-slides-list]');
	var tpl = wrap.querySelector('[data-wgc-slide-template]');
	var max = parseInt(wrap.getAttribute('data-max'), 10) || 12;
	function reindex() {
		Array.prototype.forEach.call(list.children, function (row, i) {
			row.querySelectorAll('[name]').forEach(function (f) { f.name = f.name.replace(/wg_slides\[\d+\]/, 'wg_slides[' + i + ']'); });
			var idx = row.querySelector('[data-wgc-slide-index]');
			if (idx) { idx.textContent = i + 1; }
		});
	}
	function initColors(row) {
		if (window.jQuery && jQuery.fn.wpColorPicker) { jQuery(row).find('.wgc-color-field').wpColorPicker(); }
	}
	wrap.addEventListener('click', function (e) {
		if (e.target.closest('[data-wgc-slide-add]')) {
			e.preventDefault();
			if (list.children.length >= max) { return; }
			var node = tpl.content.firstElementChild.cloneNode(true);
			list.appendChild(node);
			reindex();
			initColors(node);
			return;
		}
		var rm = e.target.closest('[data-wgc-slide-remove]');
		if (rm) { e.preventDefault(); if (window.confirm(rm.getAttribute('aria-label') + '?')) { rm.closest('[data-wgc-slide]').remove(); reindex(); } return; }
		var mv = e.target.closest('[data-wgc-slide-move]');
		if (mv) {
			e.preventDefault();
			var row = mv.closest('[data-wgc-slide]');
			if (mv.getAttribute('data-wgc-slide-move') === 'up' && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); }
			if (mv.getAttribute('data-wgc-slide-move') === 'down' && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); }
			reindex();
			return;
		}
		var tg = e.target.closest('[data-wgc-slide-toggle]');
		if (tg) { e.preventDefault(); tg.closest('[data-wgc-slide]').classList.toggle('is-collapsed'); }
	});
})();
/* Reel video picker (media library, video type). */
(function () {
	'use strict';
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.wgc-video-select');
		if (btn && window.wp && wp.media) {
			e.preventDefault();
			var wrap = btn.closest('.wgc-video-field');
			var frame = wp.media({ title: btn.textContent, multiple: false, library: { type: 'video' } });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				wrap.querySelector('input[type="hidden"]').value = att.id;
				var name = wrap.querySelector('.wgc-video-field__name');
				name.textContent = att.filename || att.url;
				name.hidden = false;
				wrap.querySelector('.wgc-video-remove').hidden = false;
			});
			frame.open();
			return;
		}
		var rm = e.target.closest('.wgc-video-remove');
		if (rm) {
			e.preventDefault();
			var w = rm.closest('.wgc-video-field');
			w.querySelector('input[type="hidden"]').value = '';
			w.querySelector('.wgc-video-field__name').hidden = true;
			rm.hidden = true;
		}
	});
})();
