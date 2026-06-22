/* lavtheme — Single post enhancements (progressive; page works without JS).
   Reading progress (rAF + cached metrics), TOC scroll-spy (IntersectionObserver,
   no per-frame layout), rail/dock actions (AJAX likes, save, copy), comment UX
   (auto-grow, sort, overflow menu, like), and the related carousel.
   transform/opacity-only motion; respects prefers-reduced-motion. */
( function () {
	'use strict';

	var root = document.querySelector( '.lav-single' );
	if ( ! root ) { return; }

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var CFG = window.LavSingle || null;

	/* ============ reading progress (cached metrics, no layout thrash) ============ */
	( function () {
		var prose = document.getElementById( 'lav-prose' );
		var bar = document.querySelector( '#lav-progress span' );
		var left = root.querySelector( '.toc-left' );
		if ( ! prose || ! bar ) { return; }
		var total = left ? ( parseInt( left.getAttribute( 'data-total' ), 10 ) || 0 ) : 0;
		var top = 0, height = 1, ticking = false;

		function measure() {
			var r = prose.getBoundingClientRect();
			top = r.top + window.pageYOffset;
			height = prose.offsetHeight || 1;
			update();
		}
		function update() {
			var scrolled = window.pageYOffset + window.innerHeight * 0.5 - top;
			var pct = Math.max( 0, Math.min( 1, scrolled / height ) );
			bar.style.transform = 'scaleX(' + pct.toFixed( 4 ) + ')';
			if ( left && total ) {
				var m = Math.max( 0, Math.round( total * ( 1 - pct ) ) );
				left.textContent = m <= 0 ? 'Finished' : m + ' min left';
			}
			ticking = false;
		}
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) { ticking = true; window.requestAnimationFrame( update ); }
		}, { passive: true } );
		window.addEventListener( 'resize', measure, { passive: true } );
		if ( document.readyState === 'complete' ) { measure(); } else { window.addEventListener( 'load', measure ); }
		measure();
	} )();

	/* ============ TOC build + scroll-spy (IntersectionObserver) ============ */
	( function () {
		var prose = document.getElementById( 'lav-prose' );
		var toc = document.getElementById( 'lav-toc' );
		if ( ! prose || ! toc ) { return; }
		var list = toc.querySelector( '.toc-list' );
		var heads = Array.prototype.slice.call( prose.querySelectorAll( 'h2' ) );
		if ( heads.length < 2 ) { return; } // not worth a TOC

		var used = {};
		var linkFor = {};
		heads.forEach( function ( h ) {
			if ( ! h.id ) {
				var s = ( h.textContent || 'section' ).toLowerCase().trim()
					.replace( /[^\w؀-ۿ\s-]/g, '' ).replace( /\s+/g, '-' ).replace( /-+/g, '-' );
				s = s || 'section';
				if ( used[ s ] ) { used[ s ]++; s = s + '-' + used[ s ]; } else { used[ s ] = 1; }
				h.id = s;
			}
			var li = document.createElement( 'li' );
			var a = document.createElement( 'a' );
			a.href = '#' + h.id;
			a.textContent = ( h.textContent || '' ).trim();
			a.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				h.scrollIntoView( { behavior: reduce ? 'auto' : 'smooth', block: 'start' } );
			} );
			li.appendChild( a );
			list.appendChild( li );
			linkFor[ h.id ] = a;
		} );
		toc.removeAttribute( 'hidden' );

		var visible = {};
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) { visible[ en.target.id ] = en.isIntersecting ? en.intersectionRatio : 0; } );
			// pick the heading highest on screen that is (or was last) intersecting
			var bestId = null, bestTop = Infinity;
			heads.forEach( function ( h ) {
				var t = h.getBoundingClientRect().top;
				if ( t <= window.innerHeight * 0.4 && t > -h.offsetHeight - 400 ) {
					if ( Math.abs( t ) < bestTop ) { bestTop = Math.abs( t ); bestId = h.id; }
				}
			} );
			Object.keys( linkFor ).forEach( function ( id ) { linkFor[ id ].classList.toggle( 'active', id === bestId ); } );
		}, { rootMargin: '-30% 0px -55% 0px', threshold: [ 0, 1 ] } );
		heads.forEach( function ( h ) { io.observe( h ); } );
	} )();

	/* ============ rail / dock: like (AJAX), save (local), copy ============ */
	function toggleClassAll( sel, cls, on ) {
		Array.prototype.forEach.call( document.querySelectorAll( sel ), function ( el ) {
			el.classList.toggle( cls, on );
			if ( el.hasAttribute( 'aria-pressed' ) ) { el.setAttribute( 'aria-pressed', on ? 'true' : 'false' ); }
		} );
	}

	/* post like (rail + dock share .lav-like) */
	( function () {
		var btns = document.querySelectorAll( '.lav-like' );
		if ( ! btns.length ) { return; }
		var counts = document.querySelectorAll( '.lav-like-count' );
		var liked = false, busy = false;
		var postId = btns[ 0 ].getAttribute( 'data-post' );
		function setCount( txt ) { Array.prototype.forEach.call( counts, function ( c ) { c.textContent = txt; } ); }
		Array.prototype.forEach.call( btns, function ( b ) {
			b.addEventListener( 'click', function () {
				if ( busy ) { return; }
				if ( ! CFG ) { liked = ! liked; toggleClassAll( '.lav-like', 'on', liked ); return; }
				busy = true;
				var body = 'action=lavtheme_like&type=post&id=' + encodeURIComponent( postId ) + '&nonce=' + encodeURIComponent( CFG.nonce );
				fetch( CFG.ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, credentials: 'same-origin', body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( res && res.success ) { liked = !! res.data.liked; toggleClassAll( '.lav-like', 'on', liked ); setCount( res.data.countF ); }
					} )
					.catch( function () {} )
					.then( function () { busy = false; } );
			} );
		} );
	} )();

	/* save (visual + localStorage) */
	( function () {
		var btns = document.querySelectorAll( '.lav-save' );
		if ( ! btns.length ) { return; }
		var keyEl = document.querySelector( '.lav-like' );
		var pid = keyEl ? keyEl.getAttribute( 'data-post' ) : '0';
		var k = 'lav_saved_' + pid;
		var on = false;
		try { on = window.localStorage.getItem( k ) === '1'; } catch ( e ) {}
		toggleClassAll( '.lav-save', 'on', on );
		Array.prototype.forEach.call( btns, function ( b ) {
			b.addEventListener( 'click', function () {
				on = ! on;
				toggleClassAll( '.lav-save', 'on', on );
				try { on ? window.localStorage.setItem( k, '1' ) : window.localStorage.removeItem( k ); } catch ( e ) {}
			} );
		} );
	} )();

	/* copy link */
	Array.prototype.forEach.call( document.querySelectorAll( '.lav-copy' ), function ( b ) {
		b.addEventListener( 'click', function () {
			var url = b.getAttribute( 'data-url' ) || window.location.href;
			var done = function () {
				Array.prototype.forEach.call( document.querySelectorAll( '.lav-copy' ), function ( x ) {
					x.classList.add( 'copied' ); window.setTimeout( function () { x.classList.remove( 'copied' ); }, 1600 );
				} );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) { navigator.clipboard.writeText( url ).then( done ).catch( done ); }
			else { done(); }
		} );
	} );

	/* ============ comments: auto-grow, sort, like, overflow menu ============ */
	( function () {
		var ta = document.querySelector( '.cm-textarea' );
		if ( ta ) {
			var grow = function () { ta.style.height = 'auto'; ta.style.height = Math.min( ta.scrollHeight, 360 ) + 'px'; };
			ta.addEventListener( 'input', grow );
			ta.addEventListener( 'focus', grow );
		}

		var sort = document.querySelector( '.cm-sort-sel' );
		if ( sort ) {
			sort.addEventListener( 'change', function () {
				var u = new URL( window.location.href );
				u.searchParams.set( 'csort', sort.value );
				u.hash = 'comments';
				window.location.href = u.toString();
			} );
		}

		/* comment likes (AJAX) */
		Array.prototype.forEach.call( document.querySelectorAll( '.cm-like' ), function ( b ) {
			var busy = false, liked = false;
			b.addEventListener( 'click', function () {
				if ( busy ) { return; }
				var id = b.getAttribute( 'data-comment' );
				var cEl = b.querySelector( '.cm-like-count' );
				if ( ! CFG ) { liked = ! liked; b.classList.toggle( 'on', liked ); b.setAttribute( 'aria-pressed', liked ); return; }
				busy = true;
				var body = 'action=lavtheme_like&type=comment&id=' + encodeURIComponent( id ) + '&nonce=' + encodeURIComponent( CFG.nonce );
				fetch( CFG.ajaxUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, credentials: 'same-origin', body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( res && res.success ) { liked = !! res.data.liked; b.classList.toggle( 'on', liked ); b.setAttribute( 'aria-pressed', liked ); if ( cEl ) { cEl.textContent = res.data.countF; } }
					} )
					.catch( function () {} )
					.then( function () { busy = false; } );
			} );
		} );

		/* overflow menus */
		Array.prototype.forEach.call( document.querySelectorAll( '.cm-more-btn' ), function ( b ) {
			var menu = b.parentNode.querySelector( '.cm-menu' );
			if ( ! menu ) { return; }
			b.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				var open = ! menu.hasAttribute( 'hidden' );
				closeMenus();
				if ( open ) { return; }
				menu.removeAttribute( 'hidden' ); b.setAttribute( 'aria-expanded', 'true' );
			} );
		} );
		function closeMenus() {
			Array.prototype.forEach.call( document.querySelectorAll( '.cm-menu' ), function ( m ) { m.setAttribute( 'hidden', '' ); } );
			Array.prototype.forEach.call( document.querySelectorAll( '.cm-more-btn' ), function ( x ) { x.setAttribute( 'aria-expanded', 'false' ); } );
		}
		document.addEventListener( 'click', closeMenus );
		document.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { closeMenus(); } } );
	} )();

	/* ============ related carousel ============ */
	( function () {
		var viewport = root.querySelector( '.rel-viewport' );
		var track = root.querySelector( '.rel-track' );
		if ( ! viewport || ! track ) { return; }
		var items = Array.prototype.slice.call( track.querySelectorAll( '.rel-item' ) );
		var prev = root.querySelector( '.rel-arrow[data-dir="prev"]' );
		var next = root.querySelector( '.rel-arrow[data-dir="next"]' );
		var dotsWrap = root.querySelector( '.rel-dots' );
		if ( ! items.length ) { return; }

		var index = 0, maxIndex = 0, stops = [];
		var dragging = false, startX = 0, startScroll = 0, moved = 0, pid = null;

		function px( v ) { return parseFloat( v ) || 0; }
		function measure() {
			track.style.transition = 'none';
			track.style.transform = 'translateX(0px)';
			void track.offsetWidth;
			var vpW = viewport.clientWidth;
			var cs = getComputedStyle( track );
			var gap = px( cs.columnGap || cs.gap );
			var step = items[ 0 ].offsetWidth + gap;
			var perPage = Math.max( 1, Math.round( ( vpW + gap ) / step ) );
			var maxScroll = Math.max( 0, track.scrollWidth - vpW );
			stops = [];
			for ( var i = 0; i < items.length; i += perPage ) {
				stops.push( Math.min( items[ i ].offsetLeft - px( cs.paddingLeft ), maxScroll ) );
			}
			stops = stops.filter( function ( v, i, a ) { return i === 0 || v !== a[ i - 1 ]; } );
			if ( stops.length && stops[ stops.length - 1 ] < maxScroll - 1 ) { stops.push( maxScroll ); }
			maxIndex = stops.length - 1;
			if ( index > maxIndex ) { index = maxIndex; }
			buildDots();
			apply( false );
		}
		function buildDots() {
			if ( ! dotsWrap ) { return; }
			dotsWrap.innerHTML = '';
			var fits = stops.length <= 1;
			dotsWrap.style.display = fits ? 'none' : 'flex';
			if ( prev && next ) { prev.style.display = next.style.display = fits ? 'none' : 'grid'; }
			for ( var i = 0; i < stops.length; i++ ) {
				( function ( i ) {
					var b = document.createElement( 'b' );
					b.setAttribute( 'role', 'tab' );
					b.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
					b.addEventListener( 'click', function () { go( i ); } );
					dotsWrap.appendChild( b );
				} )( i );
			}
		}
		function apply( animate ) {
			var x = stops.length ? stops[ index ] : 0;
			track.style.transition = ( animate && ! reduce ) ? 'transform .5s var(--ease)' : 'none';
			track.style.transform = 'translateX(' + ( -x ) + 'px)';
			if ( prev ) { prev.disabled = index <= 0; }
			if ( next ) { next.disabled = index >= maxIndex; }
			if ( dotsWrap ) {
				Array.prototype.forEach.call( dotsWrap.children, function ( d, i ) {
					d.classList.toggle( 'on', i === index );
					d.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
				} );
			}
		}
		function go( i ) { index = Math.max( 0, Math.min( maxIndex, i ) ); apply( true ); }

		if ( next ) { next.addEventListener( 'click', function () { go( index + 1 ); } ); }
		if ( prev ) { prev.addEventListener( 'click', function () { go( index - 1 ); } ); }
		track.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowRight' ) { e.preventDefault(); go( index + 1 ); }
			else if ( e.key === 'ArrowLeft' ) { e.preventDefault(); go( index - 1 ); }
		} );

		function curX() { var m = /translateX\(([-0-9.]+)px\)/.exec( track.style.transform ); return m ? parseFloat( m[ 1 ] ) : 0; }
		track.addEventListener( 'pointerdown', function ( e ) {
			if ( e.button && e.button !== 0 ) { return; }
			dragging = true; moved = 0; pid = e.pointerId; startX = e.clientX; startScroll = curX();
			track.classList.add( 'dragging' );
			if ( track.setPointerCapture ) { track.setPointerCapture( e.pointerId ); }
		} );
		track.addEventListener( 'pointermove', function ( e ) {
			if ( ! dragging ) { return; }
			moved = e.clientX - startX;
			var x = startScroll + moved, min = -stops[ maxIndex ], max = 0;
			if ( x > max ) { x = max + ( x - max ) * 0.35; }
			if ( x < min ) { x = min + ( x - min ) * 0.35; }
			track.style.transition = 'none';
			track.style.transform = 'translateX(' + x + 'px)';
		} );
		function endDrag() {
			if ( ! dragging ) { return; }
			dragging = false; track.classList.remove( 'dragging' );
			if ( track.releasePointerCapture && pid != null ) { try { track.releasePointerCapture( pid ); } catch ( e ) {} }
			var threshold = Math.min( 80, viewport.clientWidth * 0.18 );
			if ( moved <= -threshold ) { go( index + 1 ); }
			else if ( moved >= threshold ) { go( index - 1 ); }
			else { apply( true ); }
		}
		track.addEventListener( 'pointerup', endDrag );
		track.addEventListener( 'pointercancel', endDrag );
		track.addEventListener( 'click', function ( e ) { if ( Math.abs( moved ) > 6 ) { e.preventDefault(); } }, true );
		track.addEventListener( 'dragstart', function ( e ) { e.preventDefault(); } );

		var rt;
		window.addEventListener( 'resize', function () { clearTimeout( rt ); rt = setTimeout( measure, 150 ); } );
		if ( document.readyState === 'complete' ) { measure(); } else { window.addEventListener( 'load', measure ); }
		measure();
	} )();
}() );
