<?php
/**
 * Konfiguracja motywu Przedszkole.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

define( 'PRZEDSZKOLE_VERSION', '0.6.8' );

require_once get_theme_file_path( 'inc/helpers.php' );

/**
 * Deklaracja możliwości motywu.
 */
function przedszkole_setup() {
	load_theme_textdomain( 'przedszkole', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
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
		)
	);

	// Rozmiar miniatury dla kafelków aktualności. 3:2, przycinany.
	add_image_size( 'przedszkole-karta', 640, 427, true );
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
}
add_action( 'wp_enqueue_scripts', 'przedszkole_assets' );

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
}
add_action( 'init', 'przedszkole_cleanup' );

/**
 * Wyłączenie XML-RPC. Strona nie korzysta z zewnętrznych klientów,
 * a jest to częsty cel ataków siłowych na hasła.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

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
