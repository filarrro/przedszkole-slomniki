/**
 * Pasek dostepnosci: rozmiar tekstu.
 * Czysty JavaScript, bez zaleznosci.
 *
 * Stan siedzi w atrybucie `data-rozmiar` na <html> i w `localStorage`.
 * Atrybut ustawia juz krotki skrypt w naglowku (`functions.php`), zeby strona
 * nie mrugnela domyslnym rozmiarem przed odczytem preferencji. Tutaj zostaje
 * obsluga klikniec i prostowanie `aria-pressed`, ktore serwer wysyla zawsze
 * na „normalny" - strona jest cache'owalna i nie zna preferencji przegladarki.
 */
( function () {
	'use strict';

	var KLUCZ     = 'przedszkole-rozmiar';
	var DOZWOLONE = [ 'normalny', 'duzy', 'bardzo-duzy' ];

	var korzen    = document.documentElement;
	var przyciski = document.querySelectorAll( '[data-przedszkole-rozmiar]' );

	if ( ! przyciski.length ) {
		return;
	}

	function biezacy() {
		var rozmiar = korzen.getAttribute( 'data-rozmiar' );
		return -1 === DOZWOLONE.indexOf( rozmiar ) ? 'normalny' : rozmiar;
	}

	function odswiezPrzyciski() {
		var teraz = biezacy();

		Array.prototype.forEach.call( przyciski, function ( przycisk ) {
			var jego = przycisk.getAttribute( 'data-przedszkole-rozmiar' );
			przycisk.setAttribute( 'aria-pressed', jego === teraz ? 'true' : 'false' );
		} );
	}

	function ustaw( rozmiar ) {
		if ( -1 === DOZWOLONE.indexOf( rozmiar ) ) {
			return;
		}

		// Stan domyslny to brak atrybutu, a nie atrybut o wartosci „normalny" -
		// dzieki temu selektor `html:not([data-rozmiar])` w CSS jest prawdziwy
		// zarowno przed pierwszym kliknieciem, jak i po powrocie do standardu.
		if ( 'normalny' === rozmiar ) {
			korzen.removeAttribute( 'data-rozmiar' );
		} else {
			korzen.setAttribute( 'data-rozmiar', rozmiar );
		}

		// Tryb prywatny i zablokowane dane witryny potrafia rzucic wyjatkiem.
		// Przelacznik ma wtedy dzialac do konca wizyty, a nie przestac w ogole.
		try {
			localStorage.setItem( KLUCZ, rozmiar );
		} catch ( e ) {}

		odswiezPrzyciski();
	}

	Array.prototype.forEach.call( przyciski, function ( przycisk ) {
		przycisk.addEventListener( 'click', function () {
			ustaw( przycisk.getAttribute( 'data-przedszkole-rozmiar' ) );
		} );
	} );

	odswiezPrzyciski();
}() );
