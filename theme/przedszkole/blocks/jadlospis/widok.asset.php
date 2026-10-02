<?php
/**
 * Zależności skryptu frontu bloku „Jadłospis”.
 *
 * Bez kroku budowania listę utrzymujemy ręcznie, jak w `edytor.asset.php`.
 * Skrypt nie ma zależności — plik istnieje dla wersji: bez niego rdzeń
 * doklejałby do adresu wersję WordPressa i po zmianie skryptu przeglądarki
 * trzymałyby starą kopię.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(),
	'version'      => PRZEDSZKOLE_VERSION,
);
