<?php
/**
 * Role własne motywu — konto specjalisty: jedna strona, czasem jedna kategoria.
 *
 * Etap 6 rozstrzygnął, że natywne role wystarczają, i to nadal jest prawda dla
 * dyrekcji (Editor) i nauczycielek (Author). Wyjątki są dwa i mają ten sam
 * kształt: osoba ma prowadzić **jedną** stronę, a najwęższa natywna rola
 * z dostępem do stron — Editor — otwiera wszystkie dwadzieścia.
 *
 * - **Intendent** aktualizuje „Jadłospis”. Nic poza tym.
 * - **Pedagog** prowadzi „Kącik pedagoga” i pisze poradniki do kategorii
 *   `pedagog`. Wpisy zna rdzeń (rola Author), ale przypisania do jednej
 *   kategorii nie ma czym wymusić — patrz {@see przedszkole_wymus_kategorie()}.
 *
 * Świadomie bez wtyczki (PublishPress Permissions i podobne): silnik uprawnień
 * z własnymi tabelami i ekranem ustawień pod dwie reguły na stronie o dwudziestu
 * stronach i dziewięciu kontach. Tutaj reguły są w repozytorium i widać je
 * w diffie; konfiguracja wtyczki siedziałaby w bazie i przy wdrożeniu trzeba by
 * ją odtwarzać z pamięci. Trzeci taki przypadek — przelicz na nowo.
 *
 * Trzy warstwy, bo sama pierwsza nie wystarcza:
 * 1. Rola z wąskim zestawem uprawnień — czego nie ma, tego nie obejdzie.
 * 2. `map_meta_cap` — twarda bramka na konkretną stronę. To ona odpowiada 403.
 * 3. `pre_get_posts` — listy w panelu pokazują tylko to, co dostępne. Sama
 *    kosmetyka, ale bez niej specjalista widzi tytuły i autorów cudzych treści.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wersja definicji ról.
 *
 * Uprawnienia ról WordPress trzyma w bazie (`wp_user_roles`), nie w kodzie —
 * `add_role()` na istniejącej roli nic nie robi. Bez tego znacznika poprawka
 * listy uprawnień w tym pliku nie dotarłaby do działającej instalacji.
 * Zmieniasz {@see przedszkole_role_wlasne()} — podbij tę stałą.
 *
 * 2 — doszła rola „Pedagog” (2026-09-16).
 */
const PRZEDSZKOLE_WERSJA_ROL = 2;

/**
 * Role własne motywu.
 *
 * Każda pozycja to jedno konto specjalisty:
 *
 * - `nazwa` — etykieta widoczna w panelu,
 * - `strona` — **slug** strony, którą rola redaguje. Slug, nie ID:
 *   identyfikator jest przypadkową liczbą z migracji i przy odtworzeniu
 *   instalacji z czystej bazy byłby inny, a slug stoi w adresie strony, więc
 *   nikt go nie zmieni bez zastanowienia,
 * - `kategoria` — opcjonalny slug kategorii wymuszanej na wpisach tej roli;
 *   brak klucza znaczy „rola nie pisze wpisów”,
 * - `uprawnienia` — lista możliwie krótka, bo każde uprawnienie to coś,
 *   czego potem trzeba pilnować.
 *
 * Wspólne uprawnienia stron:
 * - `read` — panel i własny profil, bez tego nie ma logowania do kokpitu,
 * - `upload_files` — załącznik do strony albo zdjęcie do wpisu,
 * - `edit_pages` — bez tego WordPress nie pokaże menu „Strony” i nie wpuści
 *   do edytora; na konkretną stronę i tak przepuszcza dopiero bramka niżej,
 * - `publish_pages` — zapis strony, która jest już opublikowana, idzie przez
 *   REST ze statusem `publish`, a ten status kontroler sprawdza tym
 *   uprawnieniem (`class-wp-rest-posts-controller.php`, `handle_status_param`).
 *   Bez niego edytor blokowy odmawia zapisu komunikatem o braku prawa
 *   do publikowania.
 *
 * Czego nie ma nigdzie: `edit_others_pages`, `edit_published_pages`,
 * `delete_pages`, `create_pages`. Bramka `map_meta_cap` zwraca własny zestaw
 * uprawnień, więc rozróżnienia „cudze / opublikowane” nigdy nie dochodzą
 * do głosu, a usuwanie stron jest odcięte na sztywno.
 *
 * Pedagog dostaje ponadto uprawnienia do wpisów **bez** `*_others_*` — to już
 * zwykły zestaw autora, obsługiwany przez rdzeń. Bramka niżej przepuszcza
 * wpisy bez zmian, więc obowiązuje natywna zasada „tylko własne”.
 *
 * @return array<string, array<string, mixed>>
 */
