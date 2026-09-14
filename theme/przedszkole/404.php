<?php
/**
 * Strona błędu 404.
 *
 * Kolejność: nagłówek, ilustracja, wyjaśnienie, powrót na stronę główną.
 * Plama pod ilustracją jest przesunięta w prawo i w dół — ten sam chwyt co
 * w hero na stronie głównej (sekcja 13 w style.css), tylko bez centrowania.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="wrap blad">

	<h1 class="blad__tytul"><?php esc_html_e( 'Ups! Coś poszło nie tak…', 'przedszkole' ); ?></h1>

	<figure class="blad__art">
		<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/404.webp' ) ); ?>"
			srcset="<?php echo esc_attr( get_theme_file_uri( 'assets/img/404-maly.webp' ) . ' 820w, ' . get_theme_file_uri( 'assets/img/404.webp' ) . ' 1400w' ); ?>"
			sizes="(min-width: 900px) 60vw, 92vw"
			width="1400" height="764"
			alt="<?php esc_attr_e( 'Rudy kotek turla się ze śmiechu na podłodze, a myszka obok zaśmiewa się w głos', 'przedszkole' ); ?>">
	</figure>

	<div class="blad__tresc">
		<p class="blad__wiodacy"><?php esc_html_e( 'Szukanej strony tutaj niestety nie ma.', 'przedszkole' ); ?></p>
		<p><?php esc_html_e( 'Mogła zostać przeniesiona, zmienić adres albo już jej nie publikujemy. Zdarza się też, że w adresie zabrakło jednej literki. Wróć na stronę główną i poszukaj stamtąd — albo zadzwoń do nas, jeśli czegoś nie możesz znaleźć.', 'przedszkole' ); ?></p>

		<p class="blad__powrot">
			<a class="blad__przycisk" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Wróć na stronę główną', 'przedszkole' ); ?>
			</a>
		</p>
	</div>

</div>

<?php
get_footer();
