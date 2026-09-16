<?php
/**
 * Rola „Intendent” — jedna osoba, jedna strona.
 *
 * Etap 6 rozstrzygnął, że natywne role wystarczają, i to nadal jest prawda dla
 * dyrekcji i nauczycieli. Intendent jest wyjątkiem, którego WordPress nie ma
 * czym pokryć: ma aktualizować wyłącznie „Jadłospis”, a najwęższa rola
 * z dostępem do stron — Editor — otwiera wszystkie dwadzieścia.
 *
 * Świadomie bez wtyczki (PublishPress Permissions i podobne): silnik uprawnień
 * z własnymi tabelami i ekranem ustawień pod jedną regułę na stronie o dwudziestu
 * stronach i ośmiu kontach. Tutaj reguła jest w repozytorium i widać ją w diffie.
 *
 * Trzy warstwy, bo sama pierwsza nie wystarcza:
 * 1. Rola z wąskim zestawem uprawnień — czego nie ma, tego nie obejdzie.
 * 2. `map_meta_cap` — twarda bramka na konkretną stronę. To ona odpowiada 403.
 * 3. `pre_get_posts` — listy w panelu pokazują tylko to, co dostępne. Sama
 *    kosmetyka, ale bez niej intendent widzi tytuły i autorów cudzych treści.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nazwa roli w bazie.
 */
const PRZEDSZKOLE_ROLA_INTENDENT = 'intendent';

/**
 * Slug strony, którą intendent redaguje.
 *
 * Slug, nie ID. Identyfikator jest przypadkową liczbą z migracji i przy
 * odtworzeniu instalacji z czystej bazy byłby inny; slug jest w adresie strony,
 * więc nikt go nie zmieni bez zastanowienia.
 */
const PRZEDSZKOLE_STRONA_INTENDENTA = 'jadlospis';

/**
 * Wersja definicji roli.
 *
 * Uprawnienia ról WordPress trzyma w bazie (`wp_user_roles`), nie w kodzie —
 * `add_role()` na istniejącej roli nic nie robi. Bez tego znacznika poprawka
 * listy uprawnień w tym pliku nie dotarłaby do działającej instalacji.
 * Zmieniasz `przedszkole_uprawnienia_roli()` — podbij tę stałą.
 */
const PRZEDSZKOLE_WERSJA_ROL = 1;

/**
 * Uprawnienia roli Intendent.
 *
 * Krótko, bo każde uprawnienie to coś, czego potem trzeba pilnować:
 * - `read` — panel i własny profil, bez tego nie ma logowania do kokpitu,
 * - `upload_files` — wgranie PDF-a z jadłospisem,
 * - `edit_pages` — bez tego WordPress nie pokaże menu „Strony” i nie wpuści
 *   do edytora; na konkretną stronę i tak przepuszcza dopiero bramka niżej,
 * - `publish_pages` — zapis strony, która jest już opublikowana, idzie przez
 *   REST ze statusem `publish`, a ten status kontroler sprawdza tym
 *   uprawnieniem (`class-wp-rest-posts-controller.php`, `handle_status_param`).
 *   Bez niego edytor blokowy odmawia zapisu komunikatem o braku prawa
 *   do publikowania.
 *
 * Czego nie ma: `edit_others_pages`, `edit_published_pages`, `delete_pages`.
 * Bramka `map_meta_cap` zwraca własny zestaw uprawnień, więc rozróżnienia
 * „cudze / opublikowane” nigdy nie dochodzą do głosu, a usuwanie jest odcięte
 * na sztywno.
 *
 * @return array<string, bool>
 */
function przedszkole_uprawnienia_roli() {
	return array(
		'read'          => true,
		'upload_files'  => true,
		'edit_pages'    => true,
		'publish_pages' => true,
	);
}

/**
 * Założenie i aktualizacja roli.
 *
 * `remove_role()` przed `add_role()`, żeby zmiana listy uprawnień faktycznie
 * weszła. Konta nie tracą przy tym roli: przypisanie siedzi w metadanych
 * użytkownika, definicja w opcji — kasujemy definicję, nie przypisanie.
 */
function przedszkole_zaloz_role() {
	if ( (int) get_option( 'przedszkole_wersja_rol' ) === PRZEDSZKOLE_WERSJA_ROL ) {
		return;
	}

	remove_role( PRZEDSZKOLE_ROLA_INTENDENT );
	add_role(
		PRZEDSZKOLE_ROLA_INTENDENT,
		__( 'Intendent', 'przedszkole' ),
		przedszkole_uprawnienia_roli()
	);

	// Zakładanie stron przeniesione z `edit_pages` na własne uprawnienie
	// (patrz `przedszkole_strony_bez_zakladania()`) — role, które robiły to
	// dotąd, muszą je dostać, inaczej dyrektor traci przycisk „Dodaj stronę”.
	foreach ( array( 'administrator', 'editor' ) as $nazwa ) {
		$rola = get_role( $nazwa );

		if ( $rola ) {
			$rola->add_cap( 'create_pages' );
		}
	}

	update_option( 'przedszkole_wersja_rol', PRZEDSZKOLE_WERSJA_ROL );
}
add_action( 'init', 'przedszkole_zaloz_role' );

