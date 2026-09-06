/* Webgram admin: field behaviors for the Theme Settings panel, builders and menu item fields. Vanilla JS; jQuery is
 * used only for WordPress' color picker. */
(function () {
	'use strict';

	var cfg = window.webgramAdmin || {};
	var i18n = cfg.i18n || {};

	function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

	/* Color pickers */
	function initColors(root) {
		if (!window.jQuery || !jQuery.fn.wpColorPicker) return;
		qsa('.wg-color', root).forEach(function (input) {
			if (input.dataset.wgColor) return;
			input.dataset.wgColor = '1';
			var $i = jQuery(input);
			$i.wpColorPicker({
				change: function () { setTimeout(function () { input.dispatchEvent(new Event('change', { bubbles: true })); }, 0); },
				clear: function () { input.dispatchEvent(new Event('change', { bubbles: true })); }
			});
		});
	}

	/* Media pickers */
	document.addEventListener('click', function (e) {
		var sel = e.target.closest('.wg-media__select');
		if (sel && window.wp && wp.media) {
			e.preventDefault();
			var wrap = sel.closest('[data-wg-media]');
			var frame = wp.media({ title: i18n.choose || 'Choose image', multiple: false, library: { type: 'image' } });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
				var input = wrap.querySelector('input[type="hidden"]');
				input.value = att.id;
				wrap.querySelector('.wg-media__preview img').src = url;
				wrap.querySelector('.wg-media__preview').hidden = false;
				wrap.querySelector('.wg-media__remove').hidden = false;
				input.dispatchEvent(new Event('change', { bubbles: true }));
			});
			frame.open();
			return;
		}
		var rm = e.target.closest('.wg-media__remove');
		if (rm) {
			e.preventDefault();
			var w = rm.closest('[data-wg-media]');
			var inp = w.querySelector('input[type="hidden"]');
			inp.value = '';
			w.querySelector('.wg-media__preview').hidden = true;
			rm.hidden = true;
			inp.dispatchEvent(new Event('change', { bubbles: true }));
		}
	});

	/* Device tabs */
	document.addEventListener('click', function (e) {
		var tab = e.target.closest('.wg-devices__tab');
		if (!tab) return;
		var wrap = tab.closest('[data-wg-devices]');
		qsa('.wg-devices__tab', wrap).forEach(function (t) { t.classList.toggle('is-active', t === tab); });
		qsa('.wg-devices__pane', wrap).forEach(function (p) { p.classList.toggle('is-active', p.dataset.device === tab.dataset.device); });
	});
	function initDevices(root) {
		qsa('[data-wg-devices]', root).forEach(function (wrap) {
			if (!wrap.querySelector('.wg-devices__pane.is-active')) {
				var first = wrap.querySelector('.wg-devices__pane');
				if (first) first.classList.add('is-active');
			}
		});
	}

	/* Range output */
	document.addEventListener('input', function (e) {
		if (e.target.matches('.wg-range input[type="range"]')) {
			var out = e.target.parentNode.querySelector('output');
			if (out) out.textContent = e.target.value;
		}
	});

	/* Dependencies: data-show-if='["field","==",value]' evaluated within the nearest form or repeater row. */
	function valueOf(scope, name) {
		var inputs = qsa('[data-field="' + name + '"] input, [data-field="' + name + '"] select, [data-field="' + name + '"] textarea', scope);
		if (!inputs.length) return null;
		var checks = inputs.filter(function (i) { return i.type === 'checkbox' || i.type === 'radio'; });
		if (checks.length) {
			var checked = checks.filter(function (i) { return i.checked; });
			if (checks.length === 1 && checks[0].type === 'checkbox') return checks[0].checked;
			return checked.length ? checked[0].value : '';
		}
		return inputs[0].value;
	}
	function applyDeps(root) {
		qsa('[data-show-if]', root).forEach(function (field) {
			var rule;
			try { rule = JSON.parse(field.dataset.showIf); } catch (err) { return; }
			var scope = field.closest('.wg-repeater__row') || field.closest('.wg-builder__panel') || field.closest('form') || document;
			var current = valueOf(scope, rule[0]);
			if (current === null) { field.hidden = false; return; }
			var expected = rule[2];
			var isBool = typeof expected === 'boolean';
			var cur = isBool ? (current === true || current === '1' || current === 'true') : String(current);
			var exp = isBool ? expected : String(expected);
			var match = rule[1] === '!=' ? cur !== exp : cur === exp;
			field.hidden = !match;
		});
	}
	document.addEventListener('change', function () { applyDeps(document); });

	/* Repeater */
	function updateRepeaterTitles(rep) {
		qsa('.wg-repeater__row', rep).forEach(function (row) {
			var first = row.querySelector('.wg-repeater__body input[type="text"], .wg-repeater__body select, .wg-repeater__body textarea');
			var t = row.querySelector('.wg-repeater__title');
			if (first && t && first.value) t.textContent = first.selectedOptions ? first.selectedOptions[0].textContent : first.value;
		});
	}
	function reindex(rep) {
		var rows = qsa(':scope > .wg-repeater__rows > .wg-repeater__row', rep);
		rows.forEach(function (row, i) {
			qsa('[name]', row).forEach(function (input) {
				input.name = input.name.replace(/\[(\d+|__INDEX__)\]/, '[' + i + ']');
			});
		});
	}
	document.addEventListener('click', function (e) {
		var add = e.target.closest('.wg-repeater__add');
		if (add) {
			e.preventDefault();
			var rep = add.closest('[data-wg-repeater]');
			var rows = rep.querySelector(':scope > .wg-repeater__rows');
			var max = parseInt(rep.dataset.max || '50', 10);
			if (rows.children.length >= max) return;
			var tpl = rep.querySelector(':scope > .wg-repeater__template');
			var html = tpl.innerHTML.replace(/__INDEX__/g, String(rows.children.length));
			var frag = document.createElement('div');
			frag.innerHTML = html;
			var row = frag.firstElementChild;
			rows.appendChild(row);
			initAll(row);
			reindex(rep);
			markDirty(rep);
			return;
		}
		var rm = e.target.closest('.wg-repeater__remove');
		if (rm) {
			e.preventDefault();
			if (!window.confirm(i18n.remove || 'Remove this item?')) return;
			var rep2 = rm.closest('[data-wg-repeater]');
			rm.closest('.wg-repeater__row').remove();
			reindex(rep2);
			markDirty(rep2);
			return;
		}
		var tg = e.target.closest('.wg-repeater__toggle');
		if (tg) { e.preventDefault(); tg.closest('.wg-repeater__row').classList.toggle('is-collapsed'); }
	});
	document.addEventListener('input', function (e) {
		var rep = e.target.closest('[data-wg-repeater]');
		if (rep) updateRepeaterTitles(rep);
	});

	/* Drag sorting for sortables and repeater rows (HTML5 DnD) */
	var dragEl = null;
	document.addEventListener('dragstart', function (e) {
		var item = e.target.closest('.wg-sortable__item, .wg-repeater__row');
		if (!item || e.target.closest('.wg-builder__chip')) return;
		dragEl = item;
		item.classList.add('is-dragging');
		e.dataTransfer.effectAllowed = 'move';
		try { e.dataTransfer.setData('text/plain', 'sort'); } catch (err) {}
	});
	document.addEventListener('dragover', function (e) {
		if (!dragEl) return;
		var over = e.target.closest('.wg-sortable__item, .wg-repeater__row');
		if (!over || over === dragEl || over.parentNode !== dragEl.parentNode) return;
		e.preventDefault();
		var rect = over.getBoundingClientRect();
		var after = (e.clientY - rect.top) > rect.height / 2;
		over.parentNode.insertBefore(dragEl, after ? over.nextSibling : over);
	});
	document.addEventListener('dragend', function () {
		if (!dragEl) return;
		dragEl.classList.remove('is-dragging');
		var rep = dragEl.closest('[data-wg-repeater]');
		if (rep) reindex(rep);
		markDirty(dragEl);
		dragEl = null;
	});
	document.addEventListener('change', function (e) {
		var item = e.target.closest('.wg-sortable__item');
		if (item && e.target.type === 'checkbox') item.classList.toggle('is-on', e.target.checked);
	});

	/* Code editor */
	function initCode(root) {
		if (!window.wp || !wp.codeEditor || !cfg.codeEditor) return;
		qsa('[data-wg-code]', root).forEach(function (ta) {
			if (ta.dataset.wgCodeInit) return;
			ta.dataset.wgCodeInit = '1';
			var mode = ta.dataset.wgCode === 'javascript' ? 'javascript' : (ta.dataset.wgCode === 'html' ? 'htmlmixed' : 'css');
			var settings = cfg.codeEditor[mode];
			if (!settings) return;
			var editor = wp.codeEditor.initialize(ta, settings);
			if (editor && editor.codemirror) {
				editor.codemirror.on('change', function () { editor.codemirror.save(); markDirty(ta); });
			}
		});
	}

	/* Unsaved changes guard */
	var dirty = false;
	function markDirty(el) {
		var form = el && el.closest ? el.closest('[data-wg-form]') : null;
		if (!form) return;
		dirty = true;
		var badge = form.querySelector('.wg-settings__unsaved');
		if (badge) badge.hidden = false;
	}
	document.addEventListener('change', function (e) { if (e.target.closest('[data-wg-form]')) markDirty(e.target); });
	document.addEventListener('input', function (e) { if (e.target.closest('[data-wg-form]')) markDirty(e.target); });
	document.addEventListener('submit', function () { dirty = false; });
	window.addEventListener('beforeunload', function (e) {
		if (!dirty) return;
		e.preventDefault();
		e.returnValue = i18n.unsaved || '';
	});
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wg-confirm]');
		if (btn && !window.confirm(i18n.confirm || 'Are you sure?')) e.preventDefault();
	});

	/* Icon select preview */
	document.addEventListener('change', function (e) {
		if (!e.target.matches('.wg-icon-select')) return;
		var preview = e.target.parentNode.querySelector('.wg-icon-preview');
		var any = document.querySelector('.wg-icon-preview svg.wg-icon--' + e.target.value);
		if (preview) preview.innerHTML = any ? any.outerHTML : '';
	});

	function initAll(root) {
		initColors(root);
		initDevices(root);
		initCode(root);
		applyDeps(root);
		qsa('[data-wg-repeater]', root).forEach(updateRepeaterTitles);
	}
	window.webgramAdminInit = initAll;

	document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	/* Appearance > Menus adds items dynamically. */
	if (window.jQuery) {
		jQuery(document).on('menu-item-added', function () { initAll(document); });
	}
})();
