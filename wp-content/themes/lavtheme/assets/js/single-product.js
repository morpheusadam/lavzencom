/**
 * Single product page interactions.
 *
 * @package lavtheme
 */

( function() {
	'use strict';

	// Smooth anchor scroll for in-page navigation.
	const anchorLinks = document.querySelectorAll( 'a[href^="#"]' );
	anchorLinks.forEach( link => {
		link.addEventListener( 'click', function( e ) {
			const href = this.getAttribute( 'href' );
			if ( href !== '#' ) {
				const target = document.querySelector( href );
				if ( target ) {
					e.preventDefault();
					target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			}
		} );
	} );

	// Lazy load related product images.
	const relatedImages = document.querySelectorAll( '.related-thumb img' );
	if ( 'IntersectionObserver' in window ) {
		const imageObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( entry => {
				if ( entry.isIntersecting ) {
					const img = entry.target;
					img.style.opacity = '1';
					observer.unobserve( img );
				}
			} );
		} );

		relatedImages.forEach( img => {
			img.style.opacity = '0';
			img.style.transition = 'opacity 0.3s var(--ease)';
			imageObserver.observe( img );
		} );
	}

	// Add keyboard navigation support.
	document.addEventListener( 'keydown', function( e ) {
		// Escape key handler (future use for modals).
		if ( e.key === 'Escape' ) {
			// Handle closing any open modals if added later.
		}
	} );
} )();
