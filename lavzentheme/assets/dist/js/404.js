/* lavtheme — Error page enhancement. Progressive: the page works fully without
   JS. Adds a subtle pointer parallax to the big code and autofocuses search.
   Respects prefers-reduced-motion. No dependencies. */
( function () {
	'use strict';

	var root = document.querySelector( '.lav-404' );
	if ( ! root ) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var code = root.querySelector( '[data-parallax]' );

	if ( code && ! reduce && window.matchMedia( '(hover: hover)' ).matches ) {
		root.addEventListener( 'pointermove', function ( e ) {
			var r = root.getBoundingClientRect();
			var dx = ( ( e.clientX - r.left ) / r.width - 0.5 ) * 18;
			var dy = ( ( e.clientY - r.top ) / r.height - 0.5 ) * 14;
			code.style.transform = 'translate3d(' + dx.toFixed(1) + 'px,' + dy.toFixed(1) + 'px,0)';
		} );
		root.addEventListener( 'pointerleave', function () {
			code.style.transform = '';
		} );
	}
}() );
