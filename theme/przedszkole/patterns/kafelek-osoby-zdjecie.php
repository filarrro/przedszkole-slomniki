<?php
/**
 * Title: Kafelek osoby — ze zdjęciem
 * Slug: przedszkole/kafelek-osoby-zdjecie
 * Categories: przedszkole
 * Description: Biogram pracownika z okrągłym zdjęciem. Zdjęcie wstawia się w lewej kolumnie; kadr obcina je do kółka.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:columns {"verticalAlignment":"center","className":"is-style-kafelek-osoby"} -->
<div class="wp-block-columns are-vertically-aligned-center is-style-kafelek-osoby"><!-- wp:column {"verticalAlignment":"center","width":"38%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:38%"><!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Imię i nazwisko</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"kafelek-osoby__tytul"} -->
<p class="kafelek-osoby__tytul">Uzyskany tytuł lub wykształcenie</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tu wpisz biogram — wykształcenie, staż pracy, grupę, zainteresowania.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