function przedszkole_role_wlasne() {
	$strony = array(
		'read'          => true,
		'upload_files'  => true,
		'edit_pages'    => true,
		'publish_pages' => true,
	);

	$wpisy = array(
		'edit_posts'             => true,
		'publish_posts'          => true,
		'edit_published_posts'   => true,
		'delete_posts'           => true,
		'delete_published_posts' => true,
	);

	return array(
		'intendent' => array(
			'nazwa'       => __( 'Intendent', 'przedszkole' ),
			'strona'      => 'jadlospis',
			'uprawnienia' => $strony,
		),
		'pedagog'   => array(
			'nazwa'       => __( 'Pedagog', 'przedszkole' ),
			'strona'      => 'pedagog',
			'kategoria'   => 'pedagog',
			'uprawnienia' => array_merge( $strony, $wpisy ),
		),
	);
}

/**
 * Założenie i aktualizacja ról.
 *
 * `remove_role()` przed `add_role()`, żeby zmiana listy uprawnień faktycznie
 * weszła. Konta nie tracą przy tym roli: przypisanie siedzi w metadanych
 * użytkownika, definicja w opcji — kasujemy definicję, nie przypisanie.
 */
function przedszkole_zaloz_role() {
	if ( (int) get_option( 'przedszkole_wersja_rol' ) === PRZEDSZKOLE_WERSJA_ROL ) {
		return;
	}

	foreach ( przedszkole_role_wlasne() as $nazwa_roli => $opis ) {
		remove_role( $nazwa_roli );
		add_role( $nazwa_roli, $opis['nazwa'], $opis['uprawnienia'] );
	}

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
 * Rola własna konta, jeśli ją ma.
 *
 * Po roli, nie po loginie — kont pedagoga może być kiedyś dwa.
 *
 * @param int|null $id_uzytkownika ID konta; domyślnie zalogowane.
 * @return string Nazwa roli albo pusty łańcuch.
 */
function przedszkole_rola_wlasna( $id_uzytkownika = null ) {
	$uzytkownik = $id_uzytkownika ? get_userdata( $id_uzytkownika ) : wp_get_current_user();

	if ( ! $uzytkownik ) {
		return '';
	}

	foreach ( array_keys( przedszkole_role_wlasne() ) as $nazwa_roli ) {
		if ( in_array( $nazwa_roli, (array) $uzytkownik->roles, true ) ) {
			return $nazwa_roli;
		}
	}

	return '';
}

/**
 * ID strony o danym slugu.
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
 * Szkice liczą się na równi z opublikowanymi: „Kącik pedagoga” czeka jako
 * szkic na potwierdzenie treści (PLAN.md, Etap 9.4), a pedagog ma go właśnie
 * redagować. Odpada wyłącznie kosz.
 *
 * Wynik zapamiętany na czas żądania: `map_meta_cap` chodzi przy każdym
 * sprawdzeniu uprawnienia, na liście stron kilkadziesiąt razy.
 *
 * @param string $slug Slug strony.
 * @return int Zero, jeśli strony nie ma.
 */
function przedszkole_id_strony( $slug ) {
	static $znane = array();

	if ( isset( $znane[ $slug ] ) ) {
		return $znane[ $slug ];
	}

	global $wpdb;

	$znane[ $slug ] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_name = %s AND post_type = 'page'
			AND post_status IN ( 'publish', 'draft', 'pending', 'private' )
			LIMIT 1",
			$slug
		)
	);

	return $znane[ $slug ];
}

