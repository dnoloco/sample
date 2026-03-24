/**
 * Interactive pattern components — stat counters, tabs, and accordion.
 *
 * @package Simple_Church
 */
( function () {
	'use strict';

	/* ------------------------------------------------------------------
	   STAT COUNTERS — Animate numbers when scrolled into view
	   ------------------------------------------------------------------ */

	var statNumbers = document.querySelectorAll( '[data-count-to]' );

	if ( statNumbers.length && 'IntersectionObserver' in window ) {
		var counted = new WeakSet();

		var statObserver = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting && ! counted.has( entry.target ) ) {
						counted.add( entry.target );
						animateCounter( entry.target );
						statObserver.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.3 }
		);

		statNumbers.forEach( function ( el ) {
			statObserver.observe( el );
		} );
	}

	function animateCounter( el ) {
		var target   = parseInt( el.getAttribute( 'data-count-to' ), 10 );
		if ( isNaN( target ) || target <= 0 ) return;

		var duration = 2000;
		var start    = 0;
		var startTime = null;

		function step( timestamp ) {
			if ( ! startTime ) startTime = timestamp;
			var progress = Math.min( ( timestamp - startTime ) / duration, 1 );
			// ease-out cubic
			var eased = 1 - Math.pow( 1 - progress, 3 );
			var current = Math.round( eased * target );

			el.textContent = current.toLocaleString();

			if ( progress < 1 ) {
				requestAnimationFrame( step );
			}
		}

		requestAnimationFrame( step );
	}

	/* ------------------------------------------------------------------
	   TABBED CONTENT
	   ------------------------------------------------------------------ */

	var tabContainers = document.querySelectorAll( '[data-tabs]' );

	tabContainers.forEach( function ( container ) {
		var tabs   = container.querySelectorAll( '[role="tab"]' );
		var panels = container.querySelectorAll( '[role="tabpanel"]' );

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				// Deactivate all
				tabs.forEach( function ( t ) {
					t.classList.remove( 'sc-tabs__tab--active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				panels.forEach( function ( p ) {
					p.classList.remove( 'sc-tabs__panel--active' );
					p.hidden = true;
				} );

				// Activate clicked
				tab.classList.add( 'sc-tabs__tab--active' );
				tab.setAttribute( 'aria-selected', 'true' );

				var panelId = tab.getAttribute( 'aria-controls' );
				var panel   = document.getElementById( panelId );
				if ( panel ) {
					panel.classList.add( 'sc-tabs__panel--active' );
					panel.hidden = false;
				}
			} );
		} );

		// Keyboard navigation
		container.addEventListener( 'keydown', function ( e ) {
			var activeTabs = Array.prototype.slice.call( tabs );
			var index      = activeTabs.indexOf( document.activeElement );
			if ( index === -1 ) return;

			var newIndex;
			if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				newIndex = ( index + 1 ) % activeTabs.length;
			} else if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				newIndex = ( index - 1 + activeTabs.length ) % activeTabs.length;
			}

			if ( newIndex !== undefined ) {
				activeTabs[ newIndex ].focus();
				activeTabs[ newIndex ].click();
			}
		} );
	} );

	/* ------------------------------------------------------------------
	   FAQ ACCORDION
	   ------------------------------------------------------------------ */

	var accordions = document.querySelectorAll( '[data-accordion]' );

	accordions.forEach( function ( accordion ) {
		var triggers = accordion.querySelectorAll( '.sc-accordion__trigger' );

		triggers.forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function () {
				var item      = trigger.closest( '.sc-accordion__item' );
				var panel     = item.querySelector( '.sc-accordion__panel' );
				var isOpen    = item.classList.contains( 'sc-accordion__item--open' );

				if ( isOpen ) {
					// Close this item
					item.classList.remove( 'sc-accordion__item--open' );
					trigger.setAttribute( 'aria-expanded', 'false' );
					panel.hidden = true;
				} else {
					// Close all other items in this accordion
					accordion.querySelectorAll( '.sc-accordion__item--open' ).forEach( function ( openItem ) {
						openItem.classList.remove( 'sc-accordion__item--open' );
						openItem.querySelector( '.sc-accordion__trigger' ).setAttribute( 'aria-expanded', 'false' );
						openItem.querySelector( '.sc-accordion__panel' ).hidden = true;
					} );

					// Open this item
					item.classList.add( 'sc-accordion__item--open' );
					trigger.setAttribute( 'aria-expanded', 'true' );
					panel.hidden = false;
				}
			} );
		} );
	} );
} )();
