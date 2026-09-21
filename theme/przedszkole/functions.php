<?php
/**
 * Konfiguracja motywu Przedszkole.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

define( 'PRZEDSZKOLE_VERSION', '0.31.0' );

require_once get_theme_file_path( 'inc/helpers.php' );
require_once get_theme_file_path( 'inc/panel.php' );
require_once get_theme_file_path( 'inc/seo.php' );
require_once get_theme_file_path( 'inc/bezpieczenstwo.php' );
require_once get_theme_file_path( 'inc/role.php' );
require_once get_theme_file_path( 'inc/rok-szkolny.php' );
require_once get_theme_file_path( 'inc/blok-osoba.php' );

/**
 * Deklaracja możliwości motywu.
 */
function przedszkole_setup() {
	load_theme_textdomain( 'przedszkole', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'automatic-feed-links' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 104,
			'width'       => 500,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Menu główne', 'przedszkole' ),
			'footer'  => __( 'Menu w stopce', 'przedszkole' ),
			'skroty'  => __( 'Na skróty (strona główna)', 'przedszkole' ),
		)
	);

	// Rozmiar miniatury dla kafelków aktualności. 3:2, przycinany.
	add_image_size( 'przedszkole-karta', 640, 427, true );

	// Komentarze wyłączone — panel redakcyjny nie pokazuje pól, których nikt nie używa.
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'post', 'trackbacks' );
	remove_post_type_support( 'page', 'comments' );
	remove_post_type_support( 'page', 'trackbacks' );
}
add_action( 'after_setup_theme', 'przedszkole_setup' );

/**
 * Style i skrypty frontendu.
 */
function przedszkole_assets() {
	wp_enqueue_style(
		'przedszkole-style',
		get_stylesheet_uri(),
		array(),
		PRZEDSZKOLE_VERSION
	);

	wp_enqueue_script(
		'przedszkole-nav',
		get_theme_file_uri( 'assets/js/nav.js' ),
		array(),
		PRZEDSZKOLE_VERSION,
		array( 'strategy' => 'defer' )
	);

	wp_enqueue_script(
		'przedszkole-dostepnosc',
		get_theme_file_uri( 'assets/js/dostepnosc.js' ),
		array(),
		PRZEDSZKOLE_VERSION,
		array( 'strategy' => 'defer' )
	);
}
add_action( 'wp_enqueue_scripts', 'przedszkole_assets' );

/**
 * Preferencje dostepnosci, odczytane przed pierwszym malowaniem.
 *
 * Jedyny skrypt w tym motywie wstawiony w tresci strony, i to swiadomie.
 * Preferencje siedza w `localStorage`, a nie w ciasteczku - serwer ich nie zna,
 * wiec ustawic je moze dopiero przegladarka. Skrypt odroczony zrobilby to
 * po pierwszym malowaniu: strona blysnelaby domyslnym rozmiarem i bialym tlem,
 * a dopiero potem przeskoczyla na wybrane. Przy wysokim kontrascie to nie
 * kosmetyka - blysk bieli w oczy osobie swiatloczulej jest dokladnie tym,
 * przed czym ten tryb ma chronic. Kilkadziesiat znakow w naglowku kosztuje
 * mniej niz to mrugniecie, a zewnetrznego zapytania nie doklada zadnego.
 *
 * Preferencje systemowa (`prefers-contrast: more`) czyta ten sam skrypt, a nie
 * osobna regula w CSS. Dzieki temu caly tryb wisi na jednym atrybucie i CSS ma
 * jeden blok zamiast dwoch kopii. Wybor uzytkownika jest wazniejszy: preferencje
 * systemowa bierzemy pod uwage tylko wtedy, gdy nic nie zapisano.
 *
 * Zapisane „zwykly" jest tak samo wazne jak „wysoki": bez niego osoba, ktora
 * ma wysoki kontrast wlaczony w systemie, nie moglaby go tu wylaczyc.
 *
 * `localStorage`, nie ciasteczko, takze dlatego, ze odpowiedz serwera zostaje
 * bez `Set-Cookie` - na tym opiera sie brak banera zgod (Etap 8.4).
 *
 * Klasa `ma-js` odslania pasek. Bez JavaScriptu przyciski nic by nie robily,
 * a przycisk, ktory nic nie robi, jest gorszy niz jego brak.
 */
