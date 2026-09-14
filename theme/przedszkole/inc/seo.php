<?php
/**
 * SEO: opis strony, podgląd odnośnika, dane strukturalne.
 *
 * Etap 8. Bez wtyczki SEO — nie dlatego, że wtyczki są złe, tylko dlatego,
 * że po odjęciu tego, co WordPress robi sam, zostają trzy znaczniki w nagłówku.
 *
 * Rdzeń daje już: `<title>` (`add_theme_support( 'title-tag' )`), `rel=canonical`,
 * `robots` z `noindex` na wynikach wyszukiwania, mapę witryny `wp-sitemap.xml`
 * i `robots.txt` z odnośnikiem do niej. Zweryfikowane w wygenerowanym HTML-u,
 * nie założone. Brakuje wyłącznie opisu meta, znaczników Open Graph i danych
 * strukturalnych — i to jest zawartość tego pliku.
 *
 * Koszt wtyczki (SEOPress, Yoast, Slim SEO) to ekran ustawień, własne tabele,
 * pola w edytorze, cykl aktualizacji i pola do wypełnienia przy każdej nowej
 * podstronie. Korzyść — możliwość ręcznego nadpisania opisu — jest dla strony
 * przedszkola pozorna: nikt nie będzie tego robił. Opis wyliczamy z treści,
 * a pracownik może go nadpisać polem „Fragment” w edytorze, które jest natywne.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Opis strony dla wyszukiwarki i podglądu odnośnika.
 *
 * Kolejność źródeł: ręczny „Fragment” → treść strony → opis kategorii →
 * opis witryny. Wynik zawsze przechodzi przez skracanie, bo pracownik może
 * wpisać we „Fragmencie” akapit.
 *
 * Używamy `get_queried_object()`, a nie globalnego `$post` — w `wp_head`
 * pętla jeszcze się nie zaczęła, a na stronie głównej motyw uruchamia ją
 * dopiero w sekcji powitalnej.
 *
 * @return string Opis albo pusty łańcuch, gdy nie ma czego opisać.
 */
function przedszkole_opis_strony() {
	$obiekt = get_queried_object();

	if ( $obiekt instanceof WP_Post ) {
		$opis = has_excerpt( $obiekt ) ? $obiekt->post_excerpt : przedszkole_tresc_na_opis( $obiekt );

		if ( '' !== trim( (string) $opis ) ) {
			return przedszkole_skroc_opis( $opis );
		}
	}

	if ( $obiekt instanceof WP_Term && '' !== trim( (string) $obiekt->description ) ) {
		return przedszkole_skroc_opis( $obiekt->description );
	}

	if ( $obiekt instanceof WP_Term ) {
		return przedszkole_skroc_opis(
			sprintf(
				/* translators: %1$s: nazwa kategorii, %2$s: nazwa witryny. */
				__( 'Aktualności z kategorii %1$s — %2$s.', 'przedszkole' ),
				$obiekt->name,
				get_bloginfo( 'name' )
			)
		);
	}

	$opis_witryny = get_bloginfo( 'description', 'display' );

	return $opis_witryny ? przedszkole_skroc_opis( get_bloginfo( 'name' ) . ' ' . $opis_witryny ) : '';
}

/**
 * Obrazek do podglądu odnośnika.
 *
 * Zdjęcie wyróżniające wpisu, a gdy go nie ma — ilustracja ze strony głównej.
 * Wielkość `large` (1024 px) mieści się w wymaganiach Facebooka i Twittera,
 * a waży ułamek pełnego zdjęcia.
 *
 * @return array{url: string, szerokosc: int, wysokosc: int, opis: string}
 */
