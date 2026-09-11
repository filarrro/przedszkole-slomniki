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
