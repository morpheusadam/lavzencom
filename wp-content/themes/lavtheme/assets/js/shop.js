/**
 * Shop archive interactions — progressive enhancement only.
 * Without JS the filter form still works (it is a plain GET form); JS just adds
 * the mobile filter drawer and small UX niceties.
 *
 * @package lavtheme
 */
( function() {
	'use strict';

	var filters = document.getElementById( 'lav-filters' );
	var toggle  = document.querySelector( '.lav-filters-toggle' );
	var overlay = document.querySelector( '.lav-shop-overlay' );

	if ( ! filters || ! toggle ) {
		return;
	}

	function openDrawer() {
		filters.classList.add( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		if ( overlay ) {
			overlay.hidden = false;
		}
		document.body.style.overflow = 'hidden';
	}

	function closeDrawer() {
		filters.classList.remove( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		if ( overlay ) {
			overlay.hidden = true;
		}
		document.body.style.overflow = '';
	}

	toggle.addEventListener( 'click', function() {
		if ( filters.classList.contains( 'is-open' ) ) {
			closeDrawer();
		} else {
			openDrawer();
		}
	} );

	if ( overlay ) {
		overlay.addEventListener( 'click', closeDrawer );
	}

	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' && filters.classList.contains( 'is-open' ) ) {
			closeDrawer();
		}
	} );

	// Reset to the desktop state if the viewport grows past the drawer breakpoint.
	var mq = window.matchMedia( '(min-width: 1025px)' );
	var onChange = function( e ) {
		if ( e.matches ) {
			closeDrawer();
		}
	};
	if ( mq.addEventListener ) {
		mq.addEventListener( 'change', onChange );
	} else if ( mq.addListener ) {
		mq.addListener( onChange );
	}

	// Keep the two price inputs sane: max should never drop below min.
	var minEl = filters.querySelector( 'input[name="min"]' );
	var maxEl = filters.querySelector( 'input[name="max"]' );
	if ( minEl && maxEl ) {
		minEl.addEventListener( 'change', function() {
			if ( minEl.value !== '' ) {
				maxEl.min = minEl.value;
			}
		} );
	}
} )();
