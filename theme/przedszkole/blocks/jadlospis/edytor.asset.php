<?php
/**
 * Zależności skryptu edytora bloku „Jadłospis”.
 *
 * Normalnie ten plik generuje `@wordpress/scripts` przy budowaniu paczki.
 * Motyw nie ma kroku budowania — `edytor.js` to zwykły JavaScript bez JSX,
 * więc listę zależności utrzymujemy ręcznie. Bez `wp-components` zabrakłoby
 * kalendarza, bez `wp-block-editor` — pól tekstowych.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-data',
		'wp-element',
		'wp-i18n',
	),
	'version'      => PRZEDSZKOLE_VERSION,
);