function przedszkole_obrazek_podgladu() {
	$obiekt = get_queried_object();

	if ( $obiekt instanceof WP_Post && has_post_thumbnail( $obiekt ) ) {
		$id  = get_post_thumbnail_id( $obiekt );
		$dane = wp_get_attachment_image_src( $id, 'large' );

		if ( $dane ) {
			return array(
				'url'       => $dane[0],
				'szerokosc' => (int) $dane[1],
				'wysokosc'  => (int) $dane[2],
				'opis'      => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}
	}

	return array(
		'url'       => get_theme_file_uri( 'assets/img/hero.webp' ),
		'szerokosc' => 1400,
		'wysokosc'  => 931,
		'opis'      => __( 'Dzieci w kręgu, każde z pluszakiem', 'przedszkole' ),
	);
}

/**
 * Zamiana treści strony na jedno ciągłe zdanie.
 *
 * `wp_trim_excerpt()` zrobiłby to samo, ale skleja nagłówek z akapitem bez
 * żadnego znaku między nimi: „Witamy w naszym przedszkolu Jesteśmy miejscem…”.
 * Nagłówki nie kończą się kropką, bo w układzie strony oddziela je odstęp —
 * w jednej linijce opisu tego odstępu nie ma, więc kropkę dokładamy sami.
 *
 * `excerpt_remove_blocks()` wycina wcześniej bloki, które nie są treścią
 * (menu, wyszukiwarka, przyciski) — ta sama funkcja, z której korzysta rdzeń.
 *
 * @param WP_Post $wpis Wpis albo strona.
 * @return string
 */
function przedszkole_tresc_na_opis( $wpis ) {
	$tresc = strip_shortcodes( excerpt_remove_blocks( $wpis->post_content ) );

	$tresc = preg_replace_callback(
		'#<h[1-6][^>]*>(.*?)</h[1-6]>#is',
		static function ( $dopasowanie ) {
			$naglowek = trim( wp_strip_all_tags( $dopasowanie[1] ) );

			if ( '' === $naglowek ) {
				return ' ';
			}

			// Nagłówek zakończony znakiem interpunkcyjnym drugiej kropki nie chce.
			return preg_match( '/[.!?:…]$/u', $naglowek ) ? ' ' . $naglowek . ' ' : ' ' . $naglowek . '. ';
		},
		$tresc
	);

	return wp_strip_all_tags( $tresc );
}

/**
 * Kanoniczny adres bieżącego widoku.
 *
 * Nie sklejamy go z `REQUEST_URI`, bo do adresu trafiłby wtedy każdy doklejony
 * parametr — a `og:url` ma wskazywać jeden adres treści, nie ten, którym rodzic
 * akurat wszedł.
 *
 * @return string
 */
function przedszkole_adres_strony() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$obiekt = get_queried_object();

	if ( $obiekt instanceof WP_Post ) {
		return (string) get_permalink( $obiekt );
	}

	if ( $obiekt instanceof WP_Term ) {
		$adres = get_term_link( $obiekt );

		return is_wp_error( $adres ) ? home_url( '/' ) : $adres;
	}

	return home_url( '/' );
}

/**
 * Znaczniki nagłówka: opis meta i Open Graph.
 *
 * Open Graph czytają Facebook, Messenger i komunikatory — czyli droga, którą
 * rodzice najczęściej podają sobie odnośniki. Bez niego podgląd wpisu to goły
 * adres. Twitterowi wystarczy jedna linijka, resztę dobiera z Open Graph.
 *
 * Na wynikach wyszukiwania i błędzie 404 nie wypisujemy nic — WordPress
 * oznacza je `noindex`, więc opis nie ma komu się przydać.
 */
function przedszkole_znaczniki_head() {
	if ( is_search() || is_404() ) {
		return;
	}

	$opis    = przedszkole_opis_strony();
	$obrazek = przedszkole_obrazek_podgladu();
	$tytul   = wp_get_document_title();
	$adres   = przedszkole_adres_strony();

	if ( $opis ) {
		printf( "\n<meta name=\"description\" content=\"%s\">\n", esc_attr( $opis ) );
	}

	printf( "<meta property=\"og:type\" content=\"%s\">\n", is_singular( 'post' ) ? 'article' : 'website' );
	printf( "<meta property=\"og:title\" content=\"%s\">\n", esc_attr( $tytul ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $adres ) );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( "<meta property=\"og:locale\" content=\"%s\">\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );

	if ( $opis ) {
		printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $opis ) );
	}

	printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $obrazek['url'] ) );
	printf( "<meta property=\"og:image:width\" content=\"%d\">\n", (int) $obrazek['szerokosc'] );
	printf( "<meta property=\"og:image:height\" content=\"%d\">\n", (int) $obrazek['wysokosc'] );

	if ( $obrazek['opis'] ) {
		printf( "<meta property=\"og:image:alt\" content=\"%s\">\n", esc_attr( $obrazek['opis'] ) );
	}

	if ( is_singular( 'post' ) ) {
		printf( "<meta property=\"article:published_time\" content=\"%s\">\n", esc_attr( get_the_date( 'c' ) ) );
		printf( "<meta property=\"article:modified_time\" content=\"%s\">\n", esc_attr( get_the_modified_date( 'c' ) ) );
	}

	print( "<meta name=\"twitter:card\" content=\"summary_large_image\">\n" );
}
add_action( 'wp_head', 'przedszkole_znaczniki_head', 2 );

