/**
 * لامرد خودرو — تعاملات قالب (بدون وابستگی خارجی).
 */
(function () {
	'use strict';

	var doc = document;

	/* ---------------------------------------------------------------- منوی موبایل */
	function initNav() {
		var toggle = doc.querySelector('[data-lk-nav-toggle]');
		var nav = doc.querySelector('[data-lk-nav]');

		if (!toggle || !nav) {
			return;
		}

		function close() {
			nav.removeAttribute('data-open');
			toggle.setAttribute('aria-expanded', 'false');
			doc.body.classList.remove('lk-nav-open');
		}

		toggle.addEventListener('click', function () {
			var open = toggle.getAttribute('aria-expanded') === 'true';
			if (open) {
				close();
			} else {
				toggle.setAttribute('aria-expanded', 'true');
				nav.setAttribute('data-open', 'true');
				doc.body.classList.add('lk-nav-open');
			}
		});

		doc.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				close();
			}
		});

		doc.addEventListener('click', function (event) {
			if (!nav.contains(event.target) && !toggle.contains(event.target)) {
				close();
			}
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth >= 900) {
				close();
			}
		});
	}

	/* ------------------------------------------------------- سایه سربرگ هنگام اسکرول */
	function initHeaderShadow() {
		var header = doc.querySelector('[data-lk-header]');
		if (!header) {
			return;
		}

		var ticking = false;

		function update() {
			header.classList.toggle('lk-header--scrolled', window.scrollY > 8);
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(update);
				ticking = true;
			}
		}, { passive: true });

		update();
	}

	/* --------------------------------------------------------- دکمه بازگشت به بالا */
	function initToTop() {
		var button = doc.querySelector('[data-lk-to-top]');
		if (!button) {
			return;
		}

		var ticking = false;

		function update() {
			button.setAttribute('data-visible', window.scrollY > 500 ? 'true' : 'false');
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(update);
				ticking = true;
			}
		}, { passive: true });

		button.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});

		update();
	}

	/* ------------------------------------------------------------ جدول قیمت قابل مرتب‌سازی */
	function initPriceTableSort() {
		var table = doc.querySelector('[data-lk-sortable]');
		if (!table) {
			return;
		}

		var buttons = table.querySelectorAll('[data-lk-sort]');
		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				var key = button.getAttribute('data-lk-sort');
				var direction = button.getAttribute('data-direction') === 'asc' ? 'desc' : 'asc';
				button.setAttribute('data-direction', direction);

				var body = table.tBodies[0];
				var rows = Array.prototype.slice.call(body.rows);

				rows.sort(function (a, b) {
					var av = a.getAttribute('data-' + key) || '';
					var bv = b.getAttribute('data-' + key) || '';
					var an = parseFloat(av);
					var bn = parseFloat(bv);

					if (!isNaN(an) && !isNaN(bn)) {
						return direction === 'asc' ? an - bn : bn - an;
					}
					return direction === 'asc'
						? av.localeCompare(bv, 'fa')
						: bv.localeCompare(av, 'fa');
				});

				rows.forEach(function (row) {
					body.appendChild(row);
				});
			});
		});
	}

	/* ------------------------------------------------------------- افزودن به مقایسه */
	function compareKey() {
		return 'lk_compare_ids';
	}

	function readCompare() {
		try {
			var raw = window.localStorage.getItem(compareKey());
			var list = raw ? JSON.parse(raw) : [];
			return Array.isArray(list) ? list.filter(function (id) { return Number(id) > 0; }) : [];
		} catch (error) {
			return [];
		}
	}

	function writeCompare(ids) {
		try {
			window.localStorage.setItem(compareKey(), JSON.stringify(ids.slice(0, 4)));
		} catch (error) {
			/* ذخیره‌سازی در دسترس نیست؛ مقایسه بدون حافظه کار می‌کند. */
		}
	}

	function setCompareButtonState(button, active) {
		button.setAttribute('aria-pressed', active ? 'true' : 'false');
		button.classList.toggle('is-active', active);

		var label = active
			? button.getAttribute('data-lk-compare-label-remove')
			: button.getAttribute('data-lk-compare-label-add');

		if (label) {
			button.textContent = label;
		}
	}

	function initCompare() {
		var buttons = doc.querySelectorAll('[data-lk-compare-add]');

		buttons.forEach(function (button) {
			var id = Number(button.getAttribute('data-lk-compare-add'));

			setCompareButtonState(button, readCompare().indexOf(id) !== -1);

			button.addEventListener('click', function () {
				var current = readCompare();
				var active = current.indexOf(id) !== -1;

				if (active) {
					current = current.filter(function (item) { return item !== id; });
				} else if (current.length < 4) {
					current.push(id);
				} else {
					return;
				}

				writeCompare(current);
				setCompareButtonState(button, !active);
				updateCompareLinks();
			});
		});
	}

	/* ------------------------------------------------------- پیوند مقایسه با localStorage */
	function compareBaseHref(link) {
		var base = link.getAttribute('data-lk-base-href');

		if (!base) {
			base = (link.getAttribute('href') || '')
				.replace(/([?&])ids=[^&]*&?/, '$1')
				.replace(/[?&]$/, '');
			link.setAttribute('data-lk-base-href', base);
		}

		return base;
	}

	function updateCompareLinks() {
		var ids = readCompare();

		doc.querySelectorAll('[data-lk-compare-link]').forEach(function (link) {
			var base = compareBaseHref(link);			var separator = base.indexOf('?') === -1 ? '?' : '&';

			link.setAttribute(
				'href',
				ids.length ? base + separator + 'ids=' + ids.join(',') : base
			);
		});
	}

	/* ---------------------------------------------------------- شمارش معکوس آگهی */
	function faDigits(value) {
		return String(value).replace(/\d/g, function (digit) {
			return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(digit));
		});
	}

	function initCountdowns() {
		var nodes = doc.querySelectorAll('[data-lk-countdown]');

		if (!nodes.length) {
			return;
		}

		var strings = (window.lkData && window.lkData.countdown) || {
			expired: 'پایان یافته',
			day: 'روز'
		};

		var pad = function (value) {
			return value < 10 ? '0' + value : String(value);
		};

		var tick = function () {
			nodes.forEach(function (node) {
				var until = Number(node.getAttribute('data-lk-countdown')) || 0;
				var value = node.querySelector('[data-lk-countdown-value]');
				var left = until - Math.floor(Date.now() / 1000);

				if (left <= 0) {
					node.classList.add('is-expired');
					if (value) {
						value.textContent = strings.expired;
					}
					return;
				}

				node.classList.remove('is-expired');

				var days = Math.floor(left / 86400);
				var clock = faDigits(
					pad(Math.floor((left % 86400) / 3600)) + ':' +
					pad(Math.floor((left % 3600) / 60)) + ':' +
					pad(left % 60)
				);

				if (value) {
					value.textContent = days > 0 ? faDigits(days) + ' ' + strings.day + ' و ' + clock : clock;
				}
			});
		};

		tick();
		window.setInterval(tick, 1000);
	}

	doc.addEventListener('DOMContentLoaded', function () {
		initNav();
		initHeaderShadow();
		initToTop();
		initPriceTableSort();
		initCompare();
		updateCompareLinks();
		initCountdowns();
	});
})();