/**
 * ID strony przypisanej roli własnej.
 *
 * @param string $nazwa_roli Nazwa roli.
 * @return int Zero, jeśli roli albo strony nie ma.
 */
function przedszkole_strona_roli( $nazwa_roli ) {
	$role = przedszkole_role_wlasne();

	if ( ! isset( $role[ $nazwa_roli ]['strona'] ) ) {
		return 0;
	}

	return przedszkole_id_strony( $role[ $nazwa_roli ]['strona'] );
}

/**
 * ID kategorii wymuszanej na wpisach roli.
 *
 * @param string $nazwa_roli Nazwa roli.
 * @return int Zero, jeśli rola nie pisze wpisów albo kategorii nie ma.
 */
function przedszkole_kategoria_roli( $nazwa_roli ) {
	$role = przedszkole_role_wlasne();

	if ( empty( $role[ $nazwa_roli ]['kategoria'] ) ) {
		return 0;
	}

	$kategoria = get_category_by_slug( $role[ $nazwa_roli ]['kategoria'] );

	return $kategoria ? (int) $kategoria->term_id : 0;
}

/**
 * Bramka uprawnień ról własnych.
 *
 * `map_meta_cap` zamienia pytanie o konkretną treść („czy może edytować wpis
 * 45”) na listę uprawnień, które konto musi mieć. Zwracamy własną listę:
 * `edit_pages` (rola ma) dla swojej strony, `do_not_allow` (nie ma nikt,
 * łącznie z administratorem) dla wszystkiego innego. Dlatego nie ma znaczenia,
 * kto jest autorem strony ani w jakim jest statusie.
 *
 * Wpisy przepuszczamy bez zmian — tam rozstrzyga rdzeń po autorze, dokładnie
 * tak jak przy natywnej roli Author. Rola bez uprawnień do wpisów (intendent)
 * i tak niczego nie dostanie.
 *
 * Załączniki osobno: specjalista wgrywa pliki, a podpis, tytuł i tekst
 * alternatywny to już edycja załącznika. Bez tego wyjątku plik ląduje
 * w mediach bez opisu, czego nie da się poprawić — wprost przeciwko
 * dostępności. Własny plik może też skasować (wgrał nie ten). Cudzych nie tyka.
 *
 * @param string[] $uprawnienia    Uprawnienia wyliczone dotąd.
 * @param string   $sprawdzane     Sprawdzane uprawnienie meta.
 * @param int      $id_uzytkownika ID konta.
 * @param array    $argumenty      Argumenty sprawdzenia; `[0]` to ID treści.
 * @return string[]
 */
function przedszkole_bramka_rol( $uprawnienia, $sprawdzane, $id_uzytkownika, $argumenty ) {
	$meta_tresci = array( 'edit_post', 'delete_post', 'publish_post', 'edit_page', 'delete_page' );

	if ( ! in_array( $sprawdzane, $meta_tresci, true ) ) {
		return $uprawnienia;
	}

	$nazwa_roli = przedszkole_rola_wlasna( $id_uzytkownika );

	if ( '' === $nazwa_roli ) {
		return $uprawnienia;
	}

	$tresc = isset( $argumenty[0] ) ? get_post( (int) $argumenty[0] ) : null;

	if ( ! $tresc ) {
		return array( 'do_not_allow' );
	}

	if ( 'post' === $tresc->post_type ) {
		return $uprawnienia;
	}

	if ( 'attachment' === $tresc->post_type ) {
		return (int) $tresc->post_author === (int) $id_uzytkownika
			? array( 'upload_files' )
			: array( 'do_not_allow' );
	}

	// Usuwanie stron odcięte zawsze. Strona specjalisty jest w menu i wpiętej
	// w nie treści — skasowana zostawia po sobie błąd 404 w nawigacji.
	if ( in_array( $sprawdzane, array( 'delete_post', 'delete_page' ), true ) ) {
		return array( 'do_not_allow' );
	}

	return (int) $tresc->ID === przedszkole_strona_roli( $nazwa_roli )
		? array( 'edit_pages' )
		: array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'przedszkole_bramka_rol', 10, 4 );

