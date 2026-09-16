<?php
/**
 * Rok szkolny na listach wpisów.
 *
 * Przedszkole żyje rokiem szkolnym, nie kalendarzowym. Listy aktualności
 * pokazują domyślnie rok bieżący, starsze roczniki wchodzą przez `?rok=`.
 *
 * Rok wyliczamy z daty publikacji, a nie z taksonomii ani pola własnego.
 * Dzięki temu nauczycielka nic nie klika, nie ma kilkudziesięciu pustych
 * terminów, a przeniesienie wpisu do innego rocznika to zmiana daty
 * publikacji — coś, co WordPress i tak potrafi.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kategoria wyjęta spod podziału na lata.
 *
 * Artykuły logopedki to poradniki — „Rozwój mowy dziecka" z 2016 roku jest
 * tak samo aktualny jak wpis z wczoraj. Cięcie po roczniku schowałoby je
 * bez powodu.
 */
const PRZEDSZKOLE_LOGOPEDA = 'logopeda';

/**
 * Rok szkolny dla podanej daty.
 *
 * Granica to 1 września. Wrzesień zaczyna rok `R/R+1`, wszystko przed nim
 * należy jeszcze do `R-1/R`.
 *
 * @param string|null $data Data w formacie zrozumiałym dla `strtotime()`.
 *                          Null oznacza teraz.
 * @return string Na przykład `2026/2027`.
 */
function przedszkole_rok_szkolny( $data = null ) {
	$znacznik = null === $data ? time() : strtotime( $data );

	$rok     = (int) wp_date( 'Y', $znacznik );
	$miesiac = (int) wp_date( 'n', $znacznik );

	if ( $miesiac < 9 ) {
		--$rok;
	}

	return $rok . '/' . ( $rok + 1 );
}

/**
 * `2026/2027` → `2026-2027`. Ukośnik nie przejdzie w adresie.
 *
 * @param string $rok Rok szkolny z ukośnikiem.
 * @return string
 */
function przedszkole_rok_slug( $rok ) {
	return str_replace( '/', '-', $rok );
}

/**
 * `2026-2027` → `2026/2027`.
 *
 * @param string $slug Rok szkolny z myślnikiem.
 * @return string
 */
function przedszkole_rok_z_slug( $slug ) {
	return str_replace( '-', '/', $slug );
}

/**
 * Lista roczników, od bieżącego do najstarszego wpisu w serwisie.
 *
 * Lista jest wspólna dla całego serwisu, a nie liczona osobno dla każdej
 * kategorii — to jedno zapytanie zamiast siedmiu grupowań. Rocznik bez wpisów
 * w danej kategorii nie jest ślepym zaułkiem: użytkownik zobaczy komunikat
 * z Zadania 4.
 *
 * @return string[] Slugi, malejąco.
 */
function przedszkole_lata_szkolne() {
	$lata = get_transient( 'przedszkole_lata' );

	if ( is_array( $lata ) && $lata ) {
		return $lata;
	}

	global $wpdb;

	$najstarszy = $wpdb->get_var(
		"SELECT MIN( post_date ) FROM {$wpdb->posts}
		 WHERE post_type = 'post' AND post_status = 'publish'"
	);

	$do = (int) substr( przedszkole_rok_szkolny(), 0, 4 );
	$od = $najstarszy ? (int) substr( przedszkole_rok_szkolny( $najstarszy ), 0, 4 ) : $do;

	$lata = array();

	for ( $rok = $do; $rok >= $od; $rok-- ) {
		$lata[] = przedszkole_rok_slug( $rok . '/' . ( $rok + 1 ) );
	}

	set_transient( 'przedszkole_lata', $lata, DAY_IN_SECONDS );

	return $lata;
}

/**
 * Czyści listę roczników po zmianie wpisów.
 */
function przedszkole_zapomnij_lata() {
	delete_transient( 'przedszkole_lata' );
}
add_action( 'save_post', 'przedszkole_zapomnij_lata' );
add_action( 'deleted_post', 'przedszkole_zapomnij_lata' );

/**
 * Rocznik żądany w adresie, po walidacji.
 *
 * **To jest zabezpieczenie, nie kosmetyka.** Wartość trafia prosto do
 * `date_query`, więc przyjmujemy wyłącznie slugi obecne na liście roczników —
 * przynależność do zbioru, nie dopasowanie wzorca. Cokolwiek innego cicho
 * spada do roku bieżącego.
 *
 * @return string Slug rocznika.
 */
function przedszkole_rok_z_zapytania() {
	// Odczyt publicznego filtra listy, bez zmiany stanu — nonce nie ma tu sensu.
	$rok = isset( $_GET['rok'] ) ? sanitize_text_field( wp_unslash( $_GET['rok'] ) ) : '';

	if ( in_array( $rok, przedszkole_lata_szkolne(), true ) ) {
		return $rok;
	}

	return przedszkole_rok_slug( przedszkole_rok_szkolny() );
}

/**
 * Zakres dat rocznika w postaci `date_query`.
 *
 * @param string $slug Slug rocznika, na przykład `2026-2027`.
 * @return array
 */
function przedszkole_zakres_roku( $slug ) {
	$rok = (int) substr( $slug, 0, 4 );

	return array(
		array(
			'after'     => array(
				'year'  => $rok,
				'month' => 9,
				'day'   => 1,
			),
			'before'    => array(
				'year'  => $rok + 1,
				'month' => 8,
				'day'   => 31,
			),
			'inclusive' => true,
		),
	);
}
