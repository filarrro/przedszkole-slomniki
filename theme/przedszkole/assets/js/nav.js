/**
 * Menu mobilne. Czysty JavaScript, bez zaleznosci.
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '.nav-toggle' );
	var nav    = document.getElementById( 'main-nav' );

	if ( ! toggle || ! nav ) {
		return;
	}

	function setOpen( open ) {
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		nav.classList.toggle( 'is-open', open );
	}

	toggle.addEventListener( 'click', function () {
		setOpen( toggle.getAttribute( 'aria-expanded' ) !== 'true' );
	} );

	// Escape zamyka menu i wraca fokusem na przycisk.
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && toggle.getAttribute( 'aria-expanded' ) === 'true' ) {
			setOpen( false );
			toggle.focus();
		}
	} );

	// Po przejsciu na desktop panel mobilny nie moze zostac "otwarty".
	var desktop = window.matchMedia( '(min-width: 960px)' );
	desktop.addEventListener( 'change', function ( event ) {
		if ( event.matches ) {
			setOpen( false );
		}
	} );
}() );