/**
 * Zakładanie stron na własnym uprawnieniu.
 *
 * WordPress domyślnie wyprowadza „może dodać stronę” z `edit_pages` — tego
 * samego uprawnienia, które otwiera edytor istniejącej strony. Specjalista
 * musi mieć drugie i nie może mieć pierwszego, więc rozdzielamy je na poziomie
 * typu treści: `create_posts` dostaje własną nazwę `create_pages`, przyznaną
 * administratorowi i redaktorowi w {@see przedszkole_zaloz_role()}.
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
 * Listy w panelu zawężone do tego, co specjalista może tknąć.
 *
 * Strony: wyłącznie własna. Media: wyłącznie własne pliki — w bibliotece są
 * zdjęcia dzieci z wszystkich grup i nie ma powodu, żeby kuchnia je
 * przeglądała. Wpisów nie ruszamy: rdzeń sam zawęża listę do własnych, gdy
 * konto nie ma `edit_others_posts`.
 *
 * Tylko panel i REST. Na froncie `get_pages()` chodzi przez `WP_Query`, więc
 * ten sam filtr wyciąłby zalogowanemu specjaliście połowę nawigacji.
 *
 * @param WP_Query $zapytanie Modyfikowane zapytanie.
 */
function przedszkole_listy_rol( $zapytanie ) {
	if ( ! is_admin() && ! wp_is_serving_rest_request() ) {
		return;
	}

	$nazwa_roli = przedszkole_rola_wlasna();

	if ( '' === $nazwa_roli ) {
		return;
	}

	$typ = (array) $zapytanie->get( 'post_type' );

	if ( in_array( 'page', $typ, true ) ) {
		$zapytanie->set( 'post__in', array( przedszkole_strona_roli( $nazwa_roli ) ) );
	}

	if ( in_array( 'attachment', $typ, true ) ) {
		$zapytanie->set( 'author', get_current_user_id() );
	}
}
add_action( 'pre_get_posts', 'przedszkole_listy_rol' );

/**
 * Skrót do własnej strony w menu „Strony”.
 *
 * Wygoda i obejście rdzenia naraz — drugie ważniejsze, więc od niego.
 *
 * Specjalista ma `edit_pages`, ale nie ma `create_pages`, więc z podmenu
 * „Strony” zostaje jedna pozycja: „Wszystkie strony”. Adres tej pozycji jest
 * identyczny z adresem menu nadrzędnego, a rdzeń w takim przypadku kasuje całe
 * podmenu (`wp-admin/includes/menu.php`, „If there is only one submenu and it
 * has same destination as the parent”). Bez podmenu `get_admin_page_parent()`
 * nie ma czego dopasować i zwraca pustego rodzica, a wtedy
 * `user_can_access_admin_page()` sprawdza samo `edit.php` — które siedzi
 * w `$_wp_menu_nopriv` jako niedostępne menu „Wpisy”. Efekt: lista stron
 * odpowiada 403 komuś, kto ma do niej prawo.
 *
 * Druga pozycja w podmenu zdejmuje warunek `count() === 1` i rdzeń zostawia
 * podmenu w spokoju. Wybór padł na skrót wprost do własnej strony, bo to
 * jedyna strona na liście — specjalista wchodzi w treść jednym kliknięciem
 * zamiast dwóch, a nawigacja edytora (powrót do listy) działa jak wszędzie.
 *
 * Slug z `.php` w środku WordPress traktuje jak zwykły odnośnik, nie jak
 * podstronę wtyczki — tak samo jak własne „edit.php?post_type=page”.
 */
function przedszkole_menu_rol() {
	$nazwa_roli = przedszkole_rola_wlasna();

	if ( '' === $nazwa_roli ) {
		return;
	}

	$id = przedszkole_strona_roli( $nazwa_roli );

	if ( ! $id ) {
		return;
	}

	$tytul = get_the_title( $id );

	add_submenu_page(
		'edit.php?post_type=page',
		$tytul,
		$tytul,
		'edit_pages',
		'post.php?post=' . $id . '&action=edit'
	);
}
add_action( 'admin_menu', 'przedszkole_menu_rol' );

