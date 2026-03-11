/**
 * Navigation — overlay menu toggle.
 *
 * Handles opening/closing the full-screen overlay menu,
 * including the hamburger-to-X animation and body scroll lock.
 *
 * @package Simple_Church
 */
( function () {
	'use strict';

	const toggle   = document.getElementById( 'menu-toggle' );
	const overlay  = document.getElementById( 'overlay-menu' );
	const closeBtn = document.getElementById( 'overlay-close' );

	if ( ! toggle || ! overlay ) return;

	let isOpen = false;

	function openMenu() {
		isOpen = true;
		toggle.classList.add( 'menu-toggle--open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		toggle.setAttribute( 'aria-label', 'Close menu' );
		overlay.classList.add( 'overlay-menu--open' );
		overlay.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';

		// Force white color on toggle when menu is open (dark overlay)
		toggle.style.color = '#ffffff';
	}

	function closeMenu() {
		isOpen = false;
		toggle.classList.remove( 'menu-toggle--open' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		toggle.setAttribute( 'aria-label', 'Open menu' );
		overlay.classList.remove( 'overlay-menu--open' );
		overlay.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';

		// Reset toggle color
		toggle.style.color = '';
	}

	toggle.addEventListener( 'click', function () {
		if ( isOpen ) {
			closeMenu();
		} else {
			openMenu();
		}
	} );

	// Close via the dedicated close button inside the overlay
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			closeMenu();
		} );
	}

	// Close when clicking the overlay background (not the menu links)
	overlay.addEventListener( 'click', function ( e ) {
		if ( e.target === overlay || e.target.classList.contains( 'overlay-menu__inner' ) ) {
			closeMenu();
		}
	} );

	// Close on Escape key
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && isOpen ) {
			closeMenu();
		}
	} );

	// Close when clicking a menu link
	const menuLinks = overlay.querySelectorAll( '.overlay-menu__link' );
	menuLinks.forEach( function ( link ) {
		link.addEventListener( 'click', function () {
			closeMenu();
		} );
	} );
} )();
