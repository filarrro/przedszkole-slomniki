<?php
/**
 * Blok „Jadłospis” — tydzień posiłków jako pięć kart.
 *
 * Drugi własny blok motywu, po `przedszkole/osoba`, z tego samego powodu:
 * układ powtarzany co tydzień psuł się w rękach pracownika. Intendent
 * przepisywał tydzień najpierw do zakładek, potem do tabeli — za każdym
 * razem razem z układem. Blok trzyma w treści strony same dane (data
 * poniedziałku i pięć dni), a wygląd rysuje `blocks/jadlospis/render.php`.
 *
 * Tutaj leży to, co wspólne dla frontu i edytora: nazwy dni z kolorami
 * grup, nazwy posiłków i daty. Edytor dostaje je z PHP gotowe, więc nie
 * stoją drugi raz w JavaScripcie.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dni tygodnia z kolorami grup.
 *
 * Kolor dnia to para `--{grupa}-tlo` / `--{grupa}-tekst` ze `style.css` —
 * kontrast policzony tam raz dla wszystkich komponentów. Jeżyki odpadają:
 * dni jest pięć, grup sześć, a czerwień czyta się jak ostrzeżenie.
 *
 * Kolejność jest znacząca: indeks to przesunięcie od poniedziałku.
 *
 * @return array<int, array{nazwa: string, grupa: string}>
 */
function przedszkole_jadlospis_dni() {
	return array(
		array(
			'nazwa' => __( 'Poniedziałek', 'przedszkole' ),
			'grupa' => 'zabki',
		),
		array(
			'nazwa' => __( 'Wtorek', 'przedszkole' ),
			'grupa' => 'zajaczki',
		),
		array(
			'nazwa' => __( 'Środa', 'przedszkole' ),
			'grupa' => 'kotki',
		),
		array(
			'nazwa' => __( 'Czwartek', 'przedszkole' ),
			'grupa' => 'misie',
		),
		array(
			'nazwa' => __( 'Piątek', 'przedszkole' ),
			'grupa' => 'wiewiorki',
		),
	);
}

/**
 * Posiłki w kolejności na karcie.
 *
 * Klucz to nazwa pola w atrybucie `dni` i część klasy CSS
 * (`jadlospis__posilek--{klucz}`) — zmiana klucza gubi zapisane dane.
 *
 * @return array<string, string> Klucz => etykieta.
 */
function przedszkole_jadlospis_posilki() {
	return array(
		'sniadanie'    => __( 'Śniadanie', 'przedszkole' ),
		'obiad'        => __( 'Obiad', 'przedszkole' ),
		'podwieczorek' => __( 'Podwieczorek', 'przedszkole' ),
	);
}

/**
 * Data poniedziałku z atrybutu bloku.
 *
 * `createFromFormat()` przepuszcza „2026-02-31” jako 3 marca, więc wynik
 * porównujemy z wejściem — przepełniona data odpada razem ze śmieciami.
 * Strefa czasowa strony, nie serwera: inaczej `wp_date()` przesunęłoby
 * północ na poprzedni dzień.
 *
 * @param mixed $tekst Data `RRRR-MM-DD`.
 * @return DateTimeImmutable|null
 */
function przedszkole_jadlospis_poczatek( $tekst ) {
	if ( ! is_string( $tekst ) ) {
		return null;
	}

	$data = DateTimeImmutable::createFromFormat( '!Y-m-d', $tekst, wp_timezone() );

	if ( ! $data || $data->format( 'Y-m-d' ) !== $tekst ) {
		return null;
	}

	return $data;
}

/**
 * Zakres tygodnia do nagłówka: „22 – 26 czerwca 2026”.
 *
 * Miesiąc i rok stoją raz, chyba że tydzień przechodzi przez granicę:
 * „29 września – 3 października 2025”, „29 grudnia 2025 – 2 stycznia 2026”.
 * Odmianę miesiąca robi rdzeń — `wp_date()` z formatem „j F” zwraca
 * w polskiej lokalizacji dopełniacz (`wp_maybe_decline_date()`).
 *
 * Ta sama reguła stoi drugi raz w `blocks/jadlospis/edytor.js` — edytor
 * pokazuje zakres od razu po wyborze daty. Zmieniając jedno, popraw drugie;
 * oba testy (`tools/test_jadlospis*`) sprawdzają te same przypadki.
 *
 * @param DateTimeImmutable $poczatek Poniedziałek.
 * @return string
 */
function przedszkole_jadlospis_zakres( DateTimeImmutable $poczatek ) {
	$koniec = $poczatek->modify( '+4 days' );

	if ( $poczatek->format( 'Y' ) !== $koniec->format( 'Y' ) ) {
		$format_od = 'j F Y';
	} elseif ( $poczatek->format( 'n' ) !== $koniec->format( 'n' ) ) {
		$format_od = 'j F';
	} else {
		$format_od = 'j';
	}

	return wp_date( $format_od, $poczatek->getTimestamp() ) . ' – ' . wp_date( 'j F Y', $koniec->getTimestamp() );
}

/**
 * Jeden dzień z atrybutu `dni`, uzupełniony do pełnego kształtu.
 *
 * Atrybut może przyjść niepełny: przykład w `block.json` ma jeden dzień,
 * a ręcznie poprawiony komentarz bloku — cokolwiek.
 *
 * @param mixed $surowy Element tablicy `dni`.
 * @return array{wolny: bool, sniadanie: string, obiad: string, podwieczorek: string}
 */
function przedszkole_jadlospis_dzien( $surowy ) {
	$surowy = is_array( $surowy ) ? $surowy : array();
	$dzien  = array( 'wolny' => ! empty( $surowy['wolny'] ) );

	foreach ( array_keys( przedszkole_jadlospis_posilki() ) as $klucz ) {
		$dzien[ $klucz ] = is_string( $surowy[ $klucz ] ?? null ) ? $surowy[ $klucz ] : '';
	}

	return $dzien;
}

/**
 * Posiłki, które coś zawierają — puste sekcje karta pomija.
 *
 * „Puste” liczymy po tekście, nie po HTML-u: pole `RichText` po skasowaniu
 * treści potrafi zostawić `<br>` albo twardą spację, a to nie jest jadłospis.
 *
 * @param array $dzien Dzień z {@see przedszkole_jadlospis_dzien()}.
 * @return array<string, string> Klucz => HTML posiłku.
 */
function przedszkole_jadlospis_wpisane( array $dzien ) {
	$wpisane = array();

	foreach ( array_keys( przedszkole_jadlospis_posilki() ) as $klucz ) {
		$tekst = html_entity_decode( wp_strip_all_tags( $dzien[ $klucz ] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( '' !== preg_replace( '/[\s\x{00A0}]+/u', '', $tekst ) ) {
			$wpisane[ $klucz ] = $dzien[ $klucz ];
		}
	}

	return $wpisane;
}
