<?php
/**
 * Utwardzenie instalacji.
 *
 * Etap 8. Strona placówki publicznej bez sklepu, płatności i kont rodziców —
 * realne zagrożenie to boty, nie napastnik z celem. Trzy rzeczy, które boty
 * robią masowo: zgadują hasła na `wp-login.php`, zbierają nazwy użytkowników
 * i szukają dziur we wtyczkach. Na wszystkie trzy odpowiadamy tutaj.
 *
 * Czego tu nie ma i dlaczego: zapory aplikacyjnej (WAF), skanera plików,
 * ukrywania adresu panelu. Pierwsze dwa to wtyczki, które kosztują więcej niż
 * dają przy pięciu kontach i braku danych osobowych na stronie. Trzecie
 * to zabezpieczenie przez zaciemnienie — bot i tak trafi w `wp-login.php`,
 * a pracownik zgubi adres logowania.
 *
 * Reszta utwardzenia jest poza kodem motywu — uprawnienia plików, blokada PHP
 * w katalogu uploadów, nagłówki serwera. Opisana w PLAN.md, Etap 9.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wyłączenie edytora plików w panelu.
 *
 * Bez tego każdy z uprawnieniem administratora — albo ktoś, kto przejął takie
 * konto — może przeglądarką dopisać dowolny kod PHP do motywu. Wdrożenie robimy
 * przez FTP (Etap 9), więc edytor w panelu nie jest do niczego potrzebny.
 *
 * Kanonicznym miejscem na tę stałą jest `wp-config.php`, ale ten plik jest poza
 * repozytorium (dane dostępowe do bazy) i przy wdrożeniu powstaje na serwerze
 * od nowa. W motywie stała jedzie razem z kodem i nie da się jej zgubić.
 * WordPress sprawdza ją dopiero przy budowaniu menu panelu i w `map_meta_cap`,
 * czyli długo po wczytaniu `functions.php`.
 */
defined( 'DISALLOW_FILE_EDIT' ) || define( 'DISALLOW_FILE_EDIT', true );

/**
 * Wyłączenie haseł aplikacji.
 *
 * Hasła aplikacji to druga, równoległa droga logowania do REST API — pomyślana
 * pod integracje zewnętrzne. Strona nie ma żadnej: treść powstaje w przeglądarce,
 * nic nie łączy się z WordPressem z zewnątrz. Nieużywany sposób uwierzytelnienia
 * to samo ryzyko bez pożytku.
 */
add_filter( 'wp_is_application_passwords_available', '__return_false' );

/**
 * Lista kont w REST API tylko dla zalogowanych.
 *
 * `/wp-json/wp/v2/users` oddaje anonimowo nazwy użytkowników wszystkich autorów.
 * To połowa pary potrzebnej do zgadywania hasła — bot dostaje login na tacy
 * i zostaje mu jedno pole do zgadnięcia. Edytor Gutenberga korzysta z tego
 * punktu (pole „Autor”), ale zawsze na zalogowanej sesji.
 *
 * @param array $trasy Zarejestrowane trasy REST.
 * @return array
 */
function przedszkole_rest_bez_listy_kont( $trasy ) {
	if ( is_user_logged_in() ) {
		return $trasy;
	}

	unset( $trasy['/wp/v2/users'] );
	unset( $trasy['/wp/v2/users/(?P<id>[\d]+)'] );

	return $trasy;
}
add_filter( 'rest_endpoints', 'przedszkole_rest_bez_listy_kont' );