function przedszkole_dostepnosc_wczesnie() {
	$przedszkole_skrypt = "(function(){var d=document.documentElement;d.classList.add('ma-js');"
		. "try{var r=localStorage.getItem('przedszkole-rozmiar');"
		. "if('duzy'===r||'bardzo-duzy'===r){d.setAttribute('data-rozmiar',r);}"
		. "var k=localStorage.getItem('przedszkole-kontrast');"
		. "if('wysoki'===k||'zwykly'===k){d.setAttribute('data-kontrast',k);}"
		. "else if(window.matchMedia&&matchMedia('(prefers-contrast: more)').matches)"
		. "{d.setAttribute('data-kontrast','wysoki');}}catch(e){}}());";

	wp_print_inline_script_tag( $przedszkole_skrypt );
}
add_action( 'wp_head', 'przedszkole_dostepnosc_wczesnie', 1 );

/**
 * Wstępne wczytanie kroju pisma.
 *
 * Font startuje równolegle z arkuszem stylów, zamiast czekać na jego
 * sparsowanie. Skraca moment, w którym tekst wyświetla się zastępczym krojem.
 *
 * @param array $resources Zasoby do wstępnego wczytania.
 * @return array
 */
function przedszkole_preload_fontu( $resources ) {
	$resources[] = array(
		'href'        => get_theme_file_uri( 'assets/fonts/nunito-latin-ext.woff2' ),
		'as'          => 'font',
		'type'        => 'font/woff2',
		'crossorigin' => 'anonymous',
	);

	return $resources;
}
add_filter( 'wp_preload_resources', 'przedszkole_preload_fontu' );

/**
 * Style edytora — dzięki temu Gutenberg wygląda jak gotowa strona.
 *
 * Dwa pliki, wbrew zasadzie „jeden arkusz”: `style.css` niesie wygląd,
 * a `editor.css` mapuje selektory frontu na inny układ DOM-u edytora.
 * Kosztu na froncie nie ma — oba wchodzą wyłącznie w panelu.
 */
function przedszkole_editor_assets() {
	add_editor_style( 'style.css' );
	add_editor_style( 'assets/css/editor.css' );
}
add_action( 'after_setup_theme', 'przedszkole_editor_assets' );

/**
 * Własna kategoria wzorców bloków.
 *
 * Same wzorce leżą w katalogu `patterns/` — WordPress rejestruje je sam,
 * nie trzeba ich wyliczać w kodzie.
 */
function przedszkole_kategoria_wzorcow() {
	register_block_pattern_category(
		'przedszkole',
		array( 'label' => __( 'Przedszkole', 'przedszkole' ) )
	);
}
add_action( 'init', 'przedszkole_kategoria_wzorcow' );

/**
 * Warianty stylów dla bloków standardowych.
 *
 * Tańsze niż własne bloki: pracownik wstawia zwykły przycisk czy grupę
 * i wybiera wariant z listy, a my nie utrzymujemy kodu JS.
 */
function przedszkole_style_blokow() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'galeria',
			'label' => __( 'Link do albumu', 'przedszkole' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'wyroznienie',
			'label' => __( 'Wyróżnienie', 'przedszkole' ),
		)
	);

	register_block_style(
		'core/list',
		array(
			'name'  => 'spis-tresci',
			'label' => __( 'Spis treści', 'przedszkole' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'numer-konta',
			'label' => __( 'Numer konta', 'przedszkole' ),
		)
	);
}
add_action( 'init', 'przedszkole_style_blokow' );

/**
 * Odcięcie wzorców pobieranych z wordpress.org.
 *
 * Dwa powody: strona nie odpytuje serwerów zewnętrznych, a lista wzorców
 * w edytorze zostaje krótka i po polsku — pracownik widzi nasze, nie setki cudzych.
 */
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Obszary widgetów w stopce.
 */
