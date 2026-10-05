<?php
/**
 * Porządki w panelu administracyjnym.
 *
 * Etap 6. Ról nie dodajemy — natywne Editor i Author pokrywają potrzeby
 * przedszkola. Zostaje odjęcie z panelu tego, czego personel nie używa,
 * żeby dyrektor i nauczyciel widzieli wyłącznie swoją pracę.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widgety kokpitu.
 *
 * „Wydarzenia i nowości WordPressa” odpytuje api.wordpress.org przy każdym
 * wejściu na kokpit — łącznie z geolokalizacją pod listę meetupów. Strona
 * nie wykonuje zapytań na zewnątrz, więc widget odpada niezależnie od roli.
 *
 * „Szybki szkic” tworzy wpisy bez tytułu, kategorii i zdjęcia, lądujące
 * w szkicach, o których nikt nie pamięta. Zwykłe „Dodaj wpis” robi to samo,
 * tylko kompletnie.
 */
function przedszkole_widgety_kokpitu() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}
add_action( 'wp_dashboard_setup', 'przedszkole_widgety_kokpitu' );

/**
 * Pozycje menu panelu.
 *
 * Priorytet 999 — po wtyczkach, inaczej usuwalibyśmy pozycje, zanim powstaną.
 */
function przedszkole_menu_panelu() {
	// Komentarze są zamknięte globalnie i na każdej treści (Etap 4), a strona
	// przedszkola ich nie przewiduje. Pusty ekran mylił redaktorów.
	remove_menu_page( 'edit-comments.php' );

	// „Narzędzia” bez uprawnień administratora to pusta strona: import, eksport,
	// kondycja i dane osobowe wymagają uprawnień, których Editor i Author
	// nie mają. Sprawdzamy uprawnienie, nie nazwę roli — gdyby doszła kolejna,
	// zadziała bez poprawek.
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'tools.php' );
	}
}
add_action( 'admin_menu', 'przedszkole_menu_panelu', 999 );

/**
 * Górny pasek administratora.
 *
 * Logo „W” prowadzi wyłącznie na wordpress.org — dokumentacja i fora po
 * angielsku, bez pożytku dla personelu. Dymek komentarzy nie ma czego liczyć.
 *
 * @param WP_Admin_Bar $pasek Obiekt paska.
 */
function przedszkole_pasek_admina( $pasek ) {
	$pasek->remove_node( 'wp-logo' );
	$pasek->remove_node( 'comments' );
}
add_action( 'admin_bar_menu', 'przedszkole_pasek_admina', 999 );

/**
 * Podpowiedź nad treścią strony głównej.
 *
 * Treść tej strony to ogłoszenie tymczasowe nad aktualnościami, a pusta
 * ukrywa sekcję ({@see front-page.php}). Z samego edytora tego nie widać —
 * redaktor zobaczyłby zwykłą stronę i albo bał się ją wyczyścić, albo wpisał
 * tam powitanie, które i tak stoi już w motywie.
 *
 * Komunikat rdzenia (`core/notices`), nie własny panel: wygląda jak każda inna
 * informacja w edytorze i nie wymaga kodu bloku. Bez przycisku zamknięcia —
 * ma być widoczny przy każdej edycji, nie tylko przy pierwszej.
 */
function przedszkole_podpowiedz_strony_glownej() {
	$strona = get_post();

	if ( ! $strona || (int) get_option( 'page_on_front' ) !== $strona->ID ) {
		return;
	}

	$tekst = __( 'Treść tej strony wyświetla się na stronie głównej jako ogłoszenie „Ważne informacje”, nad aktualnościami. Gdy treść jest pusta, sekcja się nie pokazuje. Powitanie u góry strony jest stałe i nie zależy od tej treści.', 'przedszkole' );

	wp_add_inline_script(
		'wp-notices',
		sprintf(
			'wp.data.dispatch("core/notices").createNotice("info",%s,{id:"przedszkole-strona-glowna",isDismissible:false});',
			wp_json_encode( $tekst )
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'przedszkole_podpowiedz_strony_glownej' );