/**
 * Czy konto jest intendentem.
 *
 * Po roli, nie po loginie — kont intendenta może być kiedyś dwa.
 *
 * @param int|null $id_uzytkownika ID konta; domyślnie zalogowane.
 * @return bool
 */
function przedszkole_czy_intendent( $id_uzytkownika = null ) {
	$uzytkownik = $id_uzytkownika ? get_userdata( $id_uzytkownika ) : wp_get_current_user();

	return $uzytkownik && in_array( PRZEDSZKOLE_ROLA_INTENDENT, (array) $uzytkownik->roles, true );
}

/**
 * ID strony „Jadłospis”.
 *
 * Zapytanie wprost do bazy, świadomie, bo dwa oczywistsze sposoby odpadają:
 *
 * - `get_page_by_path()` oczekuje pełnej ścieżki w hierarchii. Jadłospis jest
 *   dzieckiem „Dla rodziców”, więc sam slug nic nie znajduje, a wpisanie
 *   `dla-rodzicow/jadlospis` uzależnia uprawnienia od tego, gdzie akurat wisi
 *   strona w menu. Przepięcie jej wyżej odcięłoby intendenta bez śladu.
 * - `get_posts()` to `WP_Query`, czyli `pre_get_posts` — a tam niżej sami
 *   wołamy tę funkcję. Wyszukiwanie strony wpadłoby we własny filtr.
 *
 * Wynik zapamiętany na czas żądania: `map_meta_cap` chodzi przy każdym
 * sprawdzeniu uprawnienia, na liście stron kilkadziesiąt razy.
 *
 * @return int Zero, jeśli strony nie ma.
 */
function przedszkole_id_jadlospisu() {
	static $id = null;

	if ( null !== $id ) {
		return $id;
	}

	global $wpdb;

	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_name = %s AND post_type = 'page' AND post_status = 'publish'
			LIMIT 1",
			PRZEDSZKOLE_STRONA_INTENDENTA
		)
	);

	return $id;
}

/**
 * Bramka uprawnień intendenta.
 *
 * `map_meta_cap` zamienia pytanie o konkretną treść („czy może edytować wpis
 * 45”) na listę uprawnień, które konto musi mieć. Zwracamy własną listę:
 * `edit_pages` (intendent ma) dla jadłospisu, `do_not_allow` (nie ma nikt,
 * łącznie z administratorem) dla wszystkiego innego. Dlatego nie ma znaczenia,
 * kto jest autorem strony ani w jakim jest statusie.
 *
 * Załączniki osobno: intendent wgrywa pliki, a podpis, tytuł i tekst
 * alternatywny to już edycja załącznika. Bez tego wyjątku PDF ląduje w mediach
 * bez opisu, czego nie da się poprawić — wprost przeciwko dostępności.
 * Własny plik może też skasować (wgrał nie ten). Cudzych nie tyka.
 *
 * @param string[] $uprawnienia    Uprawnienia wyliczone dotąd.
 * @param string   $sprawdzane     Sprawdzane uprawnienie meta.
 * @param int      $id_uzytkownika ID konta.
 * @param array    $argumenty      Argumenty sprawdzenia; `[0]` to ID treści.
 * @return string[]
 */
function przedszkole_bramka_intendenta( $uprawnienia, $sprawdzane, $id_uzytkownika, $argumenty ) {
	$meta_tresci = array( 'edit_post', 'delete_post', 'publish_post', 'edit_page', 'delete_page' );

	if ( ! in_array( $sprawdzane, $meta_tresci, true ) ) {
		return $uprawnienia;
	}

	if ( ! przedszkole_czy_intendent( $id_uzytkownika ) ) {
		return $uprawnienia;
	}

	$tresc = isset( $argumenty[0] ) ? get_post( (int) $argumenty[0] ) : null;

	if ( ! $tresc ) {
		return array( 'do_not_allow' );
	}

	if ( 'attachment' === $tresc->post_type ) {
		return (int) $tresc->post_author === (int) $id_uzytkownika
			? array( 'upload_files' )
			: array( 'do_not_allow' );
	}

	// Usuwanie odcięte zawsze. Jadłospis jest w menu i wpiętej w nie treści —
	// skasowany zostawia po sobie błąd 404 w nawigacji.
	if ( in_array( $sprawdzane, array( 'delete_post', 'delete_page' ), true ) ) {
		return array( 'do_not_allow' );
	}

	return (int) $tresc->ID === przedszkole_id_jadlospisu()
		? array( 'edit_pages' )
		: array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'przedszkole_bramka_intendenta', 10, 4 );

