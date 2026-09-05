/* Theme fallback block editor script (used only when Webgram Core is not active). Same generic inspector as Core. */
(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !window.webgramCoreBlocks) return;
	const el = wp.element.createElement;
	const { InspectorControls, MediaUpload } = wp.blockEditor;
	const SSR = wp.serverSideRender;
	const C = wp.components;
	const __ = wp.i18n.__;
	const defs = window.webgramCoreBlocks.definitions || {};

	const control = (c, id, value, onChange) => {
		const label = c.label || id;
		switch (c.type) {
			case 'textarea': return el(C.TextareaControl, { label, value: value || '', onChange });
			case 'number': return el(C.RangeControl, { label, value: Number(value) || 0, min: c.min ?? 0, max: c.max ?? 100, onChange });
			case 'switch': return el(C.ToggleControl, { label, checked: !!value, onChange });
			case 'select': return el(C.SelectControl, { label, value, options: Object.keys(c.options || {}).map((k) => ({ value: k, label: c.options[k] })), onChange });
			case 'image': return el('div', { className: 'components-base-control' }, el('label', { className: 'components-base-control__label' }, label), el(MediaUpload, { onSelect: (m) => onChange(m.id), allowedTypes: ['image'], value, render: (o) => el(C.Button, { variant: 'secondary', onClick: o.open }, value ? __('Change image', 'webgram') : __('Choose image', 'webgram')) }));
			case 'repeater': {
				const rows = Array.isArray(value) ? value : [];
				const fields = c.fields || {};
				return el('div', null, el('p', { className: 'components-base-control__label' }, label),
					rows.map((row, i) => el(C.PanelBody, { key: i, title: `${label} ${i + 1}`, initialOpen: false },
						Object.keys(fields).map((fid) => control(fields[fid], fid, row[fid], (v) => { const next = rows.slice(); next[i] = { ...row, [fid]: v }; onChange(next); })),
						el(C.Button, { variant: 'link', isDestructive: true, onClick: () => { const next = rows.slice(); next.splice(i, 1); onChange(next); } }, __('Remove', 'webgram')))),
					(!c.max || rows.length < c.max) ? el(C.Button, { variant: 'secondary', onClick: () => { const blank = {}; Object.keys(fields).forEach((f) => { blank[f] = fields[f].default; }); onChange(rows.concat([blank])); } }, __('Add item', 'webgram')) : null);
			}
			default: return el(C.TextControl, { label, value: value == null ? '' : String(value), onChange });
		}
	};

	Object.keys(defs).forEach((id) => {
		const def = defs[id];
		const name = 'webgram/' + id.replace(/_/g, '-');
		wp.blocks.registerBlockType(name, {
			title: def.title, icon: 'layout', category: 'webgram',
			edit: (props) => el(wp.element.Fragment, null,
				el(InspectorControls, null, el(C.PanelBody, { title: __('Options', 'webgram'), initialOpen: true },
					Object.keys(def.controls || {}).map((cid) => el('div', { key: cid, style: { marginBottom: '12px' } }, control(def.controls[cid], cid, props.attributes[cid], (v) => props.setAttributes({ [cid]: v })))))),
				el(SSR, { block: name, attributes: props.attributes })),
			save: () => null,
		});
	});
})(window.wp);
