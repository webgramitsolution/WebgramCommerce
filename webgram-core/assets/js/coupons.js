/* Webgram Core coupons: copy code with toast. */
(function () {
	'use strict';
	function toast(text) {
		var el = document.createElement('div');
		el.className = 'wgc-toast wg-toast';
		el.textContent = text;
		document.body.appendChild(el);
		requestAnimationFrame(function () { el.classList.add('is-visible'); });
		setTimeout(function () { el.classList.remove('is-visible'); setTimeout(function () { el.remove(); }, 300); }, 1800);
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-wgc-copy]');
		if (!btn) return;
		var code = btn.dataset.wgcCopy;
		var done = function () { toast(btn.dataset.copied || 'Code copied'); btn.classList.add('is-copied'); setTimeout(function () { btn.classList.remove('is-copied'); }, 1500); };
		if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(code).then(done).catch(done);
		else { var ta = document.createElement('textarea'); ta.value = code; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch (err) {} ta.remove(); done(); }
	});
	if (window.WebgramCore) window.WebgramCore.toast = toast;
})();