function przedszkole_widgets() {
	$defaults = array(
		'before_widget' => '<div class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	);

	register_sidebar(
		array_merge(
			$defaults,
			array(
				'name'        => __( 'Stopka — kontakt', 'przedszkole' ),
				'id'          => 'footer-kontakt',
				'description' => __( 'Adres, telefon, e-mail.', 'przedszkole' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$defaults,
			array(
				'name'        => __( 'Stopka — godziny', 'przedszkole' ),
				'id'          => 'footer-godziny',
				'description' => __( 'Godziny otwarcia przedszkola.', 'przedszkole' ),
			)
		)
	);
}
add_action( 'widgets_init', 'przedszkole_widgets' );

/**
 * Usunięcie rzeczy, których strona przedszkola nie potrzebuje.
 * Każda z nich to zapytanie lub kilka kilobajtów mniej na każdej podstronie.
 */
function przedszkole_cleanup() {
	// Emoji — skrypt i style ładowane na każdej stronie.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	// Odnośniki, z których nie korzystamy.
	remove_action( 'wp_head', 'wp_generator' );              // Ujawnia wersję WordPressa.
	remove_action( 'wp_head', 'wlwmanifest_link' );          // Windows Live Writer.
	remove_action( 'wp_head', 'rsd_link' );                  // Really Simple Discovery.
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );

	/*
	 * oEmbed: dwa odnośniki na każdej podstronie, po których inny WordPress
	 * potrafiłby osadzić naszą treść u siebie. Nikt tego nie robi i nie będzie.
	 */
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
add_action( 'init', 'przedszkole_cleanup' );

/**
 * Wyłączenie XML-RPC. Strona nie korzysta z zewnętrznych klientów,
 * a jest to częsty cel ataków siłowych na hasła.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Komentarze zamknięte po stronie kodu.
 *
 * Przedszkole nie prowadzi dyskusji pod wpisami: nie ma kto ich moderować,
 * a otwarty formularz ściąga spam. W ustawieniach są zamknięte, ale wystarczy
 * jedno kliknięcie w „Dyskusja”, żeby je otworzyć dla nowych wpisów — a motyw
 * nie ma `comments.php`, więc front pokazałby wtedy pustkę zamiast formularza.
 * Filtry przecinają rozjazd: rdzeń widzi komentarze jako zamknięte niezależnie
 * od tego, co stoi we wpisie.
 *
 * Priorytet 20, żeby wejść po ewentualnej wtyczce, nie przed nią.
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 20 );

/**
 * Skrócenie długości zajawki na listach.
 *
 * @param int $length Domyślna liczba słów.
 * @return int
 */
function przedszkole_excerpt_length( $length ) {
	return 24;
}
add_filter( 'excerpt_length', 'przedszkole_excerpt_length' );

/**
 * Zakończenie zajawki wielokropkiem zamiast „[…]”.
 *
 * @param string $more Domyślne zakończenie.
 * @return string
 */
function przedszkole_excerpt_more( $more ) {
	return '…';
}
add_filter( 'excerpt_more', 'przedszkole_excerpt_more' );

/**
 * Zmniejszanie zdjęć wgrywanych z telefonu.
 *
 * Treść ma 1140 px szerokości, więc 2048 px starcza nawet na ekrany o podwójnej
 * gęstości. Aparat w telefonie robi zdjęcia po 4000 px — WordPress przeskaluje
 * je przy wgrywaniu, zamiast trzymać na serwerze wielkość, której nikt nie zobaczy.
 *
 * @return int Próg w pikselach.
 */
function przedszkole_prog_duzego_obrazka() {
	return 2048;
}
add_filter( 'big_image_size_threshold', 'przedszkole_prog_duzego_obrazka' );

/**
 * Przerobienie wgranego zdjęcia na WebP.
 *
 * Konwersja dzieje się od razu przy wgrywaniu, zanim WordPress zabierze się za
 * zmniejszanie i miniatury — dzięki temu w WebP jest każda wielkość, także ta
 * pełna, a nie tylko kopie. Plik w formacie źródłowym znika: to samo zdjęcie
 * w dwóch formatach zajmowałoby dwa razy tyle miejsca bez żadnego pożytku.
 *
 * Jeden filtr obsługuje obie drogi: wgranie z panelu i dołożenie z linii poleceń
 * albo ze skryptu migracyjnego. WordPress puszcza je przez ten sam `wp_handle_upload`,
 * różnicując tylko drugim argumentem, którego tu nie potrzebujemy.
 *
 * GIF-y pomijamy — animacji nie przeniesiemy, a statyczny GIF to dziś rzadkość.
 *
 * @param array $plik Dane wgranego pliku: ścieżka, adres i typ MIME.
 * @return array
 */
function przedszkole_upload_na_webp( $plik ) {
	if ( empty( $plik['type'] ) || ! in_array( $plik['type'], array( 'image/jpeg', 'image/png' ), true ) ) {
		return $plik;
	}

	$edytor = wp_get_image_editor( $plik['file'] );

	if ( is_wp_error( $edytor ) ) {
		return $plik;
	}

	// Nazwa bez kolizji: „kwiaty.jpg” obok istniejącego „kwiaty.webp” dostanie „kwiaty-1.webp”.
	$katalog = dirname( $plik['file'] );
	$nazwa   = wp_unique_filename( $katalog, pathinfo( $plik['file'], PATHINFO_FILENAME ) . '.webp' );

	$wynik = $edytor->save( $katalog . '/' . $nazwa, 'image/webp' );

	if ( is_wp_error( $wynik ) ) {
		return $plik;
	}

	wp_delete_file( $plik['file'] );

	/*
	 * Bez klucza `error` — WordPress sprawdza go przez `isset()`, więc nawet
	 * `false` przerwałoby wgrywanie komunikatem bez treści.
	 */
	return array(
		'file' => $wynik['path'],
		'url'  => dirname( $plik['url'] ) . '/' . $wynik['file'],
		'type' => $wynik['mime-type'],
	);
}

/**
 * Usunięcie nietkniętego oryginału po zmniejszeniu.
 *
 * Zdjęcie większe niż próg WordPress zmniejsza, ale plik sprzed zmniejszenia
 * chowa obok „na wszelki wypadek”. Przy zdjęciach z telefonu to kilka megabajtów
 * na każdą pozycję w bibliotece, po które nikt nigdy nie sięgnie. Kasujemy go
 * razem z wpisem w metadanych — sam wpis bez pliku wskazywałby w pustkę.
 *
 * @param array $meta Metadane załącznika.
 * @param int   $id   ID załącznika.
 * @return array
 */
function przedszkole_bez_oryginalu( $meta, $id ) {
	if ( empty( $meta['original_image'] ) ) {
		return $meta;
	}

	$sciezka = wp_get_original_image_path( $id );

	if ( $sciezka && file_exists( $sciezka ) && $sciezka !== get_attached_file( $id ) ) {
		wp_delete_file( $sciezka );
	}

	unset( $meta['original_image'] );

	return $meta;
}

/**
 * Miniatury w WebP.
 *
 * Wgrane zdjęcia są już w WebP, więc ten filtr obsługuje to, co przyszło inną
 * drogą: migrację ze starej strony i przeliczanie miniatur poleceniem
 * `wp media regenerate`.
 *
 * @param array $formaty Mapowanie formatu wejściowego na wyjściowy.
 * @return array
 */
function przedszkole_format_miniatur( $formaty ) {
	$formaty['image/jpeg'] = 'image/webp';
	$formaty['image/png']  = 'image/webp';

	return $formaty;
}

/*
 * Warunek `imagewebp` zabezpiecza przed hostingiem bez obsługi WebP — tam żaden
 * z trzech filtrów się nie założy, a wgrywanie zadziała po staremu.
 */
if ( function_exists( 'imagewebp' ) ) {
	add_filter( 'wp_handle_upload', 'przedszkole_upload_na_webp' );
	add_filter( 'wp_generate_attachment_metadata', 'przedszkole_bez_oryginalu', 10, 2 );
	add_filter( 'image_editor_output_format', 'przedszkole_format_miniatur' );
}

/**
 * Limit rozmiaru wgrywanego pliku.
 *
 * PHP na hostingu pozwala na 100 MB — tyle nie potrzebuje żadna treść
 * przedszkola, a jeden przypadkowy plik z aparatu potrafi zapełnić przestrzeń
 * na serwerze. 5 MB mieści zeskanowany kilkustronicowy dokument i zdjęcie
 * z telefonu, a odcina filmy i surowe pliki z aparatu.
 *
 * @return int Limit w bajtach.
 */
function przedszkole_limit_uploadu() {
	return 5 * MB_IN_BYTES;
}
add_filter( 'upload_size_limit', 'przedszkole_limit_uploadu' );

/**
 * Oznaczenie odnośników prowadzących poza stronę.
 *
 * Strzałka przy przycisku „Zobacz zdjęcia" jest rysowana w CSS, więc czytnik
 * ekranu jej nie przeczyta — dopisujemy zapowiedź słowami. Dokumenty PDF
 * otwieramy w nowej karcie (plik nie zastępuje wtedy przeglądanej strony),
 * a zgodnie z WCAG 2.1 uprzedzamy o tym przed kliknięciem: wzrokowo ikoną
 * w CSS, a dla czytnika ekranu tekstem w odnośniku.
 *
 * Filtr działa na gotowym HTML-u bloku, bo treść pisze personel w edytorze —
 * nikt nie będzie pamiętał o dopisywaniu takich adnotacji ręcznie.
 *
 * @param string $html  Wyrenderowany blok.
 * @param array  $blok  Dane bloku.
 * @return string
 */
function przedszkole_linki_zewnetrzne( $html, $blok ) {
	$nazwa = $blok['blockName'] ?? '';

	if ( 'core/button' === $nazwa && str_contains( $html, 'is-style-galeria' ) ) {
		return (string) preg_replace(
			'#(<a\b[^>]*wp-block-button__link[^>]*>)(.*?)(</a>)#s',
			'$1$2<span class="screen-reader-text"> ' . esc_html__( '(album w serwisie Google Zdjęcia)', 'przedszkole' ) . '</span>$3',
			$html,
			1
		);
	}

	if ( 'core/file' === $nazwa ) {
		// Pierwszy odnośnik w bloku to nazwa pliku; drugi to przycisk „Pobierz”.
		return (string) preg_replace(
			'#<a\s+(href="[^"]*")\s*>(.*?)</a>#s',
			'<a $1 target="_blank" rel="noopener">$2<span class="screen-reader-text"> ' . esc_html__( '(otwiera się w nowej karcie)', 'przedszkole' ) . '</span></a>',
			$html,
			1
		);
	}

	return $html;
}
add_filter( 'render_block', 'przedszkole_linki_zewnetrzne', 10, 2 );

/**
 * Nagłówek archiwum bez przedrostka.
 *
 * WordPress pisze „Kategoria: Żabki”. Na stronie z sześcioma grupami
 * wystarczy „Żabki” — słowo „Kategoria” nie mówi rodzicowi niczego,
 * czego nie widać z kontekstu.
 *
 * @return string
 */
function przedszkole_bez_przedrostka() {
	return '';
}
add_filter( 'get_the_archive_title_prefix', 'przedszkole_bez_przedrostka' );

/**
 * Limit wersji roboczych wpisu.
 *
 * WordPress zapisuje pełną kopię treści przy każdym zapisie — także przy
 * autozapisie co minutę. Strona, którą personel redaguje przez kilka lat,
 * potrafi w ten sposób urosnąć o kilkadziesiąt tysięcy wierszy w `wp_posts`,
 * z których nikt nigdy nie skorzysta. Pięć ostatnich wersji wystarcza,
 * żeby cofnąć nieudaną zmianę, i tyle właśnie zostaje.
 *
 * Przez filtr, nie przez stałą w `wp-config.php`: plik konfiguracyjny jest poza
 * repozytorium i powstaje na serwerze od nowa (patrz `inc/bezpieczenstwo.php`).
 *
 * @param int $ile Domyślna liczba wersji (`true` oznacza „bez limitu”).
 * @return int
 */
function przedszkole_limit_wersji( $ile ) {
	return 5;
}
add_filter( 'wp_revisions_to_keep', 'przedszkole_limit_wersji' );

/**
 * Logo bez znacznika najwyższego priorytetu pobierania.
 *
 * WordPress wskazuje przeglądarce jeden obrazek jako najważniejszy do pobrania
 * (`fetchpriority="high"`) — i trafia nim w logo, bo jest pierwsze w kodzie.
 * Logo waży kilkanaście kilobajtów i nigdy nie jest największym elementem
 * widocznym po wejściu: na stronie głównej jest nim ilustracja w powitaniu,
 * na wpisie — zdjęcie wyróżniające.
 *
 * Samo wycięcie atrybutu z gotowego znacznika nie wystarcza. Rdzeń trzyma
 * osobną flagę „priorytet już przyznany” i zdejmuje ją przy pierwszym trafieniu,
 * więc kolejne obrazki nie dostałyby go tak czy owak — usunęlibyśmy wskazanie
 * z logo i nie dali go nikomu. Dlatego flagę oddajemy z powrotem
 * (`wp_high_priority_element_flag( true )`), a znacznik zabieramy tylko logo.
 *
 * @param array  $atrybuty Atrybuty wyliczone przez rdzeń (`loading`, `fetchpriority`, `decoding`).
 * @param string $znacznik Nazwa znacznika HTML.
 * @param array  $obrazek  Atrybuty obrazka.
 * @return array
 */
function przedszkole_logo_bez_priorytetu( $atrybuty, $znacznik, $obrazek ) {
	if ( empty( $atrybuty['fetchpriority'] ) || 'img' !== $znacznik ) {
		return $atrybuty;
	}

	if ( empty( $obrazek['class'] ) || ! str_contains( $obrazek['class'], 'custom-logo' ) ) {
		return $atrybuty;
	}

	unset( $atrybuty['fetchpriority'] );
	wp_high_priority_element_flag( true );

	return $atrybuty;
}
add_filter( 'wp_get_loading_optimization_attributes', 'przedszkole_logo_bez_priorytetu', 10, 3 );
