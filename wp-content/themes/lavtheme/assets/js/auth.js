/**
 * lavtheme — Login / Register page (progressive enhancement). The page works
 * with JS off: the server picks the tab from ?action=register, forms submit
 * normally, validation falls back to the browser. This adds the tab toggle,
 * password show/hide, inline validation, and a submit loading state.
 *
 * @package lavtheme
 */
( function () {
	'use strict';

	var root = document.querySelector( '.lav-auth' );
	if ( ! root ) {
		return;
	}

	/* ---- tab toggle (Sign in / Create account) ---- */
	var tabs = Array.prototype.slice.call( root.querySelectorAll( '.la-tab' ) );
	function showTab( name ) {
		var reg = name === 'register';
		root.classList.toggle( 'show-register', reg );
		tabs.forEach( function ( t ) {
			t.setAttribute( 'aria-selected', t.getAttribute( 'data-auth-tab' ) === name ? 'true' : 'false' );
		} );
		// reflect in the URL without reloading
		try {
			var u = new URL( window.location.href );
			if ( reg ) { u.searchParams.set( 'action', 'register' ); } else { u.searchParams.delete( 'action' ); }
			window.history.replaceState( {}, '', u.toString() );
		} catch ( e ) {}
		// focus the first field of the shown pane
		var pane = root.querySelector( reg ? '.la-pane-register' : '.la-pane-login' );
		var first = pane && pane.querySelector( 'input:not([type=hidden])' );
		if ( first ) { try { first.focus(); } catch ( e ) {} }
	}
	root.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-auth-tab]' );
		if ( btn ) { e.preventDefault(); showTab( btn.getAttribute( 'data-auth-tab' ) ); }
	} );

	/* ---- password show / hide ---- */
	root.querySelectorAll( '[data-pwtoggle]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var input = btn.parentNode.querySelector( 'input' );
			if ( ! input ) { return; }
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			btn.classList.toggle( 'is-on', show );
			btn.setAttribute( 'aria-pressed', show ? 'true' : 'false' );
			btn.setAttribute( 'aria-label', show ? 'Hide password' : 'Show password' );
		} );
	} );

	/* ---- inline validation + submit loading ---- */
	function fieldOf( input ) { return input.closest( '.la-field' ); }
	function setError( input, msg ) {
		var f = fieldOf( input );
		if ( ! f ) { return; }
		f.classList.toggle( 'is-invalid', !! msg );
		var el = f.querySelector( '.la-fielderr' );
		if ( msg ) {
			if ( ! el ) { el = document.createElement( 'span' ); el.className = 'la-fielderr'; f.appendChild( el ); }
			el.textContent = msg;
			input.setAttribute( 'aria-invalid', 'true' );
		} else if ( el ) {
			el.textContent = '';
			input.removeAttribute( 'aria-invalid' );
		}
	}
	function validate( input ) {
		var v = ( input.value || '' ).trim();
		if ( input.hasAttribute( 'required' ) && ! v ) { setError( input, 'This field is required.' ); return false; }
		if ( input.type === 'email' && v && ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( v ) ) { setError( input, 'Enter a valid email address.' ); return false; }
		setError( input, '' );
		return true;
	}

	root.querySelectorAll( '.la-pane' ).forEach( function ( form ) {
		if ( form.tagName !== 'FORM' ) { return; }
		var inputs = Array.prototype.slice.call( form.querySelectorAll( 'input[required], input[type=email]' ) );
		inputs.forEach( function ( i ) {
			i.addEventListener( 'blur', function () { validate( i ); } );
			i.addEventListener( 'input', function () { if ( fieldOf( i ) && fieldOf( i ).classList.contains( 'is-invalid' ) ) { validate( i ); } } );
		} );
		form.addEventListener( 'submit', function ( e ) {
			var ok = true, firstBad = null;
			inputs.forEach( function ( i ) { if ( ! validate( i ) ) { ok = false; if ( ! firstBad ) { firstBad = i; } } } );
			if ( ! ok ) {
				e.preventDefault();
				if ( firstBad ) { try { firstBad.focus(); } catch ( er ) {} }
				return;
			}
			var btn = form.querySelector( '.la-submit' );
			if ( btn ) { btn.classList.add( 'is-loading' ); }
		} );
	} );
} )();
