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
