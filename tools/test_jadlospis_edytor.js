/**
 * Test widoku edytora bloku "Jadlospis" - bez przegladarki i bez logowania.
 *
 * Projekt nie ma kroku budowania ani npm; ten plik tez nie ma zaleznosci.
 * Podstawia minimalne `window.wp` (createElement zwraca zwykly obiekt),
 * laduje `edytor.js`, renderuje `edit` i sprawdza drzewo oraz zapisy
 * atrybutow. Lapie literowki i bledy logiki; wygladu nie sprawdza.
 *
 *   node tools/test_jadlospis_edytor.js
 *
 * Zakresy dat te same co w `tools/test_jadlospis.php` - regula stoi
 * w PHP i w JS, oba testy pilnuja, zeby mowily to samo.
 * Dane `przedszkoleJadlospis` przepisane z `inc/blok-jadlospis.php`.
 */
'use strict';

var path = require( 'path' );

var zarejestrowany = null;
var efekty = [];
var bledy = 0;

function el( typ, wlasciwosci ) {
	var dzieci = Array.prototype.slice.call( arguments, 2 ).flat( Infinity ).filter( function ( d ) {
		return null !== d && undefined !== d && false !== d;
	} );

	return { typ: typ, props: wlasciwosci || {}, dzieci: dzieci };
}

global.window = {
	przedszkoleJadlospis: {
		dni: [
			{ nazwa: 'Poniedziałek', grupa: 'zabki' },
			{ nazwa: 'Wtorek', grupa: 'zajaczki' },
			{ nazwa: 'Środa', grupa: 'kotki' },
			{ nazwa: 'Czwartek', grupa: 'misie' },
			{ nazwa: 'Piątek', grupa: 'wiewiorki' },
		],
		posilki: [
			{ klucz: 'sniadanie', etykieta: 'Śniadanie' },
			{ klucz: 'obiad', etykieta: 'Obiad' },
			{ klucz: 'podwieczorek', etykieta: 'Podwieczorek' },
		],
		miesiace: [ 'stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca', 'lipca', 'sierpnia', 'września', 'października', 'listopada', 'grudnia' ],
	},
	wp: {
		element: {
			createElement: el,
			useEffect: function ( fn ) {
				efekty.push( fn );
			},
		},
		i18n: {
			__: function ( tekst ) {
				return tekst;
			},
		},
		blockEditor: {
			useBlockProps: function ( p ) {
				return p;
			},
			RichText: 'RichText',
		},
		components: {
			Button: 'Button',
			DatePicker: 'DatePicker',
			Dropdown: 'Dropdown',
			ToggleControl: 'ToggleControl',
		},
		blocks: {
			registerBlockType: function ( nazwa, definicja ) {
				zarejestrowany = { nazwa: nazwa, definicja: definicja };
			},
		},
	},
};

require( path.join( __dirname, '../theme/przedszkole/blocks/jadlospis/edytor.js' ) );

// --- Narzedzia ---

function sprawdz( opis, warunek ) {
	if ( warunek ) {
		console.log( 'OK    ' + opis );
		return;
	}
	bledy++;
	console.log( 'BLAD  ' + opis );
}

function wszystkie( wezel, warunek, wynik ) {
	wynik = wynik || [];
	if ( ! wezel || 'object' !== typeof wezel ) {
		return wynik;
	}
	if ( warunek( wezel ) ) {
		wynik.push( wezel );
	}
	wezel.dzieci.forEach( function ( dziecko ) {
		wszystkie( dziecko, warunek, wynik );
	} );
	return wynik;
}

function tekst( wezel ) {
	return wezel.dzieci.map( function ( d ) {
		return 'object' === typeof d ? tekst( d ) : String( d );
	} ).join( '' );
}

function zKlasa( klasa ) {
	return function ( w ) {
		return ( ' ' + ( w.props.className || '' ) + ' ' ).indexOf( ' ' + klasa + ' ' ) > -1;
	};
}

function typu( nazwa ) {
	return function ( w ) {
		return nazwa === w.typ;
	};
}

function pusteDni() {
	return [ 0, 1, 2, 3, 4 ].map( function () {
		return { wolny: false, sniadanie: '', obiad: '', podwieczorek: '' };
	} );
}

function renderuj( atrybuty ) {
	var zapisy = [];
	efekty = [];
	var drzewo = zarejestrowany.definicja.edit( {
		attributes: atrybuty,
		setAttributes: function ( zmiana ) {
			zapisy.push( zmiana );
		},
	} );
	return { drzewo: drzewo, zapisy: zapisy, ostatni: function () {
		return zapisy[ zapisy.length - 1 ];
	} };
}

// --- Rejestracja ---

sprawdz( 'blok zarejestrowany pod wlasciwa nazwa', null !== zarejestrowany && 'przedszkole/jadlospis' === zarejestrowany.nazwa );
sprawdz( 'save zwraca null - blok dynamiczny', null === zarejestrowany.definicja.save() );

// --- Uklad ---

var w = renderuj( { poczatek: '2026-06-22', dni: pusteDni() } );
var naglowki = wszystkie( w.drzewo, zKlasa( 'jadlospis__zakres' ) );

