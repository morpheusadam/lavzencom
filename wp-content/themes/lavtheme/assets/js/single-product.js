/**
 * Single product page interactions.
 * Advanced features: smooth scroll, lazy loading, analytics.
 *
 * @package lavtheme
 */

( function() {
	'use strict';

	// Track product page view.
	if ( typeof window.gtag === 'function' ) {
		gtag( 'event', 'view_item', {
			items: [ { item_name: document.title } ]
		} );
	}

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
					
					// Update URL without page reload.
					window.history.pushState( null, null, href );
				}
			}
		} );
	} );

	// Lazy load related product images with IntersectionObserver.
	const relatedImages = document.querySelectorAll( '.related-thumb img' );
	if ( 'IntersectionObserver' in window ) {
		const imageObserver = new IntersectionObserver( ( entries, observer ) => {
			entries.forEach( entry => {
				if ( entry.isIntersecting ) {
					const img = entry.target;
					if ( img.dataset.src ) {
						img.src = img.dataset.src;
						img.removeAttribute( 'data-src' );
					}
					img.style.opacity = '1';
					observer.unobserve( img );
				}
			} );
		}, { rootMargin: '50px' } );

		relatedImages.forEach( img => {
			img.style.opacity = '0';
			img.style.transition = 'opacity 0.3s cubic-bezier(.2,.7,.2,1)';
			imageObserver.observe( img );
		} );
	}

	// Track product interactions for analytics.
	document.addEventListener( 'click', function( e ) {
		// Track purchase button clicks.
		if ( e.target.closest( '.product-actions .btn' ) ) {
			if ( typeof window.gtag === 'function' ) {
				gtag( 'event', 'add_to_cart', {
					value: document.querySelector( '.amount' )?.textContent || '0',
					currency: 'USD'
				} );
			}
		}

		// Track related product clicks.
		if ( e.target.closest( '.related-card' ) ) {
			if ( typeof window.gtag === 'function' ) {
				const title = e.target.closest( '.related-card' )?.querySelector( '.related-title' )?.textContent;
				gtag( 'event', 'view_item', {
					items: [ { item_name: title } ]
				} );
			}
		}

		// Track tag clicks.
		if ( e.target.closest( '.product-tags .tag' ) ) {
			const tagName = e.target.textContent;
			if ( typeof window.gtag === 'function' ) {
				gtag( 'event', 'search', { search_term: tagName } );
			}
		}
	} );

	// Keyboard navigation support.
	document.addEventListener( 'keydown', function( e ) {
		// Escape key for future modal support.
		if ( e.key === 'Escape' ) {
			// Handle closing any open modals.
		}

		// Arrow keys for related product navigation (optional).
		if ( e.key === 'ArrowRight' || e.key === 'ArrowLeft' ) {
			const relatedCards = document.querySelectorAll( '.related-card' );
			if ( relatedCards.length > 0 ) {
				const focused = document.activeElement;
				const cards = Array.from( relatedCards );
				const currentIndex = cards.indexOf( focused );

				if ( currentIndex > -1 ) {
					let nextIndex;
					if ( e.key === 'ArrowRight' ) {
						nextIndex = ( currentIndex + 1 ) % cards.length;
					} else {
						nextIndex = ( currentIndex - 1 + cards.length ) % cards.length;
					}
					cards[ nextIndex ].focus();
					e.preventDefault();
				}
			}
		}
	} );

	// Enhanced feature list interactions.
	const featureItems = document.querySelectorAll( '.feature-item' );
	featureItems.forEach( item => {
		item.addEventListener( 'mouseenter', function() {
			this.style.transform = 'translateY(-2px)';
		} );

		item.addEventListener( 'mouseleave', function() {
			this.style.transform = 'translateY(0)';
		} );

		// Keyboard accessible.
		item.setAttribute( 'tabindex', '0' );
		item.addEventListener( 'focus', function() {
			this.style.outline = '2px solid rgba(124, 131, 255, .55)';
			this.style.outlineOffset = '2px';
		} );

		item.addEventListener( 'blur', function() {
			this.style.outline = 'none';
		} );
	} );

	// Debounced scroll event for performance.
	let scrollTimeout;
	const handleScroll = () => {
		clearTimeout( scrollTimeout );
		scrollTimeout = setTimeout( () => {
			// Track scroll depth.
			const scrollPercentage = ( window.scrollY / ( document.documentElement.scrollHeight - window.innerHeight ) ) * 100;
			
			// Log deep engagement.
			if ( scrollPercentage > 75 && typeof window.gtag === 'function' ) {
				gtag( 'event', 'scroll', { value: Math.round( scrollPercentage ) } );
			}
		}, 500 );
	};

	window.addEventListener( 'scroll', handleScroll, { passive: true } );

	// Page visibility API - pause interactions when tab is hidden.
	document.addEventListener( 'visibilitychange', function() {
		if ( document.hidden ) {
			// Pause animations, videos, etc.
		} else {
			// Resume.
		}
	} );

	// Intersection observer for fade-in animations on scroll.
	if ( 'IntersectionObserver' in window ) {
		const fadeInObserver = new IntersectionObserver( ( entries ) => {
			entries.forEach( entry => {
				if ( entry.isIntersecting ) {
					entry.target.style.opacity = '1';
					entry.target.style.transform = 'translateY(0)';
				}
			} );
		}, { threshold: 0.1 } );

		document.querySelectorAll( '.product-features, .product-related' ).forEach( el => {
			el.style.opacity = '0';
			el.style.transform = 'translateY(20px)';
			el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
			fadeInObserver.observe( el );
		} );
	}

	// Preload related product images on hover.
	const relatedCards = document.querySelectorAll( '.related-card' );
	relatedCards.forEach( card => {
		card.addEventListener( 'mouseenter', function() {
			const img = this.querySelector( 'img' );
			if ( img && img.dataset.src ) {
				const link = document.createElement( 'link' );
				link.rel = 'prefetch';
				link.href = img.dataset.src;
				document.head.appendChild( link );
			}
		}, { passive: true } );
	} );

} )();
