<?php
/**
 * Front bloku „Osoba”.
 *
 * Cały układ kafelka powstaje tutaj, a nie w treści strony. Dzięki temu
 * poprawka wyglądu to jeden plik, a nie trzynaście ręcznie poprawianych
 * kafelków — i pracownik nie ma czego rozsypać w edytorze.
 *
 * Dostępne zmienne: $attributes, $content (biogram z bloków podrzędnych), $block.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$imie  = trim( $attributes['imie'] ?? '' );
$tytul = trim( $attributes['tytul'] ?? '' );
$id    = (int) ( $attributes['zdjecieId'] ?? 0 );
$url   = $attributes['zdjecieUrl'] ?? '';

// Wyrównanie zależy od długości biogramu, nie od zdjęcia: przy krótkim opisie
// kafelek wygląda lepiej wyśrodkowany, przy długim — wyrównany do góry.
$wyrownanie = 'gora' === ( $attributes['wyrownanie'] ?? '' ) ? 'gora' : 'srodek';

/*
 * Zdjęcie przez `wp_get_attachment_image`, żeby dostać `srcset` i wymiary.
 * Pusty `alt` celowo: zdjęcie stoi obok nagłówka z imieniem i nazwiskiem,
 * więc dla czytnika ekranu nie niesie nic ponad to, co już przeczytał.
 *
 * Skasowany załącznik zwraca pusty ciąg — wtedy kafelek wraca do inicjałów
 * zamiast pokazać dziurę.
 */
$portret = '';
if ( $id ) {
	$portret = wp_get_attachment_image(
		$id,
		'large',
		false,
		array(
			'alt'   => '',
			'class' => 'kafelek-osoby__zdjecie',
		)
	);
} elseif ( $url ) {
	$portret = sprintf(
		'<img src="%s" alt="" class="kafelek-osoby__zdjecie" />',
		esc_url( $url )
	);
}

if ( '' === $portret ) {
	/*
	 * Inicjały idą dwa razy: raz normalnie, raz jako powiększony znak wodny
	 * obcięty krawędzią koła. CSS nie odczyta tekstu elementu, więc duplikat
	 * musi stać w znacznikach; `aria-hidden` trzyma go poza drzewem
	 * dostępności, żeby czytnik ekranu nie przeczytał inicjałów dwa razy.
	 */
	$inicjaly = przedszkole_inicjaly( html_entity_decode( wp_strip_all_tags( $imie ), ENT_QUOTES, 'UTF-8' ) );
	$portret  = sprintf(
		'<p class="kafelek-osoby__inicjaly">%1$s<span class="kafelek-osoby__znak-wodny" aria-hidden="true">%1$s</span></p>',
		esc_html( $inicjaly )
	);
}

/*
 * Imię i tytuł idą przez `wp_kses_post`, nie `esc_html`: pole `RichText`
 * w edytorze zapisuje już gotowy HTML, więc encje w rodzaju `&amp;`
 * wyszłyby po `esc_html` na ekran dosłownie.
 */
$atrybuty = get_block_wrapper_attributes(
	array( 'class' => 'kafelek-osoby kafelek-osoby--' . $wyrownanie )
);
?>
<div <?php echo $atrybuty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — get_block_wrapper_attributes escapuje samo. ?>>
	<div class="kafelek-osoby__portret"><?php echo wp_kses_post( $portret ); ?></div>
	<div class="kafelek-osoby__opis">
		<?php if ( '' !== $imie ) : ?>
			<h3 class="kafelek-osoby__imie"><?php echo wp_kses_post( $imie ); ?></h3>
		<?php endif; ?>
		<?php if ( '' !== $tytul ) : ?>
			<p class="kafelek-osoby__tytul"><?php echo wp_kses_post( $tytul ); ?></p>
		<?php endif; ?>
		<div class="kafelek-osoby__biogram">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — bloki podrzędne, już przepuszczone przez render rdzenia. ?>
		</div>
	</div>
</div>
