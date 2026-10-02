/**
 * Blok "Jadlospis" - widok w edytorze.
 *
 * Zwykly JavaScript, bez JSX i bez kroku budowania: `wp.element.createElement`
 * zamiast znacznikow, zaleznosci wyliczone recznie w `edytor.asset.php`.
 *
 * Karty w edytorze maja te same klasy co front, wiec `style.css`
 * (dolaczony przez `add_editor_style`) rysuje je bez osobnej stylistyki.
 * Wyjatkiem jest pasek z kalendarzem i przelacznik dnia wolnego - istnieja
 * tylko w panelu, ich wyglad siedzi w `assets/css/editor.css`.
 *
 * Nazwy dni, kolory grup, posilki i miesiace w dopelniaczu przychodza z PHP
 * (`window.przedszkoleJadlospis`, patrz `inc/blok-jadlospis.php`) - stoja
 * w jednym miejscu. Drugi raz stoi tu tylko regula zakresu dat.
 *
 * Test bez przegladarki: `node tools/test_jadlospis_edytor.js`.
 */
( function ( wp, dane ) {
	'use strict';

	var el = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;

	var useBlockProps = wp.blockEditor.useBlockProps;
	var RichText = wp.blockEditor.RichText;

	var Button = wp.components.Button;
	var DatePicker = wp.components.DatePicker;
	var Dropdown = wp.components.Dropdown;
	var ToggleControl = wp.components.ToggleControl;

	// Ikona jako SVG, nie nazwa dashicona: edytor stoi w iframe, do ktorego
	// rdzen nie laduje arkusza dashicons - przycisk zostalby bez ikony.
	// Ksztalt z ikony kalendarza rdzenia.
	var IKONA_KALENDARZA = el(
		'svg',
		{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24', width: 24, height: 24, 'aria-hidden': 'true', focusable: 'false' },
		el( 'path', { d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z' } )
	);

	var PUSTY_DZIEN = { wolny: false, sniadanie: '', obiad: '', podwieczorek: '' };

	// Daty w czasie lokalnym przegladarki. Liczymy dni kalendarzowe,
	// nie chwile, wiec strefa czasowa nie ma tu znaczenia.

	function dwieCyfry( liczba ) {
		return ( liczba < 10 ? '0' : '' ) + liczba;
	}

	// Ta sama regula co w PHP (przedszkole_jadlospis_poczatek): inaczej
	// edytor pokazalby daty, ktorych front nie wyswietli.
	function zTekstu( tekst ) {
		var czesci = /^(\d{4})-(\d{2})-(\d{2})$/.exec( tekst || '' );

		if ( ! czesci ) {
			return null;
		}

		var data = new Date( +czesci[ 1 ], czesci[ 2 ] - 1, +czesci[ 3 ] );

		return naTekst( data ) === tekst && 1 === data.getDay() ? data : null;
	}

	function naTekst( data ) {
		return data.getFullYear() + '-' + dwieCyfry( data.getMonth() + 1 ) + '-' + dwieCyfry( data.getDate() );
	}

	function dodajDni( data, ile ) {
		return new Date( data.getFullYear(), data.getMonth(), data.getDate() + ile );
	}

	function dzienIMiesiac( data ) {
		return data.getDate() + ' ' + dane.miesiace[ data.getMonth() ];
	}

	/**
	 * Zakres tygodnia: "22 - 26 czerwca 2026"; miesiac i rok powtorzone
	 * tylko na granicy. Ta sama regula stoi w `przedszkole_jadlospis_zakres()`
	 * w `inc/blok-jadlospis.php` - zmieniajac jedno, popraw drugie.
	 */
	function zakres( poczatek ) {
		var koniec = dodajDni( poczatek, 4 );
		var od = String( poczatek.getDate() );

		if ( poczatek.getFullYear() !== koniec.getFullYear() ) {
			od = dzienIMiesiac( poczatek ) + ' ' + poczatek.getFullYear();
		} else if ( poczatek.getMonth() !== koniec.getMonth() ) {
			od = dzienIMiesiac( poczatek );
		}

		return od + ' – ' + dzienIMiesiac( koniec ) + ' ' + koniec.getFullYear();
	}

	/**
	 * Poniedzialek dla swiezo wstawionego bloku: biezacy tydzien, a w sobote
	 * i niedziele juz nastepny - jadlospis wpisuje sie z wyprzedzeniem.
	 */
	function domyslnyPoniedzialek() {
		var dzis = new Date();
		var dzien = dzis.getDay(); // 0 - niedziela, 6 - sobota.
		var przesuniecie = 1 - dzien;

		if ( 0 === dzien ) {
			przesuniecie = 1;
		} else if ( 6 === dzien ) {
			przesuniecie = 2;
		}

		return naTekst( dodajDni( dzis, przesuniecie ) );
	}

	/**
	 * Dzien z atrybutu uzupelniony do pelnego ksztaltu - przyklad w `block.json`
	 * ma jeden dzien, a recznie poprawiony komentarz bloku moze miec cokolwiek.
	 */
	function dzien( dni, indeks ) {
		return Object.assign( {}, PUSTY_DZIEN, ( dni && dni[ indeks ] ) || {} );
	}

	/**
	 * Zmiana jednego dnia. Atrybut `dni` to tablica, wiec zmiana pola
	 * to nowa tablica - zawsze pelne piec dni, nawet gdy w atrybucie bylo mniej.
	 */
	function zmienDzien( props, indeks, zmiana ) {
		var dni = dane.dni.map( function ( opis, i ) {
			var obecny = dzien( props.attributes.dni, i );

			return i === indeks ? Object.assign( obecny, zmiana ) : obecny;
		} );

		props.setAttributes( { dni: dni } );
	}

	function kalendarz( props ) {
		var poczatek = props.attributes.poczatek;

		return el( Dropdown, {
			className: 'jadlospis__kalendarz',
			popoverProps: { placement: 'bottom-start' },
			renderToggle: function ( przelacznik ) {
				return el(
					Button,
					{
						variant: 'secondary',
						icon: IKONA_KALENDARZA,
						onClick: przelacznik.onToggle,
						'aria-expanded': przelacznik.isOpen,
					},
					__( 'Zmień tydzień', 'przedszkole' )
				);
			},
			renderContent: function ( okno ) {
				return el( DatePicker, {
					currentDate: poczatek ? poczatek + 'T00:00:00' : undefined,
					startOfWeek: 1,
					// Wylacznie poniedzialki - reszte tygodnia blok liczy sam.
					isInvalidDate: function ( data ) {
						return 1 !== data.getDay();
					},
					onChange: function ( wartosc ) {
						props.setAttributes( { poczatek: wartosc.slice( 0, 10 ) } );
						okno.onClose();
					},
				} );
			},
		} );
	}

	function posilek( props, indeks, opis, tresc ) {
		return el(
			'div',
			{ key: opis.klucz, className: 'jadlospis__posilek jadlospis__posilek--' + opis.klucz },
			el( 'h4', { className: 'jadlospis__etykieta' }, opis.etykieta ),
			el( RichText, {
				tagName: 'p',
				// Kilka pol w jednym bloku - identyfikator pozwala edytorowi
				// odtworzyc kursor we wlasciwym polu po zapisie.
				identifier: 'dzien-' + indeks + '-' + opis.klucz,
				value: tresc,
				placeholder: __( 'Wpisz menu — alergeny pogrub (Ctrl+B)', 'przedszkole' ),
				onChange: function ( wartosc ) {
					var zmiana = {};

					zmiana[ opis.klucz ] = wartosc;
					zmienDzien( props, indeks, zmiana );
				},
			} )
		);
	}

	function karta( props, poczatek, opis, indeks ) {
		var biezacy = dzien( props.attributes.dni, indeks );
		var data = poczatek ? dodajDni( poczatek, indeks ) : null;

		return el(
			'li',
			{ key: opis.grupa, className: 'jadlospis__dzien jadlospis__dzien--' + opis.grupa },
			el(
				'header',
				{ className: 'jadlospis__naglowek' },
				el( 'h3', { className: 'jadlospis__nazwa' }, opis.nazwa ),
				data ? el( 'p', { className: 'jadlospis__data' }, dzienIMiesiac( data ) ) : null
			),
			el(
				'div',
				{ className: 'jadlospis__tresc' },
				el( ToggleControl, {
					className: 'jadlospis__przelacznik',
					label: __( 'Dzień wolny', 'przedszkole' ),
					checked: biezacy.wolny,
					onChange: function ( wartosc ) {
						zmienDzien( props, indeks, { wolny: wartosc } );
					},
					__nextHasNoMarginBottom: true,
				} ),
				biezacy.wolny
					? el( 'p', { className: 'jadlospis__wolne' }, __( 'Dzień wolny', 'przedszkole' ) )
					: dane.posilki.map( function ( opisPosilku ) {
						return posilek( props, indeks, opisPosilku, biezacy[ opisPosilku.klucz ] );
					} )
			)
		);
	}

	wp.blocks.registerBlockType( 'przedszkole/jadlospis', {
		edit: function ( props ) {
			var a = props.attributes;
			var poczatek = zTekstu( a.poczatek );

			// `alignfull` z tego samego powodu co w `render.php`: bez tej klasy
			// kontener ukladu rdzenia scisnalby blok do kolumny tresci.
			var blockProps = useBlockProps( { className: 'jadlospis alignfull' } );

			// Hook zawsze wywolany, przed useEffect - stala kolejnosc hookow.
			var oznaczNietrwala = wp.data.useDispatch( wp.blockEditor.store ).__unstableMarkNextChangeAsNotPersistent;

			// Swiezo wstawiony blok od razu dostaje poniedzialek - stanu
			// "bez daty" intendent w praktyce nie zobaczy.
			useEffect( function () {
				if ( ! a.poczatek ) {
					// Wstawienie bloku i domyslna data to jeden krok cofania:
					// bez tego Ctrl+Z po wstawieniu czyscilby sama date,
					// a efekt (puste zaleznosci) juz by jej nie przywrocil.
					oznaczNietrwala();
					props.setAttributes( { poczatek: domyslnyPoniedzialek() } );
				}
			}, [] );

			return el(
				'section',
				blockProps,
				el(
					'div',
					{ className: 'jadlospis__tydzien' },
					kalendarz( props ),
					poczatek ? el( 'h2', { className: 'jadlospis__zakres' }, zakres( poczatek ) ) : null
				),
				el(
					'ol',
					{ className: 'jadlospis__dni' },
					dane.dni.map( function ( opis, indeks ) {
						return karta( props, poczatek, opis, indeks );
					} )
				)
			);
		},

		// Blok jest dynamiczny - front rysuje `render.php`, w tresci strony
		// zostaje sam komentarz z atrybutami.
		save: function () {
			return null;
		},
	} );
} )( window.wp, window.przedszkoleJadlospis );
