<?php
/**
 * Drobne funkcje pomocnicze motywu.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slugi grup przedszkolnych. Każda ma własny kolor w palecie.
 *
 * Kolejność jest ta sama co w menu głównym i na kafelkach podstron —
 * od najmłodszej grupy do najstarszej. Filtr nad listą aktualności
 * wypisuje grupy w tej kolejności, więc zmiana tu przestawia filtr.
 *
 * @return string[]
 */
function przedszkole_grupy() {
	return array( 'misie', 'zajaczki', 'zabki', 'kotki', 'wiewiorki', 'jezyki' );
}

/**
 * Kąciki specjalistów: slug strony i kategorii → nagłówek sekcji z wpisami.
 *
 * Strona „Kącik logopedy” ma slug `logopeda`, „Kącik pedagoga” — `pedagog`,
 * i każda dzieli slug z kategorią o tej samej nazwie. Wiązanie po slugu jest
 * to samo co przy grupach, więc nie trzeba niczego łączyć ręcznie w panelu.
 *
 * Slugi bez przedrostka `kacik-`, bo stary adres Joomli to
 * `dla-rodzicow/logopeda` — przekierowania 301 z Etapu 9 robią się trywialne.
 * `pedagog` idzie tym samym wzorem, choć starego odpowiednika nie ma.
 *
 * Te kategorie są wyjęte spod podziału na roczniki: poradnik nie ma daty
 * ważności — „Rozwój mowy dziecka” z 2016 jest tak samo aktualny jak wpis
 * z wczoraj. Cięcie po roczniku schowałoby go bez powodu.
 *
 * Kolejny specjalista = jedna linia tutaj plus strona i kategoria o tym
 * slugu ({@see tools/kaciki.sh}). Motyw nie wymaga niczego więcej.
 *
 * @return array<string, string> Slug → nagłówek sekcji z wpisami.
 */
function przedszkole_kaciki() {
	return array(
		'logopeda' => __( 'Artykuły logopedy', 'przedszkole' ),
		'pedagog'  => __( 'Artykuły pedagoga', 'przedszkole' ),
	);
}

/**
 * Zwraca pierwszą kategorię wpisu, która jest grupą przedszkolną.
 *
 * @param int|null $post_id ID wpisu.
 * @return WP_Term|null
 */
function przedszkole_grupa_wpisu( $post_id = null ) {
	$terms = get_the_category( $post_id );

	if ( empty( $terms ) ) {
		return null;
	}

	$grupy = przedszkole_grupy();

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, $grupy, true ) ) {
			return $term;
		}
	}

	return $terms[0];
}

/**
 * Wypisuje etykietę kategorii wpisu, pokolorowaną jeśli to grupa.
 *
 * @param int|null $post_id ID wpisu.
 */
function przedszkole_etykieta( $post_id = null ) {
	$term = przedszkole_grupa_wpisu( $post_id );

	if ( ! $term ) {
		return;
	}

	$modyfikator = in_array( $term->slug, przedszkole_grupy(), true ) ? ' pill--' . $term->slug : '';

	printf(
		'<a class="pill%1$s" href="%2$s">%3$s</a>',
		esc_attr( $modyfikator ),
		esc_url( get_category_link( $term ) ),
		esc_html( $term->name )
	);
}

/**
 * Dane placówki w postaci nadającej się dla maszyn.
 *
 * Te same informacje stoją w treści strony (wzorzec „Dane kontaktowe”), ale tam
 * są zwykłym tekstem do poprawienia przez pracownika. Dane strukturalne
 * wymagają rozbicia na pola — stąd druga kopia. **Zmiana adresu czy telefonu
 * musi trafić w oba miejsca**; nie da się tego uniknąć bez zamiany wzorca
 * w formularz, czego świadomie nie robimy (Etap 5).
 *
 * Współrzędne wzięte z odnośnika „Wyznacz trasę” we wzorcu „Mapa dojazdu”.
 *
 * @return array<string, string|float>
 */
