/* lavtheme — Single post enhancements. Progressive: the page is fully functional
   without JS; this only adds a reading-progress bar, heading anchors, and a
   copy-link button. No dependencies. */
( function () {
	'use strict';

	var root = document.querySelector( '.lav-single' );
	if ( ! root ) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* ---- reading progress ---- */
	var bar = root.querySelector( '.reading-progress span' );
	var article = root.querySelector( '.art-content' );
	if ( bar && article ) {
		var ticking = false;
		var update = function () {
			var rect = article.getBoundingClientRect();
			var total = rect.height - window.innerHeight + 120;
			var scrolled = -rect.top + 80;
			var pct = total > 0 ? ( scrolled / total ) * 100 : ( rect.top < 0 ? 100 : 0 );
			bar.style.width = Math.max( 0, Math.min( 100, pct ) ) + '%';
			ticking = false;
		};
		var onScroll = function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		window.addEventListener( 'resize', onScroll, { passive: true } );
		update();
	}

	/* ---- heading anchors (deep-linkable sections) ---- */
	if ( article ) {
		var used = {};
		var slug = function ( txt ) {
			var s = txt.toLowerCase().trim().replace( /[^\w؀-ۿ\s-]/g, '' ).replace( /\s+/g, '-' ).replace( /-+/g, '-' );
			s = s || 'section';
			if ( used[ s ] ) {
				used[ s ]++;
				s = s + '-' + used[ s ];
			} else {
				used[ s ] = 1;
			}
			return s;
		};
		var heads = article.querySelectorAll( 'h2, h3' );
		Array.prototype.forEach.call( heads, function ( h ) {
			if ( ! h.id ) {
				h.id = slug( h.textContent );
			}
			var a = document.createElement( 'a' );
			a.className = 'anchor';
			a.href = '#' + h.id;
			a.setAttribute( 'aria-label', 'Link to this section' );
			a.textContent = '#';
			h.appendChild( a );
		} );
	}

	/* ---- copy link ---- */
	var copyBtn = root.querySelector( '.srb-copy' );
	if ( copyBtn ) {
		copyBtn.addEventListener( 'click', function () {
			var url = copyBtn.getAttribute( 'data-url' ) || window.location.href;
			var done = function () {
				copyBtn.classList.add( 'copied' );
				window.setTimeout( function () {
					copyBtn.classList.remove( 'copied' );
				}, 1800 );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( done ).catch( done );
			} else {
				var ta = document.createElement( 'textarea' );
				ta.value = url;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild( ta );
				ta.select();
				try { document.execCommand( 'copy' ); } catch ( e ) {}
				document.body.removeChild( ta );
				done();
			}
		} );
	}
}() );
