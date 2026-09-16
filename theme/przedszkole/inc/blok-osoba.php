<?php
/**
 * Blok „Osoba” — kafelek pracownika na stronie „Kadra”.
 *
 * Wbrew zasadzie „bez własnych bloków”: wcześniej kafelek był wzorcem na
 * zwykłych kolumnach, więc układ siedział w treści każdej strony. Pracownik
 * dodający osobę musiał trafić w szerokość kolumny, klasę wariantu stylu
 * i wpisać inicjały dwa razy — a poprawka wyglądu znaczyła przejście po
 * wszystkich kafelkach. Blok zdejmuje to z treści: w edytorze zostają
 * zdjęcie, imię, tytuł i biogram, resztę rysuje `blocks/osoba/render.php`.
 *
 * Bez kroku budowania — `edytor.js` to zwykły JavaScript bez JSX,
 * a zależności skryptu wylicza ręcznie `edytor.asset.php`.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rejestracja bloku z katalogu `blocks/osoba` (metadane w `block.json`).
 */
function przedszkole_rejestruj_bloki() {
	register_block_type( get_theme_file_path( 'blocks/osoba' ) );
}
add_action( 'init', 'przedszkole_rejestruj_bloki' );

/**
 * Własna kategoria w wyszukiwarce bloków.
 *
 * Ta sama nazwa co kategoria wzorców — pracownik ma jedno miejsce z naszymi
 * elementami, niezależnie od tego, czy to blok, czy wzorzec.
 *
 * @param array $kategorie Kategorie bloków.
 * @return array
 */
function przedszkole_kategoria_blokow( $kategorie ) {
	array_unshift(
		$kategorie,
		array(
			'slug'  => 'przedszkole',
			'title' => __( 'Przedszkole', 'przedszkole' ),
			'icon'  => null,
		)
	);
	return $kategorie;
}
add_filter( 'block_categories_all', 'przedszkole_kategoria_blokow' );

/**
 * Inicjały z imienia i nazwiska — pierwsze litery dwóch pierwszych członów
 * pisanych wielką literą.
 *
 * Stopień naukowy zapisany małymi literami („mgr”, „lic.”, „dr”) wypada sam,
 * więc nie trzeba go nigdzie wyliczać ani odcinać osobno.
 *
 * Ta sama reguła stoi drugi raz w `blocks/osoba/edytor.js` — edytor liczy
 * inicjały w przeglądarce, żeby pokazać je od razu po wpisaniu nazwiska,
 * a wspólnego kodu nie ma jak współdzielić bez kroku budowania.
 * Zmieniając jedno, popraw drugie.
 *
 * @param string $imie Imię i nazwisko, ewentualnie ze stopniem z przodu.
 * @return string Dwie wielkie litery, ewentualnie mniej.
 */
function przedszkole_inicjaly( $imie ) {
	$litery = '';

	foreach ( preg_split( '/\s+/u', trim( $imie ), -1, PREG_SPLIT_NO_EMPTY ) as $czlon ) {
		$pierwsza = mb_substr( $czlon, 0, 1 );

		if ( ! preg_match( '/^\p{Lu}$/u', $pierwsza ) ) {
			continue;
		}

		$litery .= $pierwsza;

		if ( 2 === mb_strlen( $litery ) ) {
			break;
		}
	}

	return $litery;
}