function przedszkole_dane_placowki() {
	return array(
		'ulica'      => 'ul. Świętej Jadwigi Królowej 4',
		'kod'        => '32-090',
		'miasto'     => 'Słomniki',
		'kraj'       => 'PL',
		'telefon'    => '+48510217005',
		'email'      => 'sekretariat@przedszkoleslomniki.pl',
		'szerokosc'  => 50.2451488,
		'dlugosc'    => 20.0886038,
		'otwarcie'   => '06:30',
		'zamkniecie' => '17:00',
	);
}

/**
 * Skrócenie tekstu do długości, którą wyszukiwarka pokaże w wyniku.
 *
 * Google obcina opis w okolicy 160 znaków. Tniemy sami, na granicy słowa,
 * żeby zdanie nie urywało się w połowie wyrazu.
 *
 * @param string $tekst Tekst wejściowy, może zawierać znaczniki.
 * @param int    $limit Maksymalna długość w znakach.
 * @return string
 */
function przedszkole_skroc_opis( $tekst, $limit = 160 ) {
	$tekst = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $tekst ) ) );

	if ( '' === $tekst || mb_strlen( $tekst ) <= $limit ) {
		return $tekst;
	}

	$uciety = mb_substr( $tekst, 0, $limit );
	$spacja = mb_strrpos( $uciety, ' ' );

	if ( false !== $spacja ) {
		$uciety = mb_substr( $uciety, 0, $spacja );
	}

	/*
	 * Ogon ucinamy wyrażeniem z modyfikatorem `u`, nie `rtrim()`. Lista znaków
	 * w `rtrim()` jest listą bajtów, a myślniki „–” i „—” zajmują po trzy —
	 * przy innym tekście dałoby się w ten sposób uciąć pół znaku.
	 */
	return preg_replace( '/[\s,.;:–—-]+$/u', '', $uciety ) . '…';
}

/**
 * Teksty sekcji powitalnej na stronie głównej.
 *
 * Stałe, w kodzie motywu. Wcześniej powitanie składało się z treści strony
 * „Strona główna” — ale tę treść personel zmieniał co chwilę pod bieżące
 * komunikaty, a razem z nimi znikał jedyny `<h1>` strony i opis, który
 * wyszukiwarka pokazuje w wynikach. Treść strony obsługuje dziś sekcję
 * ogłoszeń (`front-page.php`), a powitanie się nie rusza.
 *
 * W jednej funkcji, bo z opisu korzysta też `inc/seo.php`.
 *
 * @return array{tytul: string, opis: string, przycisk: string, adres: string}
 */
function przedszkole_powitanie() {
	return array(
		'tytul'    => __( 'Witamy w naszym przedszkolu', 'przedszkole' ),
		'opis'     => __( 'Jesteśmy miejscem, w którym dzieci uczą się przez zabawę, poznają świat i zdobywają pierwszych przyjaciół. Zapraszamy do zapoznania się z naszą ofertą.', 'przedszkole' ),
		'przycisk' => __( 'Poznaj przedszkole', 'przedszkole' ),
		'adres'    => home_url( '/grupy/' ),
	);
}

/**
 * Czy wyrenderowana treść ma cokolwiek do pokazania.
 *
 * Pusty edytor nie znaczy pustego pola w bazie: po skasowaniu tekstu zostaje
 * pusty akapit `<p></p>`, czasem twarda spacja. Sprawdzamy więc wynik po
 * renderowaniu — bez znaczników i białych znaków — a osobno obrazki
 * i osadzenia, które tekstu nie mają, a treścią są.
 *
 * @param string $html Treść po filtrze `the_content`.
 * @return bool
 */
function przedszkole_tresc_niepusta( $html ) {
	if ( preg_match( '/<(img|iframe|video|svg)\b/i', $html ) ) {
		return true;
	}

	$tekst = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	return '' !== preg_replace( '/[\s\x{00A0}\x{200B}]+/u', '', $tekst );
}