sprawdz( 'klasy bloku', zKlasa( 'jadlospis' )( w.drzewo ) && zKlasa( 'alignfull' )( w.drzewo ) );
sprawdz( 'zakres w jednym miesiacu', 1 === naglowki.length && '22 – 26 czerwca 2026' === tekst( naglowki[ 0 ] ) );
sprawdz( 'piec kart', 5 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien' ) ).length );
sprawdz( 'poniedzialek w kolorze Zabek', 1 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien--zabki' ) ).length );
sprawdz( 'data w dopelniaczu', '22 czerwca' === tekst( wszystkie( w.drzewo, zKlasa( 'jadlospis__data' ) )[ 0 ] ) );
sprawdz( 'pietnascie pol posilkow', 15 === wszystkie( w.drzewo, typu( 'RichText' ) ).length );
sprawdz( 'piec przelacznikow dnia wolnego', 5 === wszystkie( w.drzewo, typu( 'ToggleControl' ) ).length );

efekty.forEach( function ( f ) {
	f();
} );
sprawdz( 'blok z data nic nie zapisuje przy montowaniu', 0 === w.zapisy.length );

[
	[ '2025-09-29', '29 września – 3 października 2025' ],
	[ '2025-12-29', '29 grudnia 2025 – 2 stycznia 2026' ],
].forEach( function ( przypadek ) {
	var naglowek = wszystkie( renderuj( { poczatek: przypadek[ 0 ], dni: pusteDni() } ).drzewo, zKlasa( 'jadlospis__zakres' ) )[ 0 ];
	sprawdz( 'zakres od ' + przypadek[ 0 ], undefined !== naglowek && przypadek[ 1 ] === tekst( naglowek ) );
} );

w = renderuj( { poczatek: '2026-06-22', dni: [ { sniadanie: 'x' } ] } );
sprawdz( 'niepelny atrybut dni daje piec kart', 5 === wszystkie( w.drzewo, zKlasa( 'jadlospis__dzien' ) ).length );

// --- Dzien wolny ---

var dni = pusteDni();
dni[ 1 ].wolny = true;
dni[ 1 ].obiad = 'zostaje w atrybucie';
w = renderuj( { poczatek: '2026-06-22', dni: dni } );

sprawdz( 'dzien wolny chowa pola', 12 === wszystkie( w.drzewo, typu( 'RichText' ) ).length );
sprawdz( 'dzien wolny pokazuje napis', 1 === wszystkie( w.drzewo, zKlasa( 'jadlospis__wolne' ) ).length );

wszystkie( w.drzewo, typu( 'ToggleControl' ) )[ 1 ].props.onChange( false );
sprawdz( 'odznaczenie zapisuje piec dni', 5 === w.ostatni().dni.length );
sprawdz( 'odznaczenie przywraca dane dnia', false === w.ostatni().dni[ 1 ].wolny && 'zostaje w atrybucie' === w.ostatni().dni[ 1 ].obiad );

// Pierwsze pola: poniedzialek - sniadanie, obiad, podwieczorek.
wszystkie( w.drzewo, typu( 'RichText' ) )[ 1 ].props.onChange( '<strong>mleko</strong>' );
sprawdz( 'pole posilku zapisuje sie w swoim dniu', '<strong>mleko</strong>' === w.ostatni().dni[ 0 ].obiad );
sprawdz( 'zapis pola nie rusza innych dni', true === w.ostatni().dni[ 1 ].wolny );

// --- Data ---

w = renderuj( { poczatek: '', dni: pusteDni() } );
sprawdz( 'bez daty bez naglowka', 0 === wszystkie( w.drzewo, zKlasa( 'jadlospis__zakres' ) ).length );
efekty.forEach( function ( f ) {
	f();
} );

var domyslny = w.zapisy[ 0 ] && w.zapisy[ 0 ].poczatek;
var data = domyslny ? new Date( domyslny + 'T00:00:00' ) : null;
var roznica = data ? ( data - new Date() ) / 864e5 : NaN;

sprawdz( 'swiezy blok dostaje poniedzialek', null !== data && 1 === data.getDay() );
sprawdz( 'poniedzialek z biezacego albo nastepnego tygodnia', roznica > -7 && roznica < 3 );

w = renderuj( { poczatek: '2026-06-22', dni: pusteDni() } );

var rozwijane = wszystkie( w.drzewo, typu( 'Dropdown' ) )[ 0 ];
var zamkniete = false;
var przycisk = rozwijane.props.renderToggle( { onToggle: function () {}, isOpen: false } );
var kalendarz = rozwijane.props.renderContent( {
	onClose: function () {
		zamkniete = true;
	},
} );

sprawdz( 'przycisk "Zmień tydzień"', 'Button' === przycisk.typ && 'Zmień tydzień' === tekst( przycisk ) );
sprawdz( 'kalendarz pokazuje zapisany tydzien', '2026-06-22T00:00:00' === kalendarz.props.currentDate );
sprawdz( 'tydzien od poniedzialku', 1 === kalendarz.props.startOfWeek );
sprawdz( 'poniedzialek klikalny', false === kalendarz.props.isInvalidDate( new Date( 2026, 5, 22 ) ) );
sprawdz( 'wtorek nieklikalny', true === kalendarz.props.isInvalidDate( new Date( 2026, 5, 23 ) ) );
sprawdz( 'niedziela nieklikalna', true === kalendarz.props.isInvalidDate( new Date( 2026, 5, 28 ) ) );

kalendarz.props.onChange( '2026-06-29T00:00:00' );
sprawdz( 'wybor daty zapisuje sam dzien', '2026-06-29' === w.ostatni().poczatek );
sprawdz( 'wybor daty zamyka kalendarz', zamkniete );

if ( bledy ) {
	console.log( bledy + ' sprawdzen nie przeszlo.' );
	process.exit( 1 );
}

console.log( 'Wszystko przeszlo.' );
