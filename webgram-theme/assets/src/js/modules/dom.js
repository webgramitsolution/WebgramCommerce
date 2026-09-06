export const qs = (sel, ctx = document) => ctx.querySelector(sel);
export const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

/** Component registry: modules register an init(el) for a data-wg-component name; scan() runs it once per element. */
const registry = new Map();
export function register(name, init) { registry.set(name, init); }
export function scan(root = document) {
	qsa('[data-wg-component]', root).forEach((el) => {
		if (el.dataset.wgInit === '1') return;
		const init = registry.get(el.dataset.wgComponent);
		if (init) { el.dataset.wgInit = '1'; init(el); }
	});
}
export function onContentUpdated(fn) { document.addEventListener('wg:content-updated', fn); }
export function emit(name, detail) { document.dispatchEvent(new CustomEvent('wg:' + name, { detail })); }
