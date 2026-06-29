/**
 * lavtheme — My Account dashboard (progressive enhancement; the page works with
 * JS off — navigation is real links, data is server-rendered EDD output).
 *
 *  1) Bring the active nav item into view on the mobile horizontal nav.
 *  2) Make EDD data tables responsive: read each table's column headers and
 *     stamp every cell with data-th, so the CSS can render labelled stacked
 *     cards on small screens (version-agnostic — reads the real <thead>).
 *
 * @package lavtheme
 */
( function () {
	'use strict';

	var root = document.querySelector( '.lav-account' );
	if ( ! root ) {
		return;
	}

	var active = root.querySelector( '.la-navitem.is-active' );
	if ( active && active.scrollIntoView ) {
		try {
			active.scrollIntoView( { inline: 'center', block: 'nearest' } );
		} catch ( e ) {}
	}

	Array.prototype.forEach.call( root.querySelectorAll( '.la-edd table' ), function ( table ) {
		var ths   = table.querySelectorAll( 'thead th' );
		var heads = [];
		Array.prototype.forEach.call( ths, function ( th ) { heads.push( th.textContent.replace( /\s+/g, ' ' ).trim() ); } );
		if ( ! heads.length ) {
			return;
		}
		table.classList.add( 'la-rtable' );
		Array.prototype.forEach.call( table.querySelectorAll( 'tbody tr' ), function ( tr ) {
			Array.prototype.forEach.call( tr.children, function ( td, i ) {
				if ( heads[ i ] && ! td.hasAttribute( 'data-th' ) ) {
					td.setAttribute( 'data-th', heads[ i ] );
				}
			} );
		} );
	} );
} )();
