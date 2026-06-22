/* WP Dash — native Dashboard hero enhancement: count-up the quick stats.
   Progressive; respects prefers-reduced-motion. No dependencies. */
( function () {
	'use strict';
	var hero = document.querySelector( '.lavwp-hero' );
	if ( ! hero ) { return; }

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	Array.prototype.forEach.call( hero.querySelectorAll( '.lavwp-stat-n[data-count]' ), function ( el ) {
		var target = parseFloat( el.getAttribute( 'data-count' ) ) || 0;
		if ( reduce || target === 0 ) { el.textContent = String( Math.round( target ) ); return; }
		var dur = 1000, start = null;
		function step( t ) {
			if ( ! start ) { start = t; }
			var p = Math.min( ( t - start ) / dur, 1 );
			var eased = 1 - Math.pow( 1 - p, 3 );
			el.textContent = String( Math.round( target * eased ) );
			if ( p < 1 ) { window.requestAnimationFrame( step ); }
			else { el.textContent = String( Math.round( target ) ); }
		}
		window.requestAnimationFrame( step );
	} );
}() );
