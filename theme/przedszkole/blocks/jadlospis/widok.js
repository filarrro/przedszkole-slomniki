/**
 * Blok "Jadlospis" - wyroznienie dzisiejszego dnia.
 *
 * Data liczona w przegladarce, nie w PHP: strona moze wyjsc z cache,
 * a "dzis" serwera z chwili zapisu cache nie jest "dzis" rodzica.
 * Plakietka "Dzis" stoi juz w znacznikach z atrybutem `hidden` -
 * skrypt tylko ja odslania, wiec nie potrzebuje `wp-i18n`.
 *
 * Bez zaleznosci, ladowany przez `viewScript` z `defer` - tylko na stronie,
 * na ktorej stoi blok.
 */
( function () {
	'use strict';

	function dzis() {
		var teraz = new Date();
		var miesiac = teraz.getMonth() + 1;
		var dzien = teraz.getDate();

		return teraz.getFullYear() + '-' + ( miesiac < 10 ? '0' : '' ) + miesiac + '-' + ( dzien < 10 ? '0' : '' ) + dzien;
	}

	/**
	 * Przewiniecie do dzisiejszej karty - tylko gdy rodzic inaczej musialby
	 * jej szukac, i nigdy wbrew niemu:
	 * - jedna kolumna (odczytana z policzonej siatki, nie z okna - siatka
	 *   liczy sie od szerokosci bloku),
	 * - zwykle wejscie na strone, nie "wstecz" ani odswiezenie,
	 * - bez kotwicy w adresie i bez przewiniecia, ktore juz sie stalo,
	 * - karta zaczyna sie ponizej pierwszego ekranu (w poniedzialek nic sie
	 *   nie dzieje, tytul zostaje widoczny).
	 *
	 * Skok natychmiastowy: `html` ma `scroll-behavior: smooth`, a `auto`
	 * poszloby za nim. Miejsce pod przypietym naglowkiem zostawia
	 * `scroll-padding-top` z sekcji 2 `style.css`.
	 */
	function przewin( karta ) {
		var nawigacja = window.performance && performance.getEntriesByType
			? performance.getEntriesByType( 'navigation' )[ 0 ]
			: null;

		if ( getComputedStyle( karta.parentNode ).gridTemplateColumns.split( ' ' ).length > 1 ) {
			return;
		}
		if ( ! nawigacja || 'navigate' !== nawigacja.type ) {
			return;
		}
		if ( window.location.hash || window.scrollY > 0 ) {
			return;
		}
		if ( karta.getBoundingClientRect().top <= window.innerHeight ) {
			return;
		}

		karta.scrollIntoView( { block: 'start', behavior: 'instant' } );
	}

	var karta = document.querySelector( '.jadlospis__dzien[data-data="' + dzis() + '"]' );

	if ( ! karta ) {
		return;
	}

	karta.classList.add( 'jadlospis__dzien--dzis' );
	karta.setAttribute( 'aria-current', 'date' );

	var plakietka = karta.querySelector( '.jadlospis__dzis' );

	if ( plakietka ) {
		plakietka.hidden = false;
	}

	przewin( karta );
} )();
