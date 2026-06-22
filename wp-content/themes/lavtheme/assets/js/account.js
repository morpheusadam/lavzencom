/**
 * lavtheme — My Account dashboard (progressive enhancement only; the page is
 * fully functional with JS off — navigation is real links, data is server-
 * rendered EDD output). This just brings the active nav item into view on the
 * mobile horizontal nav.
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
} )();
