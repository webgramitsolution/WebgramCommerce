/* Webgram Core WooCommerce Enhancements: location picker modal and pincode resolution. No jQuery. */
(function () {
	'use strict';

	var core = window.WebgramCore;
	if (!core) return;
	var cfg = (core.config && core.config.location) || (window.webgramData && window.webgramData.location) || {};

	var modal = document.querySelector('[data-wgc-location-modal]');
	if (!modal) return;
	var form = modal.querySelector('[data-wgc-location-form]');
	var input = form && form.querySelector('[name="pincode"]');
	var message = modal.querySelector('[data-wgc-location-message]');
	var geoBtn = modal.querySelector('[data-wgc-location-geo]');
	var lastFocus = null;

	function open() {
		lastFocus = document.activeElement;
		modal.hidden = false;
		document.body.classList.add('wgc-modal-open');
		if (input) setTimeout(function () { input.focus(); input.select(); }, 50);
	}
	function close() {
		modal.hidden = true;
		document.body.classList.remove('wgc-modal-open');
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	}
	function setMessage(text, ok) {
		if (!message) return;
		message.textContent = text || '';
		message.classList.toggle('is-ok', !!ok);
		message.classList.toggle('is-error', !ok && !!text);
	}
	function applied(data) {
		var label = data.label || (data.location && data.location.pincode) || '';
		document.querySelectorAll('[data-wgc-location-value]').forEach(function (el) { el.textContent = label || cfg.placeholder || ''; });
		document.querySelectorAll('[data-wgc-location-open]').forEach(function (el) { el.classList.toggle('has-value', !!label); });
		document.querySelectorAll('[data-wgc-pincode-input]').forEach(function (el) { if (data.location) el.value = data.location.pincode; });
		core.emit('location-changed', data);
		setMessage(data.message, data.deliverable);
		setTimeout(close, data.deliverable ? 700 : 2200);
	}

	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-wgc-location-open]')) { e.preventDefault(); open(); return; }
		if (e.target.closest('[data-wgc-location-close]')) { e.preventDefault(); close(); }
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var value = input.value.trim();
			if (!value) return;
			setMessage('...', true);
			core.ajax('location_resolve', { pincode: value })
				.then(function (json) { applied(json.data); })
				.catch(function (err) { setMessage(err.message, false); });
		});
	}

	if (geoBtn) {
		if (!('geolocation' in navigator)) { geoBtn.hidden = true; }
		geoBtn.addEventListener('click', function () {
			geoBtn.disabled = true;
			setMessage('...', true);
			navigator.geolocation.getCurrentPosition(function (pos) {
				core.ajax('location_geocode', { lat: pos.coords.latitude, lng: pos.coords.longitude })
					.then(function (json) { if (input && json.data.location) input.value = json.data.location.pincode; applied(json.data); })
					.catch(function (err) { setMessage(err.message, false); })
					.then(function () { geoBtn.disabled = false; });
			}, function () {
				geoBtn.disabled = false;
				setMessage(geoBtn.dataset.denied || 'Location access was denied. Please enter your pincode.', false);
			}, { timeout: 10000, maximumAge: 300000 });
		});
	}
})();
