/**
 * Typing animation for the hero headline.
 *
 * Reads words from the [data-words] JSON attribute on #typed-output,
 * types them out character by character, pauses, deletes, then moves
 * to the next word in an infinite loop.
 *
 * Speed and pause duration are read from data attributes so they can
 * be configured in the WordPress Customizer.
 *
 * @package Simple_Church
 */
( function () {
	'use strict';

	var container = document.getElementById( 'typed-output' );
	if ( ! container ) return;

	var textEl = container.querySelector( '.hero__typed-text' );
	if ( ! textEl ) return;

	var words;
	try {
		words = JSON.parse( container.getAttribute( 'data-words' ) );
	} catch ( e ) {
		return;
	}
	if ( ! Array.isArray( words ) || words.length === 0 ) return;

	// Configurable via WP Customizer → data attributes
	var TYPING_SPEED   = parseInt( container.getAttribute( 'data-typing-speed' ), 10 ) || 80;
	var DELETING_SPEED = Math.max( 30, Math.round( TYPING_SPEED * 0.6 ) );
	var PAUSE_AFTER    = parseInt( container.getAttribute( 'data-typing-pause' ), 10 ) || 2000;
	var PAUSE_BEFORE   = Math.max( 200, Math.round( PAUSE_AFTER * 0.2 ) );

	var wordIndex  = 0;
	var charIndex  = 0;
	var isDeleting = false;

	function tick() {
		var currentWord = words[ wordIndex ];

		if ( isDeleting ) {
			charIndex--;
			textEl.textContent = currentWord.substring( 0, charIndex );

			if ( charIndex === 0 ) {
				isDeleting = false;
				wordIndex = ( wordIndex + 1 ) % words.length;
				setTimeout( tick, PAUSE_BEFORE );
				return;
			}

			setTimeout( tick, DELETING_SPEED );
		} else {
			charIndex++;
			textEl.textContent = currentWord.substring( 0, charIndex );

			if ( charIndex === currentWord.length ) {
				isDeleting = true;
				setTimeout( tick, PAUSE_AFTER );
				return;
			}

			setTimeout( tick, TYPING_SPEED );
		}
	}

	// Start after a short initial delay
	setTimeout( tick, 600 );
} )();
