/**
 * لامرد خودرو — جستجوی زنده خودرو.
 * وابسته به داده‌های محلی‌سازی‌شده `lkcSearch`.
 */
(function () {
	'use strict';

	if (typeof window.lkcSearch === 'undefined') {
		return;
	}

	var config = window.lkcSearch;
	var DEBOUNCE = 260;

	function formatPrice(price) {
		if (!price) {
			return '';
		}
		var value = price.market_price;
		if (value === null || typeof value === 'undefined' || value === '') {
			return 'ناموجود';
		}
		var number = Number(value);
		if (!isFinite(number)) {
			return '';
		}
		return number.toLocaleString('fa-IR') + ' تومان';
	}

	function buildItem(item) {
		var link = document.createElement('a');
		link.className = 'lkc-search__item';
		link.href = item.url;

		if (item.thumbnail) {
			var img = document.createElement('img');
			img.src = item.thumbnail;
			img.alt = '';
			img.loading = 'lazy';
			link.appendChild(img);
		}

		var text = document.createElement('span');
		var title = document.createElement('span');
		title.className = 'lkc-search__item-title';
		title.textContent = item.title;
		var meta = document.createElement('span');
		meta.className = 'lkc-search__item-meta';
		var price = formatPrice(item.price);
		meta.textContent = [item.brand, price].filter(Boolean).join(' — ');
		text.appendChild(title);
		if (meta.textContent) {
			text.appendChild(document.createElement('br'));
			text.appendChild(meta);
		}
		link.appendChild(text);

		return link;
	}

	function setup(root) {
		var input = root.querySelector('[data-lkc-search-input]');
		var results = root.querySelector('[data-lkc-search-results]');
		if (!input || !results) {
			return;
		}

		var controller = null;
		var timer = null;

		function close() {
			results.hidden = true;
			results.innerHTML = '';
		}

		function render(items) {
			results.innerHTML = '';
			if (!items.length) {
				var empty = document.createElement('div');
				empty.className = 'lkc-search__empty';
				empty.textContent = config.empty;
				results.appendChild(empty);
			} else {
				items.forEach(function (item) {
					results.appendChild(buildItem(item));
				});
			}
			results.hidden = false;
		}

		function search(term) {
			if (controller) {
				controller.abort();
			}
			controller = new AbortController();

			results.hidden = false;
			results.innerHTML = '<div class="lkc-search__empty">' + config.loading + '</div>';

			var url = config.endpoint + '&nonce=' + encodeURIComponent(config.nonce) + '&q=' + encodeURIComponent(term);

			fetch(url, {
				credentials: 'same-origin',
				signal: controller.signal,
				headers: { Accept: 'application/json' }
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('HTTP ' + response.status);
					}
					return response.json();
				})
				.then(function (payload) {
					if (!payload || !payload.success) {
						close();
						return;
					}
					render(payload.data.results || []);
				})
				.catch(function (error) {
					if (error && error.name === 'AbortError') {
						return;
					}
					close();
				});
		}

		input.addEventListener('input', function () {
			var term = input.value.trim();
			window.clearTimeout(timer);
			if (term.length < 2) {
				close();
				return;
			}
			timer = window.setTimeout(function () {
				search(term);
			}, DEBOUNCE);
		});

		input.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				close();
			}
		});

		document.addEventListener('click', function (event) {
			if (!root.contains(event.target)) {
				close();
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-lkc-search]').forEach(setup);
	});
})();
