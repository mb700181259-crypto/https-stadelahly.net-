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

	/* Mark JS as ready for progressive styling. */
	root.classList.add('mn-js');
})();