/**
 * Zakładanie stron na własnym uprawnieniu.
 *
 * WordPress domyślnie wyprowadza „może dodać stronę” z `edit_pages` — tego
 * samego uprawnienia, które otwiera edytor istniejącej strony. Intendent musi
 * mieć drugie i nie może mieć pierwszego, więc rozdzielamy je na poziomie typu
 * treści: `create_posts` dostaje własną nazwę `create_pages`, przyznaną
 * administratorowi i redaktorowi w `przedszkole_zaloz_role()`.
 *
 * Tak jest czyściej niż przekierowaniem z `post-new.php`: WordPress sam chowa
 * pozycję menu i przycisk „Dodaj stronę”, a wejście wprost po adresie kończy
 * się natywnym 403, nie naszą obsługą wyjątku.
 *
 * @param array  $argumenty Argumenty rejestracji typu treści.
 * @param string $typ       Nazwa typu treści.
 * @return array
 */
function przedszkole_strony_bez_zakladania( $argumenty, $typ ) {
	if ( 'page' !== $typ ) {
		return $argumenty;
	}

	$argumenty['capabilities'] = isset( $argumenty['capabilities'] ) ? (array) $argumenty['capabilities'] : array();
	$argumenty['capabilities']['create_posts'] = 'create_pages';

	return $argumenty;
}
add_filter( 'register_post_type_args', 'przedszkole_strony_bez_zakladania', 10, 2 );

/**
 * Listy w panelu zawężone do tego, co intendent może tknąć.
 *
 * Strony: wyłącznie jadłospis. Media: wyłącznie własne pliki — w bibliotece są
 * zdjęcia dzieci z wszystkich grup i nie ma powodu, żeby kuchnia je przeglądała.
 *
 * Tylko panel i REST. Na froncie `get_pages()` chodzi przez `WP_Query`, więc
 * ten sam filtr wyciąłby zalogowanemu intendentowi połowę nawigacji.
 *
 * @param WP_Query $zapytanie Modyfikowane zapytanie.
 */
function przedszkole_listy_intendenta( $zapytanie ) {
	if ( ! is_admin() && ! wp_is_serving_rest_request() ) {
		return;
	}

	if ( ! przedszkole_czy_intendent() ) {
		return;
	}

	$typ = (array) $zapytanie->get( 'post_type' );

	if ( in_array( 'page', $typ, true ) ) {
		$zapytanie->set( 'post__in', array( przedszkole_id_jadlospisu() ) );
	}

	if ( in_array( 'attachment', $typ, true ) ) {
		$zapytanie->set( 'author', get_current_user_id() );
	}
}
add_action( 'pre_get_posts', 'przedszkole_listy_intendenta' );

/**
 * Skrót „Jadłospis” w menu „Strony”.
 *
 * Wygoda i obejście rdzenia naraz — drugie ważniejsze, więc od niego.
 *
 * Intendent ma `edit_pages`, ale nie ma `create_pages`, więc z podmenu „Strony”
 * zostaje jedna pozycja: „Wszystkie strony”. Adres tej pozycji jest identyczny
 * z adresem menu nadrzędnego, a rdzeń w takim przypadku kasuje całe podmenu
 * (`wp-admin/includes/menu.php`, „If there is only one submenu and it has same
 * destination as the parent”). Bez podmenu `get_admin_page_parent()` nie ma
 * czego dopasować i zwraca pustego rodzica, a wtedy `user_can_access_admin_page()`
 * sprawdza samo `edit.php` — które siedzi w `$_wp_menu_nopriv` jako niedostępne
 * menu „Wpisy”. Efekt: lista stron odpowiada 403 komuś, kto ma do niej prawo.
 *
 * Druga pozycja w podmenu zdejmuje warunek `count() === 1` i rdzeń zostawia
 * podmenu w spokoju. Wybór padł na skrót wprost do jadłospisu, bo to jedyna
 * strona na liście — intendent wchodzi w treść jednym kliknięciem zamiast
 * dwóch, a nawigacja edytora (powrót do listy) działa jak wszędzie.
 *
 * Slug z `.php` w środku WordPress traktuje jak zwykły odnośnik, nie jak
 * podstronę wtyczki — tak samo jak własne „edit.php?post_type=page”.
 */
function przedszkole_menu_intendenta() {
	if ( ! przedszkole_czy_intendent() ) {
		return;
	}

	$id = przedszkole_id_jadlospisu();

	if ( ! $id ) {
		return;
	}

	add_submenu_page(
		'edit.php?post_type=page',
		__( 'Jadłospis', 'przedszkole' ),
		__( 'Jadłospis', 'przedszkole' ),
		'edit_pages',
		'post.php?post=' . $id . '&action=edit'
	);
}
add_action( 'admin_menu', 'przedszkole_menu_intendenta' );
