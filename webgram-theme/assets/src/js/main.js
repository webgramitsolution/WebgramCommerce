/* Webgram Theme main bundle. Vanilla ES modules, bundled by esbuild. */
import { scan, onContentUpdated } from './modules/dom.js';
import './modules/drawer.js';
import './modules/header.js';
import './modules/marquee.js';
import './modules/menu.js';
import './modules/search.js';
import './modules/misc.js';
import './modules/product-card.js';
import './modules/carousel.js';
import { bridgeWooEvents } from './modules/woocommerce.js';

const boot = () => {
	scan();
	bridgeWooEvents();
	onContentUpdated((e) => scan(e.detail && e.detail.root ? e.detail.root : document));
	document.documentElement.classList.add('wg-js');
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
