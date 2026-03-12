/**
 * Scroll-reveal and parallax effects.
 *
 * Uses IntersectionObserver to add .reveal--visible to elements with .reveal
 * when they enter the viewport. Supports staggered delays via [data-reveal-delay].
 *
 * Also handles:
 * - Header shrink/color change on scroll
 * - Header dark/light based on current section background
 *
 * @package Simple_Church
 */
( function () {
	'use strict';

	/* ------------------------------------------------------------------
	   SCROLL REVEAL
	   ------------------------------------------------------------------ */

	const revealElements = document.querySelectorAll( '.reveal' );

	if ( 'IntersectionObserver' in window ) {
		const observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						const el    = entry.target;
						const delay = parseInt( el.getAttribute( 'data-reveal-delay' ) || '0', 10 );

						setTimeout( function () {
							el.classList.add( 'reveal--visible' );
						}, delay );

						observer.unobserve( el );
					}
				} );
			},
			{
				threshold: 0.15,
				rootMargin: '0px 0px -60px 0px',
			}
		);

		revealElements.forEach( function ( el ) {
			observer.observe( el );
		} );
	} else {
		// Fallback: just show everything
		revealElements.forEach( function ( el ) {
			el.classList.add( 'reveal--visible' );
		} );
	}

	/* ------------------------------------------------------------------
	   HEADER SCROLL BEHAVIOUR
	   ------------------------------------------------------------------ */

	const header = document.getElementById( 'site-header' );
	if ( ! header ) return;

	let lastScrollY = 0;
	let ticking     = false;

	function onScroll() {
		lastScrollY = window.scrollY;
		if ( ! ticking ) {
			window.requestAnimationFrame( updateHeader );
			ticking = true;
		}
	}

	// Check if this is a dark variant header
	const isDarkVariant = header.getAttribute( 'data-variant' ) === 'dark';

	function updateHeader() {
		ticking = false;

		// Shrink header after scrolling past 80px
		if ( lastScrollY > 80 ) {
			header.classList.add( 'site-header--scrolled' );
		} else {
			header.classList.remove( 'site-header--scrolled' );
		}

		// Determine if header is over a dark section
		updateHeaderColor();
	}

	/**
	 * Check which section is behind the header and toggle dark/light text.
	 */
	function updateHeaderColor() {
		const headerHeight = header.offsetHeight;
		const checkPoint   = headerHeight / 2;

		// Get the element at the center of the header
		// Temporarily hide header to avoid hitting itself
		header.style.pointerEvents = 'none';
		const el = document.elementFromPoint( window.innerWidth / 2, checkPoint );
		header.style.pointerEvents = '';

		if ( ! el ) return;

		const section = el.closest( '.section--dark, .hero, .page-hero, .parallax-break, .section--light' );

		if ( section ) {
			const isDark = section.classList.contains( 'section--dark' )
				|| section.classList.contains( 'page-hero' )
				|| section.classList.contains( 'parallax-break' );

			// Hero fades from white at top so header stays dark
			if ( section.classList.contains( 'hero' ) ) {
				header.classList.remove( 'site-header--dark' );
			} else if ( isDark ) {
				header.classList.add( 'site-header--dark' );
			} else {
				header.classList.remove( 'site-header--dark' );
			}

			// For dark variant: toggle light-swap modifier over light sections
			if ( isDarkVariant ) {
				const isOverLight = section.classList.contains( 'section--light' )
					|| section.classList.contains( 'hero' );

				if ( isOverLight && lastScrollY > 80 ) {
					header.classList.add( 'site-header--variant-light-swap' );
				} else {
					header.classList.remove( 'site-header--variant-light-swap' );
				}
			}
		} else {
			header.classList.remove( 'site-header--dark' );

			// Over generic content (likely light bg), toggle light-swap
			if ( isDarkVariant && lastScrollY > 80 ) {
				header.classList.add( 'site-header--variant-light-swap' );
			} else if ( isDarkVariant ) {
				header.classList.remove( 'site-header--variant-light-swap' );
			}
		}
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );

	// Initial run
	updateHeader();

	/* ------------------------------------------------------------------
	   PARALLAX BREAK — subtle vertical shift on scroll
	   ------------------------------------------------------------------ */

	const parallaxBreak = document.querySelector( '.parallax-break__content' );
	if ( parallaxBreak ) {
		const breakSection = parallaxBreak.closest( '.parallax-break' );

		function updateParallax() {
			const rect   = breakSection.getBoundingClientRect();
			const vh     = window.innerHeight;
			const center = ( rect.top + rect.height / 2 - vh / 2 ) / vh;
			const shift  = center * -30; // subtle movement

			parallaxBreak.style.transform = 'translateY(' + shift + 'px)';
			requestAnimationFrame( updateParallax );
		}

		requestAnimationFrame( updateParallax );
	}
} )();
