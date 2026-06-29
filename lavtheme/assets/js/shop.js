/**
 * Shop archive interactions — progressive enhancement only. The filters are a
 * real GET form (work with JS off); this adds the dual-range slider UI, grid/
 * list toggle, a localStorage wishlist, the quick-view modal, and the mobile
 * filter drawer.
 *
 * @package lavtheme
 */
( function() {
	'use strict';

	var root = document.querySelector( '.lav-shop' );
	if ( ! root ) {
		return;
	}
	var $  = function( s, c ) { return ( c || root ).querySelector( s ); };
	var $$ = function( s, c ) { return Array.prototype.slice.call( ( c || root ).querySelectorAll( s ) ); };

	/* ---- mobile filter drawer ---- */
	var filters = $( '#filters' ) || $( '.filters' );
	var toggle  = $( '#filterToggle' );
	var overlay = $( '.lav-shop-overlay' );
	function openDrawer() { if ( ! filters ) { return; } filters.classList.add( 'is-open' ); if ( toggle ) { toggle.setAttribute( 'aria-expanded', 'true' ); } if ( overlay ) { overlay.hidden = false; } document.body.style.overflow = 'hidden'; }
	function closeDrawer() { if ( ! filters ) { return; } filters.classList.remove( 'is-open' ); if ( toggle ) { toggle.setAttribute( 'aria-expanded', 'false' ); } if ( overlay ) { overlay.hidden = true; } document.body.style.overflow = ''; }
	if ( toggle ) {
		toggle.addEventListener( 'click', function() { filters.classList.contains( 'is-open' ) ? closeDrawer() : openDrawer(); } );
	}
	if ( overlay ) { overlay.addEventListener( 'click', closeDrawer ); }

	/* ---- category label active state ---- */
	$$( 'label.cat input[type=checkbox]' ).forEach( function( cb ) {
		cb.addEventListener( 'change', function() { cb.closest( '.cat' ).classList.toggle( 'active', cb.checked ); } );
	} );

	/* ---- dual range slider ---- */
	var rMin = $( '#rMin' ), rMax = $( '#rMax' ), fill = $( '#fill' ), bubMin = $( '#bubMin' ), bubMax = $( '#bubMax' );
	if ( rMin && rMax && fill ) {
		var lo = +rMin.min, hi = +rMin.max, span = ( hi - lo ) || 1;
		var money = function( v ) { return '$' + v; };
		var paint = function( changed ) {
			var mn = +rMin.value, mx = +rMax.value, gap = Math.max( 1, Math.round( span * 0.02 ) );
			if ( mn > mx - gap ) {
				if ( changed === rMin ) { rMin.value = ( mn = mx - gap ); }
				else { rMax.value = ( mx = mn + gap ); }
			}
			fill.style.left  = ( ( mn - lo ) / span * 100 ) + '%';
			fill.style.width = ( ( mx - mn ) / span * 100 ) + '%';
			if ( bubMin ) { bubMin.textContent = money( mn ); }
			if ( bubMax ) { bubMax.textContent = money( mx ); }
			rMin.setAttribute( 'aria-valuetext', money( mn ) );
			rMax.setAttribute( 'aria-valuetext', money( mx ) );
		};
		rMin.addEventListener( 'input', function() { paint( rMin ); } );
		rMax.addEventListener( 'input', function() { paint( rMax ); } );
		paint();
	}

	/* ---- grid / list view (persisted) ---- */
	var grid = $( '#grid' );
	var viewBtns = $$( '.view-toggle button' );
	function setView( v ) {
		if ( grid ) { grid.classList.toggle( 'is-list', v === 'list' ); }
		viewBtns.forEach( function( b ) { b.classList.toggle( 'active', b.getAttribute( 'data-view' ) === v ); } );
		try { localStorage.setItem( 'lavShopView', v ); } catch ( e ) {}
	}
	var savedView = null;
	try { savedView = localStorage.getItem( 'lavShopView' ); } catch ( e ) {}
	if ( savedView ) { setView( savedView ); }
	viewBtns.forEach( function( b ) { b.addEventListener( 'click', function() { setView( b.getAttribute( 'data-view' ) ); } ); } );

	/* ---- wishlist (localStorage) ---- */
	function wl() { try { return JSON.parse( localStorage.getItem( 'lavShopWishlist' ) || '[]' ); } catch ( e ) { return []; } }
	function wlSave( a ) { try { localStorage.setItem( 'lavShopWishlist', JSON.stringify( a ) ); } catch ( e ) {} }
	var saved = wl();
	$$( '.pfav' ).forEach( function( b ) {
		var id = b.getAttribute( 'data-fav' );
		if ( saved.indexOf( id ) > -1 ) { b.classList.add( 'is-on' ); b.setAttribute( 'aria-pressed', 'true' ); }
		b.addEventListener( 'click', function( e ) {
			e.preventDefault();
			var list = wl(), i = list.indexOf( id );
			if ( i > -1 ) { list.splice( i, 1 ); b.classList.remove( 'is-on' ); b.setAttribute( 'aria-pressed', 'false' ); }
			else { list.push( id ); b.classList.add( 'is-on' ); b.setAttribute( 'aria-pressed', 'true' ); }
			wlSave( list );
		} );
	} );

	/* ---- quick view ---- */
	var qv = document.getElementById( 'lavQv' );
	var qvLast = null;
	function qvOpen( card, trigger ) {
		if ( ! qv ) { return; }
		qvLast = trigger || null;
		var d = function( k ) { return card.getAttribute( 'data-' + k ) || ''; };
		var set = function( sel, val, attr ) { var el = qv.querySelector( sel ); if ( ! el ) { return; } if ( attr ) { el.setAttribute( attr, val ); } else { el.textContent = val; } };
		set( '[data-qv-img]', d( 'img' ), 'src' );
		set( '[data-qv-img]', d( 'title' ), 'alt' );
		set( '[data-qv-cat]', d( 'cat' ) );
		set( '[data-qv-title]', d( 'title' ) );
		set( '[data-qv-price]', d( 'price' ) );
		set( '[data-qv-excerpt]', d( 'excerpt' ) );
		set( '[data-qv-link]', d( 'url' ), 'href' );
		qv.hidden = false;
		document.body.style.overflow = 'hidden';
		var closeBtn = qv.querySelector( '[data-qv-close]' );
		if ( closeBtn ) { closeBtn.focus(); }
	}
	function qvClose() {
		if ( ! qv ) { return; }
		qv.hidden = true;
		document.body.style.overflow = '';
		if ( qvLast ) { qvLast.focus(); qvLast = null; }
	}
	root.addEventListener( 'click', function( e ) {
		var q = e.target.closest( '.pquick' );
		if ( q ) { e.preventDefault(); qvOpen( q.closest( '.pcard' ), q ); }
	} );
	if ( qv ) {
		qv.addEventListener( 'click', function( e ) { if ( e.target.closest( '[data-qv-close]' ) ) { qvClose(); } } );
		// Focus trap inside the dialog (keeps Tab within the modal).
		qv.addEventListener( 'keydown', function( e ) {
			if ( e.key !== 'Tab' ) { return; }
			var f = Array.prototype.slice.call( qv.querySelectorAll( 'a[href],button,input,select,textarea,[tabindex]:not([tabindex="-1"])' ) ).filter( function( el ) { return ! el.disabled && el.offsetParent !== null; } );
			if ( ! f.length ) { return; }
			var first = f[ 0 ], last = f[ f.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
			else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
		} );
	}

	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) { qvClose(); closeDrawer(); }
	} );

	/* ---- reset drawer on desktop ---- */
	var mq = window.matchMedia( '(min-width: 1025px)' );
	var onChange = function( e ) { if ( e.matches ) { closeDrawer(); } };
	if ( mq.addEventListener ) { mq.addEventListener( 'change', onChange ); } else if ( mq.addListener ) { mq.addListener( onChange ); }
} )();
