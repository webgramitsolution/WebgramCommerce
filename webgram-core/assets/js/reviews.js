/* Webgram Core reviews: sort, filter, load more, helpful votes, media lightbox, form star input and previews. */
(function () {
	'use strict';

	var Core = window.WebgramCore;
	if (!Core) { return; }

	function toast(msg) {
		if (!msg) { return; }
		if (typeof Core.toast === 'function') { Core.toast(msg); return; }
		window.alert(msg);
	}

	function initBlock(block) {
		var productId = parseInt(block.getAttribute('data-wgc-reviews'), 10);
		var list = block.querySelector('[data-wgc-reviews-list]');
		var showing = block.querySelector('[data-wgc-reviews-showing]');
		var moreWrap = block.querySelector('[data-wgc-reviews-more-wrap]');
		var moreBtn = block.querySelector('[data-wgc-reviews-more]');
		var sortSel = block.querySelector('[data-wgc-reviews-sort]');
		var state = { page: 1, sort: block.getAttribute('data-sort') || 'newest', stars: 0, media: false, loading: false, pages: parseInt(block.getAttribute('data-pages'), 10) || 1 };

		function load(append) {
			if (state.loading) { return; }
			state.loading = true;
			block.classList.add('is-loading');
			Core.ajax('reviews_load', { product_id: productId, page: state.page, sort: state.sort, stars: state.stars, media: state.media ? 1 : 0 }).then(function (res) {
				var d = res.data || {};
				if (!list) { return; }
				if (append) {
					list.insertAdjacentHTML('beforeend', d.html || '');
				} else {
					list.innerHTML = d.html || '<p class="wgc-reviews__none wg-reviews__none" data-wgc-reviews-empty>' + (block.getAttribute('data-empty') || 'No reviews match this filter yet.') + '</p>';
				}
				if (showing) { showing.textContent = d.showing || ''; }
				state.pages = d.pages || 1;
				if (moreWrap) { moreWrap.hidden = !d.has_more; }
				if (moreBtn && moreBtn.getAttribute('data-label-more')) { moreBtn.textContent = moreBtn.getAttribute('data-label-more'); }
				Core.emit('reviews:loaded', d);
			}).catch(function (err) {
				toast(err.message);
			}).then(function () {
				state.loading = false;
				block.classList.remove('is-loading');
			});
		}

		function setChip(type, value) {
			block.querySelectorAll('[data-wgc-reviews-filter]').forEach(function (chip) {
				if (!chip.classList.contains('wgc-chip') && !chip.classList.contains('wg-chip')) { return; }
				var t = chip.getAttribute('data-wgc-reviews-filter');
				var v = parseInt(chip.getAttribute('data-value') || '0', 10);
				chip.classList.toggle('is-active', (t === 'all' && type === 'all') || (t === type && (t !== 'stars' || v === value)));
			});
		}

		block.addEventListener('click', function (e) {
			var chip = e.target.closest('[data-wgc-reviews-filter]');
			if (chip) {
				var type = chip.getAttribute('data-wgc-reviews-filter');
				if (type === 'all') { state.stars = 0; state.media = false; }
				if (type === 'stars') { var v = parseInt(chip.getAttribute('data-value'), 10); state.stars = state.stars === v && chip.classList.contains('is-active') ? 0 : v; state.media = false; }
				if (type === 'media') { state.media = !state.media; state.stars = 0; }
				setChip(state.media ? 'media' : (state.stars ? 'stars' : 'all'), state.stars);
				state.page = 1;
				load(false);
				if (list) { list.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
				return;
			}
			if (e.target.closest('[data-wgc-reviews-more]')) {
				if (state.page < state.pages) { state.page += 1; load(true); }
				return;
			}
			var write = e.target.closest('[data-wgc-reviews-write]');
			if (write) {
				var form = block.querySelector('[data-wgc-reviews-form]');
				if (form) {
					form.hidden = !form.hidden;
					if (!form.hidden) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); var first = form.querySelector('input[type="radio"], textarea, input'); if (first) { first.focus(); } }
				}
				return;
			}
			var vote = e.target.closest('[data-wgc-helpful]');
			if (vote) {
				vote.disabled = true;
				Core.ajax('reviews_helpful', { comment_id: vote.getAttribute('data-wgc-helpful') }).then(function (res) {
					var d = res.data || {};
					var count = vote.querySelector('[data-wgc-helpful-count]');
					if (count && typeof d.count !== 'undefined') { count.textContent = d.count; }
					vote.classList.add('is-voted');
					toast(d.message || res.message);
				}).catch(function (err) { vote.disabled = false; toast(err.message); });
				return;
			}
			var thumb = e.target.closest('[data-wgc-lightbox]');
			if (thumb) { openLightbox(block, thumb); }
		});

		if (sortSel) {
			sortSel.addEventListener('change', function () { state.sort = sortSel.value; state.page = 1; load(false); });
		}

		initForm(block);
	}

	function openLightbox(block, thumb) {
		var modal = block.querySelector('[data-wgc-lightbox-modal]');
		if (!modal) { return; }
		var stage = modal.querySelector('[data-wgc-lightbox-stage]');
		var review = thumb.closest('[data-wgc-review], .review, .comment_container') || block;
		var items = Array.prototype.slice.call(review.querySelectorAll('[data-wgc-lightbox]'));
		var index = items.indexOf(thumb);
		function show(i) {
			index = (i + items.length) % items.length;
			var el = items[index];
			stage.innerHTML = el.getAttribute('data-type') === 'video'
				? '<video src="' + el.getAttribute('data-wgc-lightbox') + '" controls autoplay playsinline></video>'
				: '<img src="' + el.getAttribute('data-wgc-lightbox') + '" alt="">';
			modal.querySelectorAll('[data-wgc-lightbox-prev], [data-wgc-lightbox-next]').forEach(function (b) { b.hidden = items.length < 2; });
		}
		function close() { modal.hidden = true; stage.innerHTML = ''; document.removeEventListener('keydown', onKey); thumb.focus(); }
		function onKey(e) {
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowLeft') { show(index - 1); }
			if (e.key === 'ArrowRight') { show(index + 1); }
		}
		modal.hidden = false;
		show(index);
		document.addEventListener('keydown', onKey);
		modal.onclick = function (e) {
			if (e.target.closest('[data-wgc-lightbox-close]')) { close(); }
			if (e.target.closest('[data-wgc-lightbox-prev]')) { show(index - 1); }
			if (e.target.closest('[data-wgc-lightbox-next]')) { show(index + 1); }
		};
	}

	function initForm(root) {
		root.querySelectorAll('form.comment-form').forEach(function (form) {
			if (form.querySelector('[data-wgc-review-media]')) { form.setAttribute('enctype', 'multipart/form-data'); }
		});
		root.querySelectorAll('[data-wgc-star-input]').forEach(function (fieldset) {
			var stars = Array.prototype.slice.call(fieldset.querySelectorAll('label'));
			function paint(n) { stars.forEach(function (s, i) { s.classList.toggle('is-on', i < n); }); }
			fieldset.addEventListener('change', function () { var checked = fieldset.querySelector('input:checked'); paint(checked ? parseInt(checked.value, 10) : 0); });
			stars.forEach(function (s, i) {
				s.addEventListener('mouseenter', function () { paint(i + 1); });
				s.addEventListener('mouseleave', function () { var checked = fieldset.querySelector('input:checked'); paint(checked ? parseInt(checked.value, 10) : 0); });
			});
		});
		root.querySelectorAll('[data-wgc-review-media]').forEach(function (wrap) {
			var input = wrap.querySelector('input[type="file"]');
			var previews = wrap.querySelector('[data-wgc-review-previews]');
			var error = wrap.querySelector('[data-wgc-review-media-error]');
			var max = parseInt(wrap.getAttribute('data-max'), 10) || 5;
			var maxBytes = (parseInt(wrap.getAttribute('data-max-mb'), 10) || 8) * 1048576;
			if (!input) { return; }
			input.addEventListener('change', function () {
				var files = Array.prototype.slice.call(input.files || []);
				var msg = '';
				if (files.length > max) { msg = wrap.getAttribute('data-error-count'); }
				files.forEach(function (f) { if (f.size > maxBytes) { msg = wrap.getAttribute('data-error-size'); } });
				if (error) { error.textContent = msg || ''; error.hidden = !msg; }
				if (msg) { input.value = ''; if (previews) { previews.innerHTML = ''; } return; }
				if (!previews) { return; }
				previews.innerHTML = '';
				files.forEach(function (f) {
					var url = URL.createObjectURL(f);
					var el = document.createElement(f.type.indexOf('video/') === 0 ? 'video' : 'img');
					el.src = url;
					if (el.tagName === 'VIDEO') { el.muted = true; }
					el.className = 'wgc-review-form__preview';
					previews.appendChild(el);
				});
			});
		});
	}

	function init(root) {
		(root || document).querySelectorAll('[data-wgc-reviews]').forEach(function (block) {
			if (block._wgcInit) { return; }
			block._wgcInit = true;
			initBlock(block);
		});
		// Default WooCommerce template (other themes): still enhance the form and media thumbnails.
		var defaultWrap = document.getElementById('reviews');
		if (defaultWrap && !defaultWrap.hasAttribute('data-wgc-reviews') && !defaultWrap._wgcInit) {
			defaultWrap._wgcInit = true;
			initForm(defaultWrap);
			defaultWrap.addEventListener('click', function (e) {
				var vote = e.target.closest('[data-wgc-helpful]');
				if (vote) {
					vote.disabled = true;
					Core.ajax('reviews_helpful', { comment_id: vote.getAttribute('data-wgc-helpful') }).then(function (res) {
						var count = vote.querySelector('[data-wgc-helpful-count]');
						if (count && res.data && typeof res.data.count !== 'undefined') { count.textContent = res.data.count; }
						vote.classList.add('is-voted');
					}).catch(function (err) { vote.disabled = false; toast(err.message); });
				}
				var thumb = e.target.closest('[data-wgc-lightbox]');
				if (thumb) { window.open(thumb.getAttribute('data-wgc-lightbox'), '_blank', 'noopener'); }
			});
		}
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(); }); } else { init(); }
	document.addEventListener('wg:content-updated', function () { init(); });
})();
