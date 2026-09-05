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

/* Pincode checker on the product page, bulk inquiry modal, track order. */
(function () {
	'use strict';
	var core = window.WebgramCore;
	if (!core) return;

	function setMsg(el, text, ok) { if (!el) return; el.textContent = text || ''; el.classList.toggle('is-ok', !!ok && !!text); el.classList.toggle('is-error', !ok && !!text); }

	/* Pincode check */
	document.querySelectorAll('[data-wgc-pincode]').forEach(function (box) {
		var form = box.querySelector('[data-wgc-pincode-form]');
		var input = box.querySelector('[data-wgc-pincode-input]');
		var result = box.querySelector('[data-wgc-pincode-result]');
		var check = function () {
			var value = input.value.trim();
			if (!value) return;
			setMsg(result, '...', true);
			core.ajax('pincode_check', { pincode: value, product_id: box.dataset.productId || 0 })
				.then(function (json) { setMsg(result, json.data.message, json.data.deliverable); })
				.catch(function (err) { setMsg(result, err.message, false); });
		};
		form.addEventListener('submit', function (e) { e.preventDefault(); check(); });
		if (input.value) check();
		core.on('location-changed', function (data) { if (data && data.location) { input.value = data.location.pincode; check(); } });
	});

	/* Bulk inquiry */
	var bulkModal = document.querySelector('[data-wgc-bulk-modal]');
	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-wgc-bulk-open]') && bulkModal) { e.preventDefault(); bulkModal.hidden = false; document.body.classList.add('wgc-modal-open'); var f = bulkModal.querySelector('input[name="name"]'); if (f) setTimeout(function () { f.focus(); }, 50); }
		if (e.target.closest('[data-wgc-bulk-close]') && bulkModal) { e.preventDefault(); bulkModal.hidden = true; document.body.classList.remove('wgc-modal-open'); }
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && bulkModal && !bulkModal.hidden) { bulkModal.hidden = true; document.body.classList.remove('wgc-modal-open'); } });
	document.querySelectorAll('[data-wgc-bulk-form]').forEach(function (form) {
		var msg = form.querySelector('[data-wgc-bulk-message]');
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var data = {};
			new FormData(form).forEach(function (v, k) { data[k] = v; });
			var btn = form.querySelector('[type="submit"]');
			btn.disabled = true;
			setMsg(msg, '...', true);
			core.ajax('bulk_inquiry', data)
				.then(function (json) { setMsg(msg, json.message, true); form.reset(); core.emit('bulk-inquiry-sent', json.data); })
				.catch(function (err) { setMsg(msg, err.message, false); })
				.then(function () { btn.disabled = false; });
		});
	});

	/* Track order */
	document.querySelectorAll('[data-wgc-track]').forEach(function (box) {
		var form = box.querySelector('[data-wgc-track-form]');
		var msg = box.querySelector('[data-wgc-track-message]');
		var result = box.querySelector('[data-wgc-track-result]');
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var order = form.querySelector('[name="order"]').value.trim();
			var contact = form.querySelector('[name="contact"]').value.trim();
			if (!order || !contact) return;
			setMsg(msg, '...', true);
			result.hidden = true;
			core.rest('track-order', { method: 'POST', body: { order: order, contact: contact } })
				.then(function (json) {
					var d = json.data;
					setMsg(msg, '', true);
					box.querySelector('[data-wgc-track-number]').textContent = '#' + d.order_number;
					box.querySelector('[data-wgc-track-status]').textContent = d.status_label;
					var tl = box.querySelector('[data-wgc-track-timeline]');
					tl.innerHTML = '';
					d.timeline.forEach(function (step) {
						var li = document.createElement('li');
						li.className = (step.done ? 'is-done' : '') + (step.current ? ' is-current' : '');
						li.innerHTML = '<span class="wgc-timeline__dot"></span><span class="wgc-timeline__label"></span><span class="wgc-timeline__date"></span>';
						li.querySelector('.wgc-timeline__label').textContent = step.label;
						li.querySelector('.wgc-timeline__date').textContent = step.date || '';
						tl.appendChild(li);
					});
					var carrier = box.querySelector('[data-wgc-track-carrier]');
					if (d.tracking) {
						carrier.hidden = false;
						carrier.textContent = (d.carrier ? d.carrier + ': ' : '') + d.tracking;
						if (d.tracking_url) { var a = document.createElement('a'); a.href = d.tracking_url; a.target = '_blank'; a.rel = 'noopener'; a.textContent = ' →'; carrier.appendChild(a); }
					} else { carrier.hidden = true; }
					var items = box.querySelector('[data-wgc-track-items]');
					items.innerHTML = '';
					d.items.forEach(function (it) {
						var li = document.createElement('li');
						if (it.image) { var img = document.createElement('img'); img.src = it.image; img.alt = ''; li.appendChild(img); }
						var span = document.createElement('span'); span.textContent = it.name + ' × ' + it.qty; li.appendChild(span);
						items.appendChild(li);
					});
					result.hidden = false;
				})
				.catch(function (err) { setMsg(msg, err.message, false); });
		});
	});
})();

/* Cart recommendations arrows (the row lives inside a WooCommerce fragment, so listen on document). */
document.addEventListener('click', function (e) {
	var btn = e.target.closest('[data-wgc-reco-prev], [data-wgc-reco-next]');
	if (!btn) return;
	var track = btn.closest('[data-wgc-cart-reco]').querySelector('[data-wgc-reco-track]');
	if (track) track.scrollBy({ left: btn.hasAttribute('data-wgc-reco-next') ? 250 : -250, behavior: 'smooth' });
});
