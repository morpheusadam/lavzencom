/* WP Dash — count-up numbers + radial ring fill. CSS owns the rest of the
   animation. Respects prefers-reduced-motion. No dependencies. */
( function () {
	'use strict';

	var root = document.querySelector( '[data-lavd]' );
	if ( ! root ) { return; }

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* count-up */
	function countUp( el ) {
		var target = parseFloat( el.getAttribute( 'data-count' ) ) || 0;
		var suffix = el.getAttribute( 'data-suffix' ) || '';
		if ( reduce || target === 0 ) {
			el.textContent = Math.round( target ) + suffix;
			return;
		}
		var dur = 1100, start = null;
		function step( t ) {
			if ( ! start ) { start = t; }
			var p = Math.min( ( t - start ) / dur, 1 );
			var eased = 1 - Math.pow( 1 - p, 3 );
			el.textContent = Math.round( target * eased ) + suffix;
			if ( p < 1 ) { window.requestAnimationFrame( step ); }
			else { el.textContent = Math.round( target ) + suffix; }
		}
		window.requestAnimationFrame( step );
	}

	/* radial rings: set stroke-dashoffset to the target percentage */
	function fillRing( el ) {
		var pct = parseFloat( el.getAttribute( 'data-pct' ) ) || 0;
		var r = parseFloat( el.getAttribute( 'r' ) ) || 26;
		var circ = 2 * Math.PI * r;
		var off = circ * ( 1 - pct / 100 );
		if ( reduce ) { el.style.transition = 'none'; }
		// next frame so the transition runs from the full-offset start
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () { el.style.strokeDashoffset = off; } );
		} );
	}

	function run() {
		Array.prototype.forEach.call( root.querySelectorAll( '[data-count]' ), countUp );
		Array.prototype.forEach.call( root.querySelectorAll( '.lavd-ring-fg' ), fillRing );
	}

	if ( 'IntersectionObserver' in window ) {
		var io = new IntersectionObserver( function ( entries, obs ) {
			entries.forEach( function ( e ) { if ( e.isIntersecting ) { run(); obs.disconnect(); } } );
		}, { threshold: 0.2 } );
		io.observe( root );
	} else {
		run();
	}
}() );