/**
 * Archiwa autorów przekierowane na stronę główną.
 *
 * Dwa powody naraz. Bezpieczeństwo: `/?author=1` przekierowuje na
 * `/author/<login>/` i tym samym zdradza nazwę konta — klasyczny sposób
 * zbierania loginów. Treść: autorem bywa konto grupowe („Żabki”), a wpisy tej
 * grupy są już pod adresem kategorii, więc archiwum autora to druga lista tego
 * samego pod innym adresem — dla wyszukiwarki duplikat, dla rodzica nadmiar.
 *
 * Przekierowanie, nie 404: adres mógł gdzieś trafić, a strona główna jest
 * użyteczniejsza niż komunikat o błędzie.
 *
 * Priorytet 1, przed `redirect_canonical` (10). Przy domyślnym priorytecie
 * rdzeń zdążyłby najpierw przekierować `/?author=1` na `/author/<login>/`
 * i login wyciekłby nagłówkiem `Location` — czyli dokładnie to, czemu
 * zapobiegamy.
 */
function przedszkole_bez_archiwum_autora() {
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'przedszkole_bez_archiwum_autora', 1 );

/**
 * Komunikat błędu logowania bez podpowiedzi.
 *
 * WordPress rozróżnia „nieznany użytkownik” i „błędne hasło”. Dla bota to
 * informacja, czy warto dalej zgadywać hasło do tego loginu. Pracownikowi,
 * który pomylił jedno albo drugie, i tak zostaje ta sama czynność: spróbować
 * jeszcze raz albo odzyskać hasło.
 *
 * Podmieniamy wyłącznie kody dotyczące danych logowania, a nie wszystko, co
 * przyjdzie. Ogólny filtr `login_errors` zastąpiłby także komunikat o blokadzie
 * po pięciu próbach — pracownik zobaczyłby „nieprawidłowe hasło” i wpisywał
 * kolejne, zamiast dowiedzieć się, że ma odczekać kwadrans.
 *
 * @param WP_Error $bledy Błędy zebrane przez formularz logowania.
 * @return WP_Error
 */
function przedszkole_blad_logowania( $bledy ) {
	$mylace   = array( 'invalid_username', 'invalid_email', 'incorrect_password' );
	$trafione = array_intersect( $mylace, $bledy->get_error_codes() );

	if ( ! $trafione ) {
		return $bledy;
	}

	foreach ( $trafione as $kod ) {
		$bledy->remove( $kod );
	}

	$bledy->add( 'przedszkole_zle_dane', esc_html__( 'Nieprawidłowa nazwa użytkownika lub hasło.', 'przedszkole' ) );

	return $bledy;
}
add_filter( 'wp_login_errors', 'przedszkole_blad_logowania' );

/**
 * Klucz licznika nieudanych logowań dla bieżącego adresu IP.
 *
 * Bierzemy wyłącznie `REMOTE_ADDR` — adres, z którego połączenie faktycznie
 * przyszło do Apache'a. Nagłówków `X-Forwarded-For` świadomie nie czytamy:
 * nadaje je klient, więc bot ustawiłby sobie nowy przy każdej próbie i licznik
 * nigdy by nie doszedł do limitu. Cena tego wyboru: gdyby przed serwerem stanął
 * kiedyś CDN albo proxy, wszystkie próby zliczą się jako jeden adres i limit
 * zablokuje logowanie wszystkim naraz. Wtedy — i tylko wtedy — ten filtr
 * wymaga poprawki.
 *
 * IP hashujemy, bo nazwa opcji przechodzącej do bazy nie musi być adresem
 * odwiedzającego (RODO), a `md5` daje stałą, krótką długość klucza.
 *
 * @return string
 */
function przedszkole_klucz_logowania() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'brak';

	return 'przedszkole_logowanie_' . md5( $ip );
}

/**
 * Zliczenie nieudanej próby logowania.
 *
 * W liczniku trzymamy parę: liczbę prób i moment wygaśnięcia okna. Sam licznik
 * nie wystarcza, bo `set_transient` na istniejącym kluczu odmierza czas od nowa —
 * przy ciągłym ostrzale kwadrans nigdy by nie minął, a blokada zostałaby na
 * zawsze. Zapamiętany termin pozwala przy każdej kolejnej próbie podać czas,
 * jaki naprawdę został.
 */
