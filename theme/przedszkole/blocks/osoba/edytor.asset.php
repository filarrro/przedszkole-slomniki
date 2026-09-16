<?php
/**
 * Zależności skryptu edytora bloku „Osoba”.
 *
 * Normalnie ten plik generuje `@wordpress/scripts` przy budowaniu paczki.
 * Motyw nie ma kroku budowania — `edytor.js` to zwykły JavaScript bez JSX,
 * więc listę zależności utrzymujemy ręcznie. Bez niej WordPress zarejestruje
 * skrypt bez `wp-block-editor` i rejestracja bloku wywali się w konsoli.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
	),
	'version'      => PRZEDSZKOLE_VERSION,
);
