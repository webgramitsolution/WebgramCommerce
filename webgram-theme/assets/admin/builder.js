/* Webgram header and footer builder: drag elements from the palette into row areas, reorder, remove, open settings
 * panels, apply presets, serialize to JSON on save. Vanilla JS. */
(function () {
	'use strict';

	var root = document.querySelector('[data-wg-builder]');
	if (!root) return;
	var cfg = JSON.parse(root.dataset.wgBuilder);
	var form = root.querySelector('[data-wg-builder-form]');
	var jsonInput = root.querySelector('[data-wg-layout-json]');
	var panels = root.querySelector('.wg-builder__panels');
	var palette = root.querySelector('[data-wg-palette]');
	var dragId = null;
	var dragChip = null;

	function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
	function area(device, row, name) { return root.querySelector('.wg-builder__area[data-device="' + device + '"][data-row="' + row + '"][data-area="' + name + '"]'); }

	function chip(id) {
		var def = cfg.elements[id];
		if (!def) return null;
		var el = document.createElement('div');
		el.className = 'wg-builder__chip wg-builder__chip--placed';
		el.draggable = true;
		el.dataset.element = id;
		var pal = palette.querySelector('.wg-builder__chip[data-element="' + id + '"] svg');
		el.innerHTML = (pal ? pal.outerHTML : '') + '<span>' + def.label + '</span><button type="button" class="wg-builder__chip-remove" aria-label="' + cfg.i18n.remove + '">&times;</button>';
		return el;
	}

	function render() {
		Object.keys(cfg.areas).forEach(function (device) {
			Object.keys(cfg.areas[device]).forEach(function (row) {
				Object.keys(cfg.areas[device][row]).forEach(function (name) {
					var a = area(device, row, name);
					if (!a) return;
					qsa('.wg-builder__chip', a).forEach(function (c) { c.remove(); });
					cfg.areas[device][row][name].forEach(function (id) {
						var c = chip(id);
						if (c) a.appendChild(c);
					});
				});
			});
		});
		updatePalette();
		updateColumns();
	}

	/* Each element may appear once per device. */
	function placedIn(device) {
		var ids = [];
		qsa('.wg-builder__area[data-device="' + device + '"] .wg-builder__chip', root).forEach(function (c) { ids.push(c.dataset.element); });
		return ids;
	}
	function activeDevice() {
		var pane = root.querySelector('.wg-builder__device-pane.is-active');
		return pane ? pane.dataset.devicePane : 'desktop';
	}
	function updatePalette() {
		var used = placedIn(activeDevice());
		qsa('.wg-builder__chip', palette).forEach(function (c) {
			var unavailable = c.classList.contains('is-unavailable');
			c.classList.toggle('is-placed', used.indexOf(c.dataset.element) !== -1);
			c.style.opacity = (unavailable || used.indexOf(c.dataset.element) !== -1) ? '.45' : '';
			c.draggable = !unavailable && used.indexOf(c.dataset.element) === -1;
		});
	}
	function updateColumns() {
		var sel = root.querySelector('[data-wg-columns]');
		if (!sel) return;
		var n = parseInt(sel.value, 10);
		qsa('.wg-builder__area[data-row="widgets"]', root).forEach(function (a) {
			var idx = parseInt(a.dataset.area.replace('col_', ''), 10);
			a.hidden = idx > n;
		});
	}

	function serialize() {
		var out = {};
		qsa('.wg-builder__area', root).forEach(function (a) {
			var d = a.dataset.device, r = a.dataset.row, n = a.dataset.area;
			out[d] = out[d] || {};
			out[d][r] = out[d][r] || {};
			out[d][r][n] = qsa('.wg-builder__chip', a).map(function (c) { return c.dataset.element; });
		});
		return out;
	}

	/* Drag and drop */
	root.addEventListener('dragstart', function (e) {
		var c = e.target.closest('.wg-builder__chip');
		if (!c || c.draggable === false) return;
		dragId = c.dataset.element;
		dragChip = c.classList.contains('wg-builder__chip--placed') ? c : null;
		c.classList.add('is-dragging');
		e.dataTransfer.effectAllowed = 'move';
		try { e.dataTransfer.setData('text/plain', dragId); } catch (err) {}
	});
	root.addEventListener('dragend', function (e) {
		var c = e.target.closest('.wg-builder__chip');
		if (c) c.classList.remove('is-dragging');
		qsa('.wg-builder__area.is-over', root).forEach(function (a) { a.classList.remove('is-over'); });
		dragId = null; dragChip = null;
	});
	root.addEventListener('dragover', function (e) {
		var a = e.target.closest('.wg-builder__area');
		if (!a || !dragId) return;
		if (dragChip && dragChip.closest('.wg-builder__area').dataset.device !== a.dataset.device) return;
		if (!dragChip && placedIn(a.dataset.device).indexOf(dragId) !== -1) return;
		e.preventDefault();
		a.classList.add('is-over');
		var over = e.target.closest('.wg-builder__chip--placed');
		var moving = dragChip || chip(dragId);
		if (!moving) return;
		if (!dragChip) { dragChip = moving; }
		if (over && over !== moving) {
			var rect = over.getBoundingClientRect();
			var after = (e.clientX - rect.left) > rect.width / 2;
			a.insertBefore(moving, after ? over.nextSibling : over);
		} else if (!moving.parentNode || moving.parentNode !== a) {
			a.appendChild(moving);
		}
	});
	root.addEventListener('dragleave', function (e) {
		var a = e.target.closest('.wg-builder__area');
		if (a) a.classList.remove('is-over');
	});
	root.addEventListener('drop', function (e) {
		var a = e.target.closest('.wg-builder__area');
		if (!a) return;
		e.preventDefault();
		a.classList.remove('is-over');
		updatePalette();
		dirty();
	});

	/* Click: remove, open settings, device tabs, row toggle, panels */
	root.addEventListener('click', function (e) {
		var rm = e.target.closest('.wg-builder__chip-remove');
		if (rm) { e.preventDefault(); rm.closest('.wg-builder__chip').remove(); updatePalette(); dirty(); return; }
		var placed = e.target.closest('.wg-builder__chip--placed');
		if (placed) { openPanel('el-' + placed.dataset.element); return; }
		var pal = e.target.closest('[data-wg-palette] .wg-builder__chip');
		if (pal && !pal.classList.contains('is-unavailable')) { openPanel('el-' + pal.dataset.element); return; }
		var dev = e.target.closest('.wg-builder__device');
		if (dev) {
			qsa('.wg-builder__device', root).forEach(function (d) { d.classList.toggle('is-active', d === dev); });
			qsa('.wg-builder__device-pane', root).forEach(function (p) { p.classList.toggle('is-active', p.dataset.devicePane === dev.dataset.device); });
			updatePalette();
			return;
		}
		var open = e.target.closest('[data-wg-open-panel]');
		if (open) { e.preventDefault(); openPanel(open.dataset.wgOpenPanel); return; }
		var close = e.target.closest('[data-wg-close-panel]');
		if (close) { e.preventDefault(); closePanels(); return; }
		var apply = e.target.closest('[data-wg-apply-preset]');
		if (apply) { e.preventDefault(); applyPreset(); }
	});
	root.addEventListener('change', function (e) {
		if (e.target.matches('[data-wg-row-toggle]')) e.target.closest('.wg-builder__row').classList.toggle('is-disabled', !e.target.checked);
		if (e.target.matches('[data-wg-columns]')) updateColumns();
	});

	function openPanel(key) {
		closePanels();
		var p = panels.querySelector('[data-panel="' + key + '"]');
		if (!p) return;
		p.hidden = false;
		panels.setAttribute('data-open', key);
		if (window.webgramAdminInit) window.webgramAdminInit(p);
	}
	function closePanels() {
		qsa('.wg-builder__panel', panels).forEach(function (p) { p.hidden = true; });
		panels.removeAttribute('data-open');
	}

	function applyPreset() {
		var sel = root.querySelector('[data-wg-preset]');
		var preset = sel && cfg.presets[sel.value];
		if (!preset) return;
		if (!window.confirm(cfg.i18n.applyPreset)) return;
		var L = preset.layout;
		if (cfg.context === 'header') {
			Object.keys(cfg.areas).forEach(function (device) {
				Object.keys(cfg.areas[device]).forEach(function (row) {
					Object.keys(cfg.areas[device][row]).forEach(function (name) {
						cfg.areas[device][row][name] = (L[device] && L[device][row] && L[device][row][name]) ? L[device][row][name].slice() : [];
					});
					var toggle = root.querySelector('.wg-builder__area[data-device="' + device + '"][data-row="' + row + '"]');
					var rowEl = toggle && toggle.closest('.wg-builder__row');
					var enabled = !(L[device] && L[device][row] && L[device][row].settings && L[device][row].settings.enabled === false);
					if (rowEl) {
						var cb = rowEl.querySelector('[data-wg-row-toggle]');
						if (cb) { cb.checked = enabled; }
						rowEl.classList.toggle('is-disabled', !enabled);
					}
				});
			});
		} else {
			Object.keys(cfg.areas.desktop.widgets).forEach(function (col) { cfg.areas.desktop.widgets[col] = (L.widgets.areas[col] || []).slice(); });
			Object.keys(cfg.areas.desktop.bottom).forEach(function (n) { cfg.areas.desktop.bottom[n] = (L.bottom[n] || []).slice(); });
			var colSel = root.querySelector('[data-wg-columns]');
			if (colSel) colSel.value = String(L.widgets.columns);
		}
		render();
		dirty();
	}

	function dirty() {
		var badge = form.querySelector('.wg-settings__unsaved');
		if (badge) badge.hidden = false;
		form.dispatchEvent(new Event('change', { bubbles: true }));
	}

	form.addEventListener('submit', function () {
		jsonInput.value = JSON.stringify(serialize());
	});

	render();
})();