function przedszkole_licz_nieudane_logowanie() {
	$klucz = przedszkole_klucz_logowania();
	$stan  = get_transient( $klucz );

	if ( is_array( $stan ) && isset( $stan['proby'], $stan['do'] ) ) {
		$pozostalo = (int) $stan['do'] - time();

		if ( $pozostalo > 0 ) {
			set_transient(
				$klucz,
				array(
					'proby' => (int) $stan['proby'] + 1,
					'do'    => (int) $stan['do'],
				),
				$pozostalo
			);

			return;
		}
	}

	set_transient(
		$klucz,
		array(
			'proby' => 1,
			'do'    => time() + 15 * MINUTE_IN_SECONDS,
		),
		15 * MINUTE_IN_SECONDS
	);
}
add_action( 'wp_login_failed', 'przedszkole_licz_nieudane_logowanie' );

/**
 * Wyczyszczenie licznika po udanym logowaniu.
 */
function przedszkole_zeruj_licznik_logowania() {
	delete_transient( przedszkole_klucz_logowania() );
}
add_action( 'wp_login', 'przedszkole_zeruj_licznik_logowania' );

/**
 * Odcięcie logowania po pięciu nieudanych próbach z jednego adresu.
 *
 * Pięć prób w kwadrans to znacznie więcej, niż potrzebuje człowiek, który
 * pomylił hasło, i znacznie mniej, niż potrzebuje bot. Nie jest to zapora:
 * atak rozproszony po tysiącu adresów przejdzie przez to bez przeszkód.
 * Jest to odpowiedź na to, co dzieje się naprawdę — jeden skrypt waląc
 * słownikiem w `wp-login.php` całą dobę.
 *
 * Świadomie bez wtyczki (Limit Login Attempts Reloaded i podobne): kilkadziesiąt
 * linijek bez ekranu ustawień, własnej tabeli w bazie i cyklu aktualizacji przez
 * najbliższe lata. Gdyby okazało się to za słabe, wtyczka zastąpi ten plik bez
 * dotykania reszty motywu.
 *
 * Priorytet 30 — po `wp_authenticate_username_password` (20), które sprawdza
 * hasło. Odcinamy dopiero to, co i tak byłoby odrzucone, a nie sesję, którą
 * WordPress właśnie przyjął.
 *
 * @param WP_User|WP_Error|null $uzytkownik Wynik dotychczasowego uwierzytelnienia.
 * @return WP_User|WP_Error|null
 */
function przedszkole_limit_logowania( $uzytkownik ) {
	if ( $uzytkownik instanceof WP_User ) {
		return $uzytkownik;
	}

	$stan = get_transient( przedszkole_klucz_logowania() );

	if ( ! is_array( $stan ) || (int) ( $stan['proby'] ?? 0 ) < 5 ) {
		return $uzytkownik;
	}

	return new WP_Error(
		'przedszkole_za_duzo_prob',
		esc_html__( 'Za dużo nieudanych prób logowania. Spróbuj ponownie za kwadrans.', 'przedszkole' )
	);
}
add_filter( 'authenticate', 'przedszkole_limit_logowania', 30 );

/**
 * Awatary wyłączone.
 *
 * Awatar to obrazek z serwera Gravatara, pobierany po zahaszowanym adresie
 * e-mail. Na froncie żadnego nie pokazujemy (Etap 7), ale panel owszem —
 * na liście wpisów i w profilu. Każde wejście na listę wysyłałoby więc adresy
 * pracowników do serwisu zewnętrznego, a strona z założenia nie odpytuje nikogo
 * na zewnątrz.
 *
 * Przez `pre_option_`, a nie przez zmianę opcji w bazie: ustawienie jedzie
 * z kodem, a nie z konfiguracją, której nikt po latach nie odtworzy.
 *
 * @return string
 */
function przedszkole_bez_awatarow() {
	return '0';
}
add_filter( 'pre_option_show_avatars', 'przedszkole_bez_awatarow' );
