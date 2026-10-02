<?php
/**
 * Sprawdzenie bloku „Jadłospis” — funkcje pomocnicze i front.
 *
 * Projekt nie ma PHPUnit. To zwykły skrypt dla `wp eval-file`: wypisuje
 * OK albo BŁĄD przy każdym sprawdzeniu i kończy się błędem wp-cli (kod 1),
 * jeśli choć jedno nie przeszło.
 *
 *   ddev exec wp --path=wp eval-file tools/test_jadlospis.php
 *
 * Front sprawdzany przez `render_block()` na sztucznych atrybutach — bez
 * dotykania treści strony „Jadłospis”. Zakresy dat są te same co
 * w `tools/test_jadlospis_edytor.js`: reguła stoi w PHP i w JS, oba testy
 * pilnują, żeby mówiły to samo.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'przedszkole_jadlospis_poczatek' ) ) {
	WP_CLI::error( 'Brak funkcji pomocniczych — czy inc/blok-jadlospis.php jest dołączony w functions.php?' );
}

$bledy = 0;

$sprawdz = function ( $opis, $warunek ) use ( &$bledy ) {
	if ( $warunek ) {
		WP_CLI::log( 'OK    ' . $opis );
		return;
	}

	++$bledy;
	WP_CLI::log( 'BŁĄD  ' . $opis );
};

$zakres = function ( $tekst ) {
	$poczatek = przedszkole_jadlospis_poczatek( $tekst );
	return $poczatek ? przedszkole_jadlospis_zakres( $poczatek ) : null;
};

$renderuj = function ( $atrybuty ) {
	return render_block(
		array(
			'blockName'    => 'przedszkole/jadlospis',
			'attrs'        => $atrybuty,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
};

// --- Daty ---

$sprawdz( 'zakres w jednym miesiącu', '22 – 26 czerwca 2026' === $zakres( '2026-06-22' ) );
$sprawdz( 'zakres przez granicę miesiąca', '29 września – 3 października 2025' === $zakres( '2025-09-29' ) );
$sprawdz( 'zakres przez granicę roku', '29 grudnia 2025 – 2 stycznia 2026' === $zakres( '2025-12-29' ) );
$sprawdz( 'przepełniona data odrzucona', null === przedszkole_jadlospis_poczatek( '2026-02-31' ) );
$sprawdz( 'śmieci odrzucone', null === przedszkole_jadlospis_poczatek( 'jutro' ) );
$sprawdz( 'pusta data odrzucona', null === przedszkole_jadlospis_poczatek( '' ) );
$sprawdz( 'nie-tekst odrzucony', null === przedszkole_jadlospis_poczatek( array() ) );

// --- Dni i posiłki ---

$dni = przedszkole_jadlospis_dni();
$sprawdz( 'pięć dni', 5 === count( $dni ) );
$sprawdz( 'poniedziałek w kolorze Żabek', 'zabki' === $dni[0]['grupa'] );
$sprawdz( 'piątek w kolorze Wiewiórek', 'wiewiorki' === $dni[4]['grupa'] );
$sprawdz( 'trzy posiłki w kolejności', array( 'sniadanie', 'obiad', 'podwieczorek' ) === array_keys( przedszkole_jadlospis_posilki() ) );

$dzien = przedszkole_jadlospis_dzien(
	array(
		'sniadanie'    => '<strong>mleko</strong>',
		'obiad'        => '<br>',
		'podwieczorek' => '&nbsp; ',
	)
);
$sprawdz( 'dzień uzupełniony o „wolny”', false === $dzien['wolny'] );
$sprawdz( 'pusty HTML nie liczy się jako posiłek', array( 'sniadanie' ) === array_keys( przedszkole_jadlospis_wpisane( $dzien ) ) );
$sprawdz( 'śmieci zamiast dnia dają pusty dzień', array() === przedszkole_jadlospis_wpisane( przedszkole_jadlospis_dzien( 'x' ) ) );

// --- Rejestracja ---

$sprawdz( 'blok zarejestrowany', WP_Block_Type_Registry::get_instance()->is_registered( 'przedszkole/jadlospis' ) );

// --- Front ---

$html = $renderuj(
	array(
		'poczatek' => '2026-06-22',
		'dni'      => array(
			array( 'sniadanie' => 'Kakao /<strong>mleko</strong>/<script>alert(1)</script>' ),
			array(
				'wolny' => true,
				'obiad' => 'UKRYTY OBIAD',
			),
		),
	)
);

$sprawdz( 'pięć kart', 5 === substr_count( $html, '<li class="jadlospis__dzien ' ) );
$sprawdz( 'klasa alignfull na bloku', false !== strpos( $html, 'alignfull' ) );
$sprawdz( 'nagłówek z zakresem', false !== strpos( $html, '>22 – 26 czerwca 2026</h2>' ) );
$sprawdz( 'sekcja opisana nagłówkiem', 1 === preg_match( '/aria-labelledby="(jadlospis-zakres-\d+)".*id="\1"/s', $html ) );
$sprawdz( 'data pierwszej karty', false !== strpos( $html, 'data-data="2026-06-22"' ) );
$sprawdz( 'data ostatniej karty', false !== strpos( $html, 'data-data="2026-06-26"' ) );
$sprawdz( 'data w dopełniaczu', false !== strpos( $html, '>22 czerwca</time>' ) );
$sprawdz( 'kolor poniedziałku', false !== strpos( $html, 'jadlospis__dzien--zabki' ) );
$sprawdz( 'pogrubienie zostaje', false !== strpos( $html, '<strong>mleko</strong>' ) );
$sprawdz( 'skrypt wycięty', false === strpos( $html, '<script' ) );
$sprawdz( 'pusty obiad bez sekcji', false === strpos( $html, 'jadlospis__posilek--obiad' ) );
$sprawdz( 'dzień wolny', 1 === substr_count( $html, 'class="jadlospis__wolne"' ) );
$sprawdz( 'treść dnia wolnego ukryta', false === strpos( $html, 'UKRYTY OBIAD' ) );
$sprawdz( 'trzy dni w przygotowaniu', 3 === substr_count( $html, 'class="jadlospis__pusty"' ) );
$sprawdz( 'plakietki „Dziś” ukryte', 5 === substr_count( $html, 'class="jadlospis__dzis" hidden' ) );

$bez_daty = $renderuj( array( 'poczatek' => 'zle' ) );
$sprawdz( 'bez daty: pięć kart', 5 === substr_count( $bez_daty, '<li class="jadlospis__dzien ' ) );
$sprawdz( 'bez daty: bez nagłówka', false === strpos( $bez_daty, '<h2' ) );
$sprawdz( 'bez daty: bez aria-labelledby', false === strpos( $bez_daty, 'aria-labelledby' ) );
$sprawdz( 'bez daty: bez data-data', false === strpos( $bez_daty, 'data-data' ) );
$sprawdz( 'bez daty: bez plakietek', false === strpos( $bez_daty, 'jadlospis__dzis' ) );

// --- Skrypty --- (Zadania 5 i 6 dopisują tu swoje sprawdzenia)

if ( $bledy ) {
	WP_CLI::error( $bledy . ' sprawdzeń nie przeszło.' );
}

WP_CLI::success( 'Wszystko przeszło.' );
