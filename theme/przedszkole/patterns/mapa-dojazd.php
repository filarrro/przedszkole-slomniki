<?php
/**
 * Title: Mapa dojazdu
 * Slug: przedszkole/mapa-dojazd
 * Categories: przedszkole
 * Description: Statyczny wycinek mapy ze znacznikiem przedszkola i odnośnik do nawigacji.
 *
 * Mapa jest obrazkiem, a nie osadzoną mapą Google. Osadzenie ładowałoby się
 * z cudzego serwera przy każdym wejściu na stronę: kilkaset kilobajtów,
 * ciasteczka i klauzula RODO do napisania. Obrazek waży 43 kB na telefonie,
 * nie śledzi nikogo i wygląda tak samo po latach.
 *
 * Blok „HTML” zamiast zwykłego obrazka, bo plik leży w motywie, a nie
 * w bibliotece mediów — tylko tak da się podać dwie wielkości do wyboru
 * (`srcset`), tak jak przy zdjęciu na stronie głównej.
 *
 * Kafelki mapy pochodzą z OpenStreetMap (licencja ODbL) — stąd podpis
 * z odnośnikiem do autorów, którego nie wolno usuwać.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_mapa_duza = get_theme_file_uri( 'assets/img/mapa-dojazd-1600.webp' );
$przedszkole_mapa_mala = get_theme_file_uri( 'assets/img/mapa-dojazd-800.webp' );
?>
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Jak do nas trafić</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<figure class="mapa">
	<img class="mapa__obraz"
		src="<?php echo esc_url( $przedszkole_mapa_mala ); ?>"
		srcset="<?php echo esc_attr( $przedszkole_mapa_mala . ' 800w, ' . $przedszkole_mapa_duza . ' 1600w' ); ?>"
		sizes="(min-width: 800px) 760px, 92vw"
		width="1600" height="800"
		alt="Mapa Słomnik ze znacznikiem przedszkola przy ulicy Świętej Jadwigi Królowej 4, niedaleko rynku"
		loading="lazy" decoding="async">
	<figcaption class="mapa__podpis">
		Mapa: <a href="https://www.openstreetmap.org/copyright" rel="noopener">autorzy OpenStreetMap</a>
	</figcaption>
</figure>
<!-- /wp:html -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://www.google.com/maps/dir/?api=1&amp;destination=50.2451488,20.0886038">Wyznacz trasę</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
