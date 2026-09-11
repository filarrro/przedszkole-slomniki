<?php
/**
 * Sekcja „Pomagamy dzieciom rozwijać skrzydła" — hasło na zdjęciu.
 *
 * Jedno zdanie rozbite na dwa paski: pierwszy w lewej górnej części kadru,
 * drugi niżej po prawej. Paski są opisane jako `<span>` wewnątrz jednego
 * nagłówka, bo to jedno zdanie — dwa osobne nagłówki rozbijałyby je
 * w czytniku ekranu.
 *
 * Tekst leży na zdjęciu, ale nigdy bezpośrednio na nim: każdy pasek ma pełne
 * granatowe tło z palety, więc kontrast (9,2 wobec bieli) nie zależy od tego,
 * co akurat jest pod spodem.
 *
 * Kadr wychodzi poza siatkę treści (1140 px) i rozciąga się do 2560 px, więc
 * zdjęcie jest w trzech szerokościach: 1140 (31 kB), 1920 (66 kB) i 2816
 * (129 kB). Szerzej nie idziemy — 2816 px to natywna rozdzielczość oryginału.
 *
 * Powyżej 2000 px kadr jest przycinany od dołu (CSS), żeby zdjęcie nie urosło
 * na całą wysokość ekranu — dlatego `width`/`height` opisują plik, a nie to,
 * co widać.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="skrzydla">
	<figure class="skrzydla__kadr">

		<img class="skrzydla__zdjecie"
			src="<?php echo esc_url( get_theme_file_uri( 'assets/img/skrzydla-1920.webp' ) ); ?>"
			srcset="<?php
				echo esc_attr(
					get_theme_file_uri( 'assets/img/skrzydla-1140.webp' ) . ' 1140w, ' .
					get_theme_file_uri( 'assets/img/skrzydla-1920.webp' ) . ' 1920w, ' .
					get_theme_file_uri( 'assets/img/skrzydla-2816.webp' ) . ' 2816w'
				);
			?>"
			sizes="min(100vw, 2560px)"
			width="2816" height="1536"
			alt="<?php esc_attr_e( 'Uśmiechnięte dziecko w stroju ptaka z kartonowymi skrzydłami, na tle ściany z narysowanymi kredą chmurami i słońcem', 'przedszkole' ); ?>"
			loading="lazy" decoding="async">

		<figcaption class="skrzydla__haslo">
			<h2>
				<span class="skrzydla__pasek skrzydla__pasek--gora">
					<?php esc_html_e( 'Pomagamy dzieciom', 'przedszkole' ); ?>
				</span>
				<span class="skrzydla__pasek skrzydla__pasek--dol">
					<?php esc_html_e( 'rozwijać skrzydła', 'przedszkole' ); ?>
				</span>
			</h2>
		</figcaption>

	</figure>
</section>
