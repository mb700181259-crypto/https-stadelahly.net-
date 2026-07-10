/* arbah Pro — infinite article reading (autoload next post).
 * Vanilla JS. Fetches the next post, appends it, and updates the URL / title /
 * view count as each article scrolls into view. No jQuery. */
( function () {
	'use strict';

	var cfg = window.ArbahProAutoload || {};
	var doc = document;
	var contentSel = cfg.contentSelector || '#primary';
	var maxPosts = parseInt( cfg.maxPosts, 10 ) || 10;

	var origin = doc.querySelector( contentSel );
	if ( ! origin || ! ( 'IntersectionObserver' in window ) || ! window.fetch ) {
		return;
	}

	function meta( name, d ) {
		var m = ( d || doc ).querySelector( 'meta[name="' + name + '"]' );
		return m ? m.getAttribute( 'content' ) : '';
	}

	var nextUrl = meta( 'arbah-pro-next', doc );
	var loading = false;
	var loaded = 0;
	var done = false;

	// Article registry — index 0 is the original page.
	var articles = [ {
		href: location.href,
		title: doc.title,
		id: meta( 'arbah-pro-id', doc )
	} ];

	// The origin post is already counted server-side; never re-ping it.
	var viewed = {};
	if ( articles[0].id ) { viewed[ articles[0].id ] = true; }

	function recordView( id ) {
		if ( ! id || viewed[ id ] || ! cfg.viewEndpoint ) { return; }
		viewed[ id ] = true;
		try {
			fetch( cfg.viewEndpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce || ''
				},
				credentials: 'same-origin',
				body: JSON.stringify( { id: parseInt( id, 10 ) } )
			} );
		} catch ( e ) {}
	}

	function setActive( a ) {
		if ( ! a || location.href === a.href ) { return; }
		try { history.replaceState( null, '', a.href ); } catch ( e ) {}
		if ( a.title ) { doc.title = a.title; }
		recordView( a.id );
	}

	// Detect which article the reader is currently on (its top near viewport top).
	var activeObserver = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( en ) {
			if ( en.isIntersecting ) {
				var idx = parseInt( en.target.getAttribute( 'data-al-index' ), 10 );
				setActive( articles[ idx ] );
			}
		} );
	}, { rootMargin: '0px 0px -80% 0px', threshold: 0 } );

	origin.setAttribute( 'data-al-index', '0' );
	activeObserver.observe( origin );

	// Sentinel that triggers the next fetch when it nears the viewport.
	var sentinel = doc.createElement( 'div' );
	sentinel.className = 'arbah-pro-al-sentinel';
	origin.parentNode.insertBefore( sentinel, origin.nextSibling );

	function showEnd() {
		if ( ! cfg.endText ) { return; }
		var e = doc.createElement( 'div' );
		e.className = 'arbah-pro-al-end';
		e.textContent = cfg.endText;
		sentinel.parentNode.insertBefore( e, sentinel );
	}

	function appendNext( html, url ) {
		var parsed = new DOMParser().parseFromString( html, 'text/html' );
		var content = parsed.querySelector( contentSel );
		if ( ! content ) { done = true; return; }

		var idx = articles.length;
		var imported = doc.importNode( content, true );
		imported.id = 'arbah-al-post-' + idx; // avoid duplicate #primary id

		var wrap = doc.createElement( 'div' );
		wrap.className = 'arbah-pro-next-article';
		wrap.setAttribute( 'data-al-index', String( idx ) );
		wrap.setAttribute( 'data-al-label', cfg.nextLabel || '' );
		wrap.appendChild( imported );

		sentinel.parentNode.insertBefore( wrap, sentinel );

		articles.push( {
			href: url,
			title: parsed.title,
			id: meta( 'arbah-pro-id', parsed )
		} );
		activeObserver.observe( wrap );

		loaded++;
		nextUrl = meta( 'arbah-pro-next', parsed );

		if ( ! nextUrl || loaded >= maxPosts ) {
			done = true;
			io.disconnect();
			showEnd();
		}
	}

	function load() {
		if ( loading || done || ! nextUrl ) { return; }
		loading = true;
		var url = nextUrl;
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) { return r.ok ? r.text() : Promise.reject(); } )
			.then( function ( html ) { appendNext( html, url ); } )
			.catch( function () { done = true; io.disconnect(); } )
			.then( function () { loading = false; } );
	}

	var io = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( en ) { if ( en.isIntersecting ) { load(); } } );
	}, { rootMargin: '600px 0px' } );

	if ( nextUrl ) {
		io.observe( sentinel );
	} else {
		done = true;
	}
}() );
