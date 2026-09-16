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
 * Celowo bez `strtotime()`. `post_date` w bazie jest zapisany w czasie
 * lokalnym serwisu, a `strtotime()` czyta string tak, jakby był w UTC —
 * w połączeniu z `wp_date()` (który dokłada offset strefy jeszcze raz)
 * data przesuwa się dwukrotnie i wpis z późnego wieczora 31 sierpnia trafia
 * do złego rocznika. `mysql2date()` traktuje string jak już-lokalny, bez
 * dodatkowej konwersji, `current_time()` to lokalny odpowiednik `time()`.
 *
 * @param string|null $data Data w formacie zrozumiałym dla `mysql2date()`
 *                          (np. `post_date`). Null oznacza teraz.
 * @return string Na przykład `2026/2027`.
 */
function przedszkole_rok_szkolny( $data = null ) {
	if ( null === $data ) {
		$rok     = (int) current_time( 'Y' );
		$miesiac = (int) current_time( 'n' );
	} else {
		$rok     = (int) mysql2date( 'Y', $data );
		$miesiac = (int) mysql2date( 'n', $data );
	}

	// Data, której nie dało się rozpoznać ($rok === 0), oraz zerowa data
	// MySQL ('0000-00-00 00:00:00', którą mysql2date() parsuje na formalnie
	// poprawny rok -1) - traktuj obie jak "teraz". Import z Joomli i inne
	// uszkodzone dane potrafią taką wartość podrzucić, a rok publikacji
	// sprzed powstania WordPressa (1970) to i tak sygnał błędnych danych.
	if ( $rok < 1970 ) {
		$rok     = (int) current_time( 'Y' );
		$miesiac = (int) current_time( 'n' );
	}

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

	// Bez zmiennych w zapytaniu - prepare() nie miałby tu czego przygotować.
	$najstarszy = $wpdb->get_var(
		"SELECT MIN( post_date ) FROM {$wpdb->posts}
		 WHERE post_type = 'post' AND post_status = 'publish'"
	);

	$do = (int) substr( przedszkole_rok_szkolny(), 0, 4 );
	$od = $najstarszy ? (int) substr( przedszkole_rok_szkolny( $najstarszy ), 0, 4 ) : $do;

	// Zabezpieczenie pętli, nie tylko samej daty: gdyby `$od` mimo wszystko
	// wyszło większe od `$do` albo odległe o więcej niż stulecie, przytnij
	// do bieżącego rocznika zamiast generować tysiące pozycji w transiencie.
	if ( $od > $do || ( $do - $od ) > 100 ) {
		$od = $do;
	}

	$lata = array();

	for ( $rok = $do; $rok >= $od; $rok-- ) {
		$lata[] = przedszkole_rok_slug( $rok . '/' . ( $rok + 1 ) );
	}

	set_transient( 'przedszkole_lata', $lata, DAY_IN_SECONDS );

	return $lata;
}

/**
 * Czyści listę roczników po zmianie wpisów.
 *
 * `save_post` odpala się też dla rewizji, autozapisów i każdego typu treści
 * (stron, mediów, pozycji menu), więc bez tych warunków transient padałby
 * przy każdym autozapisie czegokolwiek, nie tylko wpisu. `deleted_post`
 * odpala się przed `clean_post_cache()` (rdzeń WP, `wp_delete_post()`),
 * więc `get_post_type()` tu jeszcze działa i jeden callback obsłuży oba haki.
 *
 * @param int $post_id ID wpisu.
 */
function przedszkole_zapomnij_lata( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

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

/**
 * Czy bieżący widok podlega cięciu po roczniku.
 *
 * Wydzielone, bo odpowiedzi potrzebują też przełącznik lat i komunikat
 * o pustym roczniku — a warunek musi być w trzech miejscach ten sam.
 *
 * @return bool
 */
function przedszkole_rok_aktywny() {
	if ( is_category( PRZEDSZKOLE_LOGOPEDA ) ) {
		return false;
	}

	return is_home() || is_category();
}

/**
 * Ogranicza listy wpisów do jednego rocznika.
 *
 * Wchodzi wyłącznie na listę aktualności i archiwa kategorii. Wyszukiwarka,
 * archiwa dat i pojedyncze wpisy zostają nietknięte — tam cięcie tylko
 * przeszkadzałoby. Panel również, bo redaktor musi widzieć całość.
 *
 * @param WP_Query $zapytanie Modyfikowane zapytanie.
 */
function przedszkole_tnij_po_roku( $zapytanie ) {
	if ( is_admin() || ! $zapytanie->is_main_query() ) {
		return;
	}

	if ( ! $zapytanie->is_home() && ! $zapytanie->is_category() ) {
		return;
	}

	if ( $zapytanie->is_category( PRZEDSZKOLE_LOGOPEDA ) ) {
		return;
	}

	$zapytanie->set( 'date_query', przedszkole_zakres_roku( przedszkole_rok_z_zapytania() ) );
}
add_action( 'pre_get_posts', 'przedszkole_tnij_po_roku' );