/**
 * Dane strukturalne: placówka na stronie głównej, wpis na aktualności.
 *
 * Dwa typy, bo tylko te dwa coś zmieniają. `Preschool` pozwala Google pokazać
 * wizytówkę z adresem i telefonem przy wyszukaniu „przedszkole Słomniki”.
 * `BlogPosting` podpina wpisowi datę i zdjęcie.
 *
 * Typ podwójny `["Preschool", "LocalBusiness"]`: `Preschool` dziedziczy po
 * `EducationalOrganization`, w którym nie ma godzin otwarcia ani współrzędnych —
 * te należą do `LocalBusiness`. JSON-LD dopuszcza wiele typów naraz i Google
 * to rozumie; bez tego trzeba by wybrać między „to jest przedszkole”
 * a „to jest miejsce, które ma adres i godziny”.
 *
 * Ścieżek okruszkowych (`BreadcrumbList`) świadomie nie ma: widoczna ścieżka
 * jest tylko na podstronach drugiego poziomu, a Google i tak buduje ją
 * z adresu URL, który mamy hierarchiczny.
 */
function przedszkole_dane_strukturalne() {
	$dane = array();

	if ( is_front_page() ) {
		$placowka = przedszkole_dane_placowki();

		$dane = array(
			'@context'  => 'https://schema.org',
			'@type'     => array( 'Preschool', 'LocalBusiness' ),
			'@id'       => home_url( '/#placowka' ),
			'name'      => get_bloginfo( 'name' ),
			'url'       => home_url( '/' ),
			'telephone' => $placowka['telefon'],
			'email'     => $placowka['email'],
			'address'   => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $placowka['ulica'],
				'postalCode'      => $placowka['kod'],
				'addressLocality' => $placowka['miasto'],
				'addressCountry'  => $placowka['kraj'],
			),
			'geo'       => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => $placowka['szerokosc'],
				'longitude' => $placowka['dlugosc'],
			),
			'openingHoursSpecification' => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => $placowka['otwarcie'],
					'closes'    => $placowka['zamkniecie'],
				),
			),
		);

		$logo = get_theme_mod( 'custom_logo' );

		if ( $logo ) {
			$dane['logo'] = wp_get_attachment_image_url( $logo, 'full' );
		}
	}

	if ( is_singular( 'post' ) ) {
		$obrazek = przedszkole_obrazek_podgladu();

		$dane = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'mainEntityOfPage' => get_permalink(),
			'headline'         => przedszkole_skroc_opis( get_the_title(), 110 ),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'image'            => $obrazek['url'],
			'publisher'        => array(
				'@type' => 'Organization',
				'@id'   => home_url( '/#placowka' ),
				'name'  => get_bloginfo( 'name' ),
			),
		);

		$opis = przedszkole_opis_strony();

		if ( $opis ) {
			$dane['description'] = $opis;
		}
	}

	if ( ! $dane ) {
		return;
	}

	/*
	 * `wp_json_encode` z `JSON_UNESCAPED_UNICODE` — bez tego polskie znaki
	 * lądują jako `ł` i plik staje się nieczytelny przy podglądzie źródła.
	 * `JSON_UNESCAPED_SLASHES` robi to samo z adresami URL.
	 */
	printf(
		"\n<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $dane, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
	);
}
add_action( 'wp_head', 'przedszkole_dane_strukturalne', 3 );

/**
 * Mapa witryny bez autorów.
 *
 * `wp-sitemap-users-1.xml` wysyła do Google listę kont z nazwami użytkowników.
 * Archiwa autorów i tak przekierowujemy (patrz `inc/bezpieczenstwo.php`),
 * więc byłaby to mapa adresów, które nie istnieją.
 *
 * @param WP_Sitemaps_Provider $dostawca Dostawca sekcji mapy.
 * @param string               $nazwa    Nazwa sekcji.
 * @return WP_Sitemaps_Provider|false
 */
function przedszkole_mapa_bez_autorow( $dostawca, $nazwa ) {
	return 'users' === $nazwa ? false : $dostawca;
}
add_filter( 'wp_sitemaps_add_provider', 'przedszkole_mapa_bez_autorow', 10, 2 );

/**
 * Kanał RSS komentarzy do usunięcia z nagłówka.
 *
 * Komentarze są zamknięte globalnie i na każdej treści (Etap 4), a pozycja
 * menu „Komentarze” zniknęła z panelu (Etap 6). Odnośnik do kanału, w którym
 * nigdy nic nie będzie, zostaje ostatni.
 *
 * @return bool
 */
function przedszkole_bez_kanalu_komentarzy() {
	return false;
}
add_filter( 'feed_links_show_comments_feed', 'przedszkole_bez_kanalu_komentarzy' );
