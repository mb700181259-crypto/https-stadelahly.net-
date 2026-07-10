/**
 * Manchit front-end script — vanilla JS, no dependencies, deferred.
 *
 * @package Manchit
 */
(function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var data = window.ManchitData || {};

	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function on(el, ev, fn, opts) { if (el) el.addEventListener(ev, fn, opts || false); }

	/* ---------------------------------------------------------------
	 * Dark / light theme toggle
	 * ------------------------------------------------------------- */
	function currentTheme() {
		return root.getAttribute('data-theme') || 'light';
	}
	function applyTheme(mode) {
		root.setAttribute('data-theme', mode);
		try { localStorage.setItem('mn-theme', mode); } catch (e) {}
	}
	$all('[data-mn-toggle-theme]').forEach(function (btn) {
		on(btn, 'click', function () {
			applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
		});
	});

	/* ---------------------------------------------------------------
	 * Mobile drawer
	 * ------------------------------------------------------------- */
	var drawer = $('[data-mn-drawer]');
	var overlay = $('[data-mn-overlay]');
	function openDrawer() {
		if (!drawer) return;
		drawer.classList.add('is-open');
		if (overlay) overlay.classList.add('is-open');
		drawer.setAttribute('aria-hidden', 'false');
		doc.body.style.overflow = 'hidden';
	}
	function closeDrawer() {
		if (!drawer) return;
		drawer.classList.remove('is-open');
		if (overlay) overlay.classList.remove('is-open');
		drawer.setAttribute('aria-hidden', 'true');
		doc.body.style.overflow = '';
	}
	$all('[data-mn-open-menu]').forEach(function (b) { on(b, 'click', openDrawer); });
	$all('[data-mn-close-menu]').forEach(function (b) { on(b, 'click', closeDrawer); });
	on(overlay, 'click', closeDrawer);

	/* Expand submenus inside the drawer on tap. */
	$all('.mn-drawer__nav .menu-item-has-children > a').forEach(function (a) {
		on(a, 'click', function (e) {
			var sub = a.parentNode.querySelector('.sub-menu');
			if (sub) {
				e.preventDefault();
				sub.style.display = sub.style.display === 'block' ? 'none' : 'block';
			}
		});
	});

	/* ---------------------------------------------------------------
	 * Search overlay
	 * ------------------------------------------------------------- */
	var searchOverlay = $('[data-mn-search]');
	function openSearch() {
		if (!searchOverlay) return;
		searchOverlay.classList.add('is-open');
		searchOverlay.setAttribute('aria-hidden', 'false');
		var input = searchOverlay.querySelector('input[type="search"]');
		if (input) setTimeout(function () { input.focus(); }, 50);
	}
	function closeSearch() {
		if (!searchOverlay) return;
		searchOverlay.classList.remove('is-open');
		searchOverlay.setAttribute('aria-hidden', 'true');
	}
	$all('[data-mn-open-search]').forEach(function (b) { on(b, 'click', openSearch); });
	$all('[data-mn-close-search]').forEach(function (b) { on(b, 'click', closeSearch); });

	on(doc, 'keyup', function (e) {
		if (e.key === 'Escape') { closeSearch(); closeDrawer(); }
	});

	/* ---------------------------------------------------------------
	 * Sticky header hide-on-scroll + shadow
	 * ------------------------------------------------------------- */
	var header = $('#mn-header');
	if (header && header.getAttribute('data-hide-on-scroll') === '1') {
		var lastY = window.pageYOffset;
		var ticking = false;
		function onScrollHeader() {
			var y = window.pageYOffset;
			header.classList.toggle('is-stuck', y > 4);
			if (y > lastY && y > 260) {
				header.classList.add('is-hidden');
			} else {
				header.classList.remove('is-hidden');
			}
			lastY = y;
			ticking = false;
		}
		on(window, 'scroll', function () {
			if (!ticking) { window.requestAnimationFrame(onScrollHeader); ticking = true; }
		}, { passive: true });
	} else if (header) {
		on(window, 'scroll', function () {
			header.classList.toggle('is-stuck', window.pageYOffset > 4);
		}, { passive: true });
	}

	/* ---------------------------------------------------------------
	 * Back to top
	 * ------------------------------------------------------------- */
	var toTop = $('[data-mn-scrolltop]');
	if (toTop) {
		on(window, 'scroll', function () {
			toTop.classList.toggle('is-visible', window.pageYOffset > 600);
		}, { passive: true });
		on(toTop, 'click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

	/* ---------------------------------------------------------------
	 * Reading progress bar
	 * ------------------------------------------------------------- */
	var progress = $('[data-mn-progress]');
	var article = $('.mn-entry');
	if (progress && article) {
		function updateProgress() {
			var rect = article.getBoundingClientRect();
			var total = article.offsetHeight - window.innerHeight;
			var scrolled = Math.min(Math.max(-rect.top, 0), total);
			var pct = total > 0 ? (scrolled / total) * 100 : 0;
			progress.style.width = pct + '%';
		}
		on(window, 'scroll', updateProgress, { passive: true });
		on(window, 'resize', updateProgress);
		updateProgress();
	}

	/* ---------------------------------------------------------------
	 * Table of contents: collapse + scrollspy
	 * ------------------------------------------------------------- */
	var toc = $('.mn-toc');
	if (toc) {
		var toggle = toc.querySelector('[data-mn-toc-toggle]');
		on(toggle, 'click', function () {
			var collapsed = toc.getAttribute('data-collapsed') === 'true';
			toc.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
		});

		var tocLinks = $all('a', toc);
		var targets = tocLinks.map(function (l) {
			var id = decodeURIComponent(l.getAttribute('href').slice(1));
			return doc.getElementById(id) || doc.querySelector('[id="' + id + '"]');
		});
		if ('IntersectionObserver' in window) {
			var spy = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						var idx = targets.indexOf(entry.target);
						tocLinks.forEach(function (l) { l.classList.remove('is-active'); });
						if (tocLinks[idx]) tocLinks[idx].classList.add('is-active');
					}
				});
			}, { rootMargin: '-80px 0px -70% 0px' });
			targets.forEach(function (t) { if (t) spy.observe(t); });
		}
	}

	/* ---------------------------------------------------------------
	 * Copy link (share)
	 * ------------------------------------------------------------- */
	$all('[data-mn-copy]').forEach(function (btn) {
		on(btn, 'click', function () {
			var url = btn.getAttribute('data-mn-copy');
			var done = function () {
				var old = btn.getAttribute('aria-label');
				btn.setAttribute('aria-label', data.copiedText || 'Copied');
				btn.classList.add('is-copied');
				setTimeout(function () { btn.setAttribute('aria-label', old); btn.classList.remove('is-copied'); }, 1600);
			};
			if (navigator.clipboard) {
				navigator.clipboard.writeText(url).then(done).catch(done);
			} else {
				var t = doc.createElement('textarea');
				t.value = url; doc.body.appendChild(t); t.select();
				try { doc.execCommand('copy'); } catch (e) {}
				doc.body.removeChild(t); done();
			}
		});
	});

	/* Native share where available (progressive enhancement). */
	if (navigator.share) {
		$all('.mn-share').forEach(function (wrap) {
			var b = doc.createElement('button');
			b.type = 'button';
			b.className = 's-native';
			b.setAttribute('aria-label', data.shareText || 'Share');
			b.textContent = '⤴';
			on(b, 'click', function () {
				navigator.share({ title: doc.title, url: location.href }).catch(function () {});
			});
			wrap.appendChild(b);
		});
	}

	/* ---------------------------------------------------------------
	 * Sticky ad close
	 * ------------------------------------------------------------- */
	$all('[data-mn-ad-close]').forEach(function (btn) {
		on(btn, 'click', function () {
			var ad = btn.closest('.mn-ad');
			if (ad) ad.remove();
		});
	});

	/* ---------------------------------------------------------------
	 * Post view ping (cache-safe)
	 * ------------------------------------------------------------- */
	if (data.isSingular && data.restUrl) {
		var art = $('.mn-article[data-post-id]');
		if (art) {
			var pid = art.getAttribute('data-post-id');
			var key = 'mn-viewed-' + pid;
			var seen = false;
			try { seen = sessionStorage.getItem(key) === '1'; } catch (e) {}
			if (!seen) {
				// Fire once, after the page settles.
				window.setTimeout(function () {
					fetch(data.restUrl + 'manchit/v1/view/' + pid, {
						method: 'POST',
						headers: { 'X-WP-Nonce': data.nonce || '' },
						keepalive: true
					}).catch(function () {});
					try { sessionStorage.setItem(key, '1'); } catch (e) {}
				}, 1200);
			}
		}
	}

	/* ---------------------------------------------------------------
	 * Re-execute <script> nodes inside a freshly-inserted container
	 * (innerHTML/template scripts are inert until recreated).
	 * ------------------------------------------------------------- */
	function reexecScripts(container) {
		$all('script', container).forEach(function (old) {
			var s = doc.createElement('script');
			Array.prototype.forEach.call(old.attributes, function (a) { s.setAttribute(a.name, a.value); });
			if (!old.src) { s.textContent = old.textContent; }
			old.parentNode.replaceChild(s, old);
		});
	}

	/* ---------------------------------------------------------------
	 * Lazy-load ads: reveal each unit when it nears the viewport
	 * ------------------------------------------------------------- */
	function revealAd(ad) {
		var tpl = ad.querySelector('template.mn-ad__tpl');
		if (!tpl) { return; }
		var holder = doc.createElement('div');
		holder.className = 'mn-ad__inner';
		holder.appendChild(tpl.content.cloneNode(true));
		tpl.remove();
		ad.appendChild(holder);
		ad.removeAttribute('data-mn-ad-lazy');
		reexecScripts(holder);
	}
	function initLazyAds(rootEl) {
		var ads = $all('[data-mn-ad-lazy]', rootEl);
		if (!ads.length) { return; }
		if (!('IntersectionObserver' in window)) { ads.forEach(revealAd); return; }
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { revealAd(e.target); io.unobserve(e.target); }
			});
		}, { rootMargin: '400px 0px' });
		ads.forEach(function (a) { io.observe(a); });
	}
	initLazyAds(doc);

	/* ---------------------------------------------------------------
	 * Autoload the next (older) article on scroll — infinite reading
	 * ------------------------------------------------------------- */
	function pingView(art) {
		var pid = art.getAttribute('data-post-id');
		if (!pid || !data.restUrl) { return; }
		var key = 'mn-viewed-' + pid, seen = false;
		try { seen = sessionStorage.getItem(key) === '1'; } catch (e) {}
		if (seen) { return; }
		fetch(data.restUrl + 'manchit/v1/view/' + pid, {
			method: 'POST', headers: { 'X-WP-Nonce': data.nonce || '' }, keepalive: true
		}).catch(function () {});
		try { sessionStorage.setItem(key, '1'); } catch (e) {}
	}

	function trackForHistory(art, url) {
		var titleEl = art.querySelector('.mn-article__title');
		var title = titleEl ? titleEl.textContent.trim() : doc.title;
		if (!('IntersectionObserver' in window)) { return; }
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) {
					try { history.replaceState(null, '', url); doc.title = title; } catch (err) {}
				}
			});
		}, { rootMargin: '-45% 0px -50% 0px' });
		io.observe(art);
	}

	(function initAutoload() {
		var sentinel = $('.mn-autoload');
		if (!sentinel || !('IntersectionObserver' in window)) { return; }
		var loading = false, done = false;

		function loadNext() {
			var url = sentinel.getAttribute('data-mn-next');
			if (!url || done) { return; }
			loading = true;
			sentinel.classList.add('is-loading');
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.text() : Promise.reject(); })
				.then(function (html) {
					var dom = new DOMParser().parseFromString(html, 'text/html');
					var art = dom.querySelector('.mn-article');
					if (!art) { done = true; sentinel.remove(); return; }
					var sep = doc.createElement('div');
					sep.className = 'mn-autoload__sep';
					var titleEl = art.querySelector('.mn-article__title');
					sep.textContent = titleEl ? titleEl.textContent.trim() : '';
					sentinel.parentNode.insertBefore(sep, sentinel);
					sentinel.parentNode.insertBefore(art, sentinel);
					reexecScripts(art);
					initLazyAds(art);
					trackForHistory(art, url);
					pingView(art);
					var ns = dom.querySelector('.mn-autoload');
					var nextUrl = ns && ns.getAttribute('data-mn-next');
					if (nextUrl) { sentinel.setAttribute('data-mn-next', nextUrl); loading = false; sentinel.classList.remove('is-loading'); }
					else { done = true; sentinel.remove(); }
				})
				.catch(function () { loading = false; sentinel.classList.remove('is-loading'); });
		}

		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { if (e.isIntersecting && !loading && !done) { loadNext(); } });
		}, { rootMargin: '700px 0px' });
		io.observe(sentinel);
	})();

	/* ---------------------------------------------------------------
	 * Archive "load more" / infinite scroll (append cards via fetch)
	 * ------------------------------------------------------------- */
	(function initLoadMore() {
		var btn = $('[data-mn-loadmore]');
		if (!btn) { return; }
		var wrap = btn.closest('.mn-loadmore');
		var grid = $('.mn-primary .mn-cards') || $('.mn-cards');
		if (!grid) { return; }
		var loading = false;

		function load() {
			var url = wrap.getAttribute('data-next');
			if (!url || loading) { return; }
			loading = true;
			btn.classList.add('is-loading');
			btn.disabled = true;
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.text() : Promise.reject(); })
				.then(function (html) {
					var dom = new DOMParser().parseFromString(html, 'text/html');
					var srcGrid = dom.querySelector('.mn-primary .mn-cards') || dom.querySelector('.mn-cards');
					if (srcGrid) {
						Array.prototype.slice.call(srcGrid.children).forEach(function (c) {
							grid.appendChild(doc.importNode(c, true));
						});
						initLazyAds(grid);
					}
					var nw = dom.querySelector('.mn-loadmore');
					var nu = nw && nw.getAttribute('data-next');
					if (nu) { wrap.setAttribute('data-next', nu); }
					else { wrap.remove(); }
					loading = false;
					btn.classList.remove('is-loading');
					btn.disabled = false;
				})
				.catch(function () { loading = false; btn.classList.remove('is-loading'); btn.disabled = false; });
		}

		on(btn, 'click', load);

		if (wrap.getAttribute('data-infinite') === '1' && 'IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (e) { if (e.isIntersecting) { load(); } });
			}, { rootMargin: '600px 0px' });
			io.observe(wrap);
		}
	})();

	/* ---------------------------------------------------------------
	 * Homepage category tabs (accessible)
	 * ------------------------------------------------------------- */
	$all('.mn-tabs').forEach(function (tabs) {
		var btns = $all('.mn-tabs__btn', tabs);
		var panels = $all('.mn-tabs__panel', tabs);
		function activate(idx) {
			btns.forEach(function (b, i) {
				var on = i === idx;
				b.classList.toggle('is-active', on);
				b.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			panels.forEach(function (p, i) {
				var on = i === idx;
				p.classList.toggle('is-active', on);
				if (on) { p.removeAttribute('hidden'); } else { p.setAttribute('hidden', ''); }
			});
		}
		btns.forEach(function (b, i) {
			on(b, 'click', function () { activate(i); });
			on(b, 'keydown', function (e) {
				if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
					e.preventDefault();
					var dir = e.key === 'ArrowLeft' ? 1 : -1; // RTL-aware
					var next = (i + dir + btns.length) % btns.length;
					btns[next].focus();
					activate(next);
				}
			});
		});
	});

	/* ---------------------------------------------------------------
	 * Reader font-size control (persisted)
	 * ------------------------------------------------------------- */
	(function initFontSize() {
		var entry = $('.mn-entry');
		var ctrl = $('.mn-fontsize');
		if (!entry || !ctrl) { return; }
		var STEP = 1, MIN = -2, MAX = 6, level = 0;
		try { level = parseInt(localStorage.getItem('mn-fontsize') || '0', 10) || 0; } catch (e) {}
		function apply() {
			level = Math.max(MIN, Math.min(MAX, level));
			entry.style.fontSize = 'calc(1.08rem + ' + (level * 0.06) + 'rem)';
			try { localStorage.setItem('mn-fontsize', String(level)); } catch (e) {}
		}
		$all('[data-mn-font]', ctrl).forEach(function (btn) {
			on(btn, 'click', function () {
				var a = btn.getAttribute('data-mn-font');
				if (a === 'inc') { level += STEP; } else if (a === 'dec') { level -= STEP; } else { level = 0; }
				apply();
			});
		});
		if (level !== 0) { apply(); }
	})();

	/* ---------------------------------------------------------------
	 * Quick search suggestions (in the search overlay)
	 * ------------------------------------------------------------- */
	(function initSuggest() {
		if (!searchOverlay || !data.restUrl) { return; }
		var input = searchOverlay.querySelector('input[type="search"]');
		if (!input) { return; }
		var box = doc.createElement('div');
		box.className = 'mn-suggest';
		input.parentNode.parentNode.appendChild(box);
		var timer = null, lastQ = '';

		function render(items) {
			if (!items.length) { box.innerHTML = ''; box.classList.remove('is-open'); return; }
			box.innerHTML = items.map(function (it) {
				return '<a href="' + it.url + '">' + it.title.replace(/</g, '&lt;') + '</a>';
			}).join('');
			box.classList.add('is-open');
		}
		on(input, 'input', function () {
			var q = input.value.trim();
			if (q === lastQ) { return; }
			lastQ = q;
			if (timer) { clearTimeout(timer); }
			if (q.length < 2) { render([]); return; }
			timer = setTimeout(function () {
				fetch(data.restUrl + 'manchit/v1/suggest?q=' + encodeURIComponent(q))
					.then(function (r) { return r.ok ? r.json() : []; })
					.then(render)
					.catch(function () {});
			}, 220);
		});
		on(doc, 'keyup', function (e) { if (e.key === 'Escape') { render([]); } });
	})();

	/* Mark JS as ready for progressive styling. */
	root.classList.add('mn-js');
})();
