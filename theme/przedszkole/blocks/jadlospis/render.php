<?php
/**
 * Front bloku „Jadłospis”.
 *
 * Cały układ tygodnia powstaje tutaj, a w treści strony zostają same dane
 * w atrybutach bloku. Poprawka wyglądu to jeden plik, a intendent nie ma
 * czego rozsypać w edytorze.
 *
 * Klasa `alignfull` nie jest wyborem wyrównania: w edytorze kontener układu
 * rdzenia wymusza na każdym innym bloku szerokość kolumny treści
 * i `margin: auto !important`. Front wychodzi poza kolumnę własną regułą
 * w `style.css` (sekcja 29), edytor — dzięki tej klasie.
 *
 * Dostępne zmienne: $attributes, $content (pusty — blok nie ma dzieci), $block.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$poczatek   = przedszkole_jadlospis_poczatek( $attributes['poczatek'] ?? '' );
$dni        = is_array( $attributes['dni'] ?? null ) ? $attributes['dni'] : array();
$posilki    = przedszkole_jadlospis_posilki();
$id_zakresu = wp_unique_id( 'jadlospis-zakres-' );

$dodatkowe = array( 'class' => 'jadlospis alignfull' );

// Nazwa sekcji dla czytnika ekranu to zakres tygodnia — bez daty nie ma
// nagłówka, więc nie ma też czego wskazać.
if ( $poczatek ) {
	$dodatkowe['aria-labelledby'] = $id_zakresu;
}

$atrybuty = get_block_wrapper_attributes( $dodatkowe );
?>
<section <?php echo $atrybuty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — get_block_wrapper_attributes escapuje samo. ?>>
	<?php if ( $poczatek ) : ?>
		<h2 class="jadlospis__zakres" id="<?php echo esc_attr( $id_zakresu ); ?>"><?php echo esc_html( przedszkole_jadlospis_zakres( $poczatek ) ); ?></h2>
	<?php endif; ?>
	<ol class="jadlospis__dni">
		<?php
		foreach ( przedszkole_jadlospis_dni() as $i => $opis ) :
			$dzien   = przedszkole_jadlospis_dzien( $dni[ $i ] ?? array() );
			$wpisane = przedszkole_jadlospis_wpisane( $dzien );
			$data    = $poczatek ? $poczatek->modify( '+' . $i . ' days' ) : null;
			?>
			<li class="jadlospis__dzien jadlospis__dzien--<?php echo esc_attr( $opis['grupa'] ); ?>"<?php echo $data ? ' data-data="' . esc_attr( $data->format( 'Y-m-d' ) ) . '"' : ''; ?>>
				<header class="jadlospis__naglowek">
					<h3 class="jadlospis__nazwa"><?php echo esc_html( $opis['nazwa'] ); ?></h3>
					<?php if ( $data ) : ?>
						<p class="jadlospis__data"><time datetime="<?php echo esc_attr( $data->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( 'j F', $data->getTimestamp() ) ); ?></time></p>
						<?php
						/*
						 * Plakietka stoi w znacznikach od razu, ukryta. Który dzień jest
						 * „dziś”, wie dopiero przeglądarka (strona może wyjść z cache),
						 * więc `widok.js` tylko zdejmuje `hidden` — bez tłumaczeń w JS.
						 */
						?>
						<p class="jadlospis__dzis" hidden><?php esc_html_e( 'Dziś', 'przedszkole' ); ?></p>
					<?php endif; ?>
				</header>
				<div class="jadlospis__tresc">
					<?php if ( $dzien['wolny'] ) : ?>
						<p class="jadlospis__wolne"><?php esc_html_e( 'Dzień wolny', 'przedszkole' ); ?></p>
					<?php elseif ( ! $wpisane ) : ?>
						<p class="jadlospis__pusty"><?php esc_html_e( 'Jadłospis w przygotowaniu', 'przedszkole' ); ?></p>
					<?php else : ?>
						<?php foreach ( $wpisane as $klucz => $tresc ) : ?>
							<div class="jadlospis__posilek jadlospis__posilek--<?php echo esc_attr( $klucz ); ?>">
								<h4 class="jadlospis__etykieta"><?php echo esc_html( $posilki[ $klucz ] ); ?></h4>
								<p><?php echo wp_kses_post( $tresc ); ?></p>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
