/**
 * Single product page interactions ("product.html" design).
 * Tabs, banner "Support" chip → tab, gallery scroll nav, and the mobile sticky
 * buy bar proxying the real EDD add-to-cart button. Progressive enhancement.
 *
 * @package lavtheme
 */
( function() {
	'use strict';

	var root = document.querySelector( '.lav-product' );
	if ( ! root ) {
		return;
	}

	var tabs   = root.querySelectorAll( '.tab' );
	var panels = root.querySelectorAll( '.panel' );

	function activate( key ) {
		tabs.forEach( function( t ) {
			var on = t.getAttribute( 'data-panel' ) === key;
			t.classList.toggle( 'is-active', on );
			t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		} );
		panels.forEach( function( p ) {
			p.classList.toggle( 'is-active', p.getAttribute( 'data-panel' ) === key );
		} );
	}

	tabs.forEach( function( t ) {
		t.addEventListener( 'click', function() {
			activate( t.getAttribute( 'data-panel' ) );
		} );
	} );

	// Banner chip that targets a tab (Support).
	root.querySelectorAll( '[data-lav-tab]' ).forEach( function( c ) {
		c.addEventListener( 'click', function( e ) {
			e.preventDefault();
			activate( c.getAttribute( 'data-lav-tab' ) );
			var tw = root.querySelector( '.tabs-wrap' );
			if ( tw ) {
				tw.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		} );
	} );

	// Gallery prev/next (scrolls the carousel when it overflows).
	var gallery = root.querySelector( '.gallery' );
	root.querySelectorAll( '.gal-nav [data-gal]' ).forEach( function( btn ) {
		btn.addEventListener( 'click', function() {
			if ( ! gallery ) {
				return;
			}
			var item = gallery.querySelector( '.gal-item' );
			var step = item ? item.getBoundingClientRect().width + 12 : 200;
			gallery.scrollBy( { left: 'next' === btn.getAttribute( 'data-gal' ) ? step : -step, behavior: 'smooth' } );
		} );
	} );

	// Mobile sticky bar → trigger the real EDD add-to-cart in the buy card.
	var proxy = document.querySelector( '.mobile-buybar [data-lav-buy-proxy]' );
	if ( proxy ) {
		proxy.addEventListener( 'click', function( e ) {
			e.preventDefault();
			var real = root.querySelector( '.s-buy .edd-add-to-cart' ) ||
				root.querySelector( '.s-buy a.btn, .s-buy button.btn, .s-buy input.edd-submit' );
			if ( real ) {
				real.click();
			} else {
				var card = root.querySelector( '#lav-buy' );
				if ( card ) {
					card.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				}
			}
		} );
	}
} )();
