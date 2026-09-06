/* Webgram Core blocks: registers one server-rendered block per section definition with generic inspector controls. */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !window.webgramCoreBlocks) { return; }
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var ServerSideRender = wp.serverSideRender;
	var C = wp.components;
	var __ = wp.i18n.__;
	var defs = window.webgramCoreBlocks.definitions || {};

	function control(c, id, value, onChange) {
		var label = c.label || id;
		switch (c.type) {
			case 'textarea':
			case 'html':
				return el(C.TextareaControl, { label: label, value: value || '', onChange: onChange, help: c.description });
			case 'number':
				return el(C.RangeControl, { label: label, value: Number(value) || 0, min: c.min !== undefined ? c.min : 0, max: c.max !== undefined ? c.max : 100, step: c.step || 1, onChange: onChange, help: c.description });
			case 'switch':
				return el(C.ToggleControl, { label: label, checked: !!value, onChange: onChange, help: c.description });
			case 'select':
				return el(C.SelectControl, { label: label, value: value, options: Object.keys(c.options || {}).map(function (k) { return { value: k, label: c.options[k] }; }), onChange: onChange, help: c.description });
			case 'image':
				return el('div', { className: 'components-base-control' },
					el('label', { className: 'components-base-control__label' }, label),
					el(MediaUpload, { onSelect: function (media) { onChange(media.id); }, allowedTypes: ['image'], value: value, render: function (obj) {
						return el(C.Button, { variant: 'secondary', onClick: obj.open }, value ? __('Change image', 'webgram-core') : __('Choose image', 'webgram-core'));
					} }),
					value ? el(C.Button, { variant: 'link', isDestructive: true, onClick: function () { onChange(0); } }, __('Remove', 'webgram-core')) : null
				);
			case 'category':
			case 'tag':
				return el(C.TextControl, { label: label, value: Array.isArray(value) ? value.join(',') : (value || ''), onChange: function (v) { onChange(v.split(',').map(function (s) { return s.trim(); }).filter(Boolean)); }, help: c.description || __('Comma separated slugs', 'webgram-core') });
			case 'repeater':
				return repeater(c, label, Array.isArray(value) ? value : [], onChange);
			default:
				return el(C.TextControl, { label: label, value: value === undefined || value === null ? '' : String(value), onChange: onChange, help: c.description, type: c.type === 'url' ? 'url' : 'text' });
		}
	}

	function repeater(c, label, rows, onChange) {
		var fields = c.fields || {};
		var items = rows.map(function (row, i) {
			return el(C.PanelBody, { key: i, title: (label + ' ' + (i + 1)), initialOpen: false },
				Object.keys(fields).map(function (fid) {
					return control(fields[fid], fid, row[fid], function (v) { var next = rows.slice(); next[i] = Object.assign({}, row); next[i][fid] = v; onChange(next); });
				}),
				el(C.Button, { variant: 'link', isDestructive: true, onClick: function () { var next = rows.slice(); next.splice(i, 1); onChange(next); } }, __('Remove', 'webgram-core'))
			);
		});
		return el('div', { className: 'wgc-block-repeater' }, el('p', { className: 'components-base-control__label' }, label), items, (!c.max || rows.length < c.max) ? el(C.Button, { variant: 'secondary', onClick: function () { var blank = {}; Object.keys(fields).forEach(function (fid) { blank[fid] = fields[fid].default; }); onChange(rows.concat([blank])); } }, __('Add item', 'webgram-core')) : null);
	}

	Object.keys(defs).forEach(function (id) {
		var def = defs[id];
		var name = 'webgram/' + id.replace(/_/g, '-');
		wp.blocks.registerBlockType(name, {
			title: def.title,
			description: def.description,
			icon: def.icon || 'layout',
			category: 'webgram',
			edit: function (props) {
				var controls = Object.keys(def.controls || {}).map(function (cid) {
					var c = def.controls[cid];
					return el('div', { key: cid, style: { marginBottom: '12px' } }, control(c, cid, props.attributes[cid], function (v) { var next = {}; next[cid] = v; props.setAttributes(next); }));
				});
				return el(Fragment, null,
					el(InspectorControls, null, el(C.PanelBody, { title: __('Options', 'webgram-core'), initialOpen: true }, controls.length ? controls : el('p', null, __('No options for this block.', 'webgram-core')))),
					el('div', { className: 'wgc-block-preview' }, el(ServerSideRender, { block: name, attributes: props.attributes, EmptyResponsePlaceholder: function () { return el(C.Placeholder, { label: def.title }, __('Nothing to show yet. Check the block options or the module settings.', 'webgram-core')); } }))
				);
			},
			save: function () { return null; }
		});
	});
})(window.wp);
