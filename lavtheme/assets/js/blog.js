/**
 * Blog archive interactions — progressive enhancement only. Filtering is a real
 * GET form (works with JS off; selects auto-submit, category pills are links).
 * This just adds instant visual sync between the category pills and the dropdown
 * before the page reloads.
 *
 * @package lavtheme
 */
( function() {
	'use strict';

	var root = document.querySelector( '.lav-blog' );
	if ( ! root ) {
		return;
	}

	var sel = root.querySelector( 'select[name="bcat"]' );
	if ( sel ) {
		sel.addEventListener( 'change', function() {
			Array.prototype.forEach.call( root.querySelectorAll( '.cpill' ), function( p ) {
				p.classList.toggle( 'active', ( p.getAttribute( 'data-cat' ) || '' ) === sel.value );
			} );
		} );
	}
} )();