/**
 * Kategoria wpisu wymuszona na sztywno.
 *
 * Rdzeń nie ma pojęcia „autor tylko w swojej kategorii”: uprawnienie
 * `assign_terms` obowiązuje dla całej taksonomii, nie dla pojedynczego terminu.
 * Zamiast pilnować wyboru, po prostu go nadpisujemy — pedagog pisze poradnik,
 * a kategoria dokleja się sama.
 *
 * Decyduje rola **osoby zapisującej**, nie autora wpisu: dyrekcja poprawiająca
 * cudzy tekst ma nadal móc go przenieść gdzie indziej.
 *
 * Dwa haki, bo dwie drogi zapisu:
 *
 * - `rest_after_insert_post` — edytor blokowy. Musi być *after*: kontroler REST
 *   ustawia terminy dopiero po `wp_insert_post()`, więc podpięcie się wcześniej
 *   zostałoby nadpisane przez to, co przysłała przeglądarka.
 * - `save_post_post` — klasyczny edytor, wp-cli, import. Tutaj terminy są już
 *   ustawione, kiedy hak się odpala.
 *
 * @param WP_Post $wpis Zapisywany wpis.
 */
function przedszkole_wymus_kategorie( $wpis ) {
	if ( ! $wpis instanceof WP_Post || 'post' !== $wpis->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $wpis->ID ) || wp_is_post_autosave( $wpis->ID ) ) {
		return;
	}

	$id_kategorii = przedszkole_kategoria_roli( przedszkole_rola_wlasna() );

	if ( ! $id_kategorii ) {
		return;
	}

	wp_set_post_categories( $wpis->ID, array( $id_kategorii ), false );
}
add_action( 'rest_after_insert_post', 'przedszkole_wymus_kategorie' );

/**
 * To samo przy zapisie spoza REST — `save_post_post` podaje najpierw ID.
 *
 * @param int     $id   ID wpisu.
 * @param WP_Post $wpis Zapisywany wpis.
 */
function przedszkole_wymus_kategorie_przy_zapisie( $id, $wpis ) {
	unset( $id );
	przedszkole_wymus_kategorie( $wpis );
}
add_action( 'save_post_post', 'przedszkole_wymus_kategorie_przy_zapisie', 10, 2 );

/**
 * Wybór kategorii schowany, skoro i tak nic nie znaczy.
 *
 * Panel, który po zapisie cofa wybór użytkownika, jest gorszy niż jego brak.
 * W edytorze blokowym widocznością taksonomii steruje `show_ui` w odpowiedzi
 * REST, w klasycznym — metaboks.
 *
 * @param WP_REST_Response $odpowiedz  Odpowiedź z opisem taksonomii.
 * @param WP_Taxonomy      $taksonomia Opisywana taksonomia.
 * @return WP_REST_Response
 */
function przedszkole_ukryj_kategorie( $odpowiedz, $taksonomia ) {
	if ( 'category' !== $taksonomia->name ) {
		return $odpowiedz;
	}

	if ( ! przedszkole_kategoria_roli( przedszkole_rola_wlasna() ) ) {
		return $odpowiedz;
	}

	if ( isset( $odpowiedz->data['visibility'] ) ) {
		$odpowiedz->data['visibility']['show_ui'] = false;
	}

	return $odpowiedz;
}
add_filter( 'rest_prepare_taxonomy', 'przedszkole_ukryj_kategorie', 10, 2 );

/**
 * Metaboks kategorii w klasycznym edytorze — jak wyżej.
 */
function przedszkole_ukryj_metaboks_kategorii() {
	if ( ! przedszkole_kategoria_roli( przedszkole_rola_wlasna() ) ) {
		return;
	}

	remove_meta_box( 'categorydiv', 'post', 'side' );
}
add_action( 'admin_menu', 'przedszkole_ukryj_metaboks_kategorii' );
