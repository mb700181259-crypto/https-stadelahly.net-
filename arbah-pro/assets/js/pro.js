/* arbah Pro — reading UX (vanilla JS, no jQuery).
 * Dark-mode toggle (persisted), reading progress bar, back-to-top button. */
( function () {
	'use strict';

	var cfg = window.ArbahPro || {};
	var labels = cfg.labels || {};
	var doc = document;
	var root = doc.documentElement;

	function el( tag, cls, html ) {
		var n = doc.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( html ) { n.innerHTML = html; }
		return n;
	}

	/* ---------- Dark mode ---------- */
	function initDarkMode( fab ) {
		var STORAGE = 'arbahProTheme';
		var btn = el( 'button', 'arbah-pro-dark', '🌙' );
		btn.type = 'button';

		function apply( mode ) {
			if ( mode === 'dark' ) {
				root.setAttribute( 'data-theme', 'dark' );
				btn.innerHTML = '☀️';
				btn.setAttribute( 'aria-label', labels.light || 'Light mode' );
				btn.setAttribute( 'aria-pressed', 'true' );
			} else {
				root.removeAttribute( 'data-theme' );
				btn.innerHTML = '🌙';
				btn.setAttribute( 'aria-label', labels.dark || 'Dark mode' );
				btn.setAttribute( 'aria-pressed', 'false' );
			}
		}

		var saved = 'light';
		try { saved = localStorage.getItem( STORAGE ) || 'light'; } catch ( e ) {}
		apply( saved );

		btn.addEventListener( 'click', function () {
			var next = root.getAttribute( 'data-theme' ) === 'dark' ? 'light' : 'dark';
			apply( next );
			try { localStorage.setItem( STORAGE, next ); } catch ( e ) {}
		} );

		fab.appendChild( btn );
	}

	/* ---------- Back to top ---------- */
	function initBackToTop( fab ) {
		var btn = el( 'button', 'arbah-pro-top', '↑' );
		btn.type = 'button';
		btn.setAttribute( 'aria-label', labels.top || 'Back to top' );

		btn.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );

		function onScroll() {
			if ( ( window.pageYOffset || root.scrollTop ) > 400 ) {
				btn.classList.add( 'is-visible' );
			} else {
				btn.classList.remove( 'is-visible' );
			}
		}
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();

		fab.appendChild( btn );
	}

	/* ---------- Reading progress (single posts) ---------- */
	function initProgress() {
		if ( ! doc.body.classList.contains( 'single' ) ) { return; }
		var article = doc.querySelector( '.article-content' ) ||
			doc.querySelector( '.entry-content' );
		if ( ! article ) { return; }

		var bar = el( 'div', 'arbah-pro-progress' );
		bar.setAttribute( 'role', 'progressbar' );
		bar.setAttribute( 'aria-hidden', 'true' );
		doc.body.appendChild( bar );

		var ticking = false;
		function update() {
			ticking = false;
			var rect = article.getBoundingClientRect();
			var vh = window.innerHeight || root.clientHeight;
			var total = rect.height - vh;
			var scrolled = -rect.top;
			var pct = total > 0 ? ( scrolled / total ) * 100 : 0;
			pct = Math.max( 0, Math.min( 100, pct ) );
			bar.style.width = pct + '%';
		}
		function onScroll() {
			if ( ! ticking ) {
				ticking = true;
				window.requestAnimationFrame( update );
			}
		}
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		update();
	}

	function ready( fn ) {
		if ( doc.readyState !== 'loading' ) { fn(); }
		else { doc.addEventListener( 'DOMContentLoaded', fn ); }
	}

	ready( function () {
		var fab = el( 'div', 'arbah-pro-fab' );
		if ( cfg.darkMode !== false ) { initDarkMode( fab ); }
		if ( cfg.backToTop !== false ) { initBackToTop( fab ); }
		if ( fab.children.length ) { doc.body.appendChild( fab ); }
		if ( cfg.progress !== false ) { initProgress(); }
	} );
}() );
