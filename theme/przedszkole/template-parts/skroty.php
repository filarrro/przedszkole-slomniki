<?php
/**
 * Kafelki „Na skróty" na stronie głównej.
 *
 * Treść bierzemy z menu w lokalizacji „Na skróty" — dyrekcja układa kafelki
 * w Wyglądzie → Menu, tak samo jak menu główne, bez dotykania kodu. Opis pod
 * tytułem to pole „Opis" pozycji menu (widoczne po włączeniu w Opcjach ekranu);
 * kafelek bez opisu jest poprawny, po prostu niższy.
 *
 * Ikona dobiera się po slugu strony, do której kafelek prowadzi. Menu może
 * wskazywać dowolny adres — wtedy zostaje ikona domyślna, a kafelek nadal działa.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

if ( ! has_nav_menu( 'skroty' ) ) {
	return;
}

$przedszkole_lokalizacje = get_nav_menu_locations();
$przedszkole_pozycje     = wp_get_nav_menu_items( $przedszkole_lokalizacje['skroty'] );

if ( empty( $przedszkole_pozycje ) ) {
	return;
}

/*
 * Kolory z palety grup. Kolejność nie jest tęczowa: sekcja stoi na żółtym tle,
 * więc barwy najbliższe żółci — kotki i wiewiórki — idą na koniec, gdzie przy
 * czterech kafelkach w ogóle nie wypadną. Kafelków bywa mniej niż kolorów
 * i odwrotnie, więc lista zapętla się modulo.
 */
$przedszkole_kolory = array( 'zajaczki', 'zabki', 'misie', 'jezyki', 'wiewiorki', 'kotki' );
?>

<section class="skroty">
	<div class="wrap">

		<h2 class="skroty__naglowek"><?php esc_html_e( 'Na skróty', 'przedszkole' ); ?></h2>

		<ul class="skroty__lista">
			<?php foreach ( $przedszkole_pozycje as $przedszkole_i => $przedszkole_pozycja ) : ?>
				<?php
				$przedszkole_slug  = 'page' === $przedszkole_pozycja->object
					? get_post_field( 'post_name', $przedszkole_pozycja->object_id )
					: '';
				$przedszkole_kolor = $przedszkole_kolory[ $przedszkole_i % count( $przedszkole_kolory ) ];
				?>
				<li class="skrot skrot--<?php echo esc_attr( $przedszkole_kolor ); ?>">
					<a class="skrot__link" href="<?php echo esc_url( $przedszkole_pozycja->url ); ?>">

						<span class="skrot__gora">

						<span class="skrot__ikona" aria-hidden="true">
							<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="3"
								stroke-linecap="round" stroke-linejoin="round" focusable="false">
								<?php
								switch ( $przedszkole_slug ) {
									case 'grupy':
										// Trójka dzieci - trzy głowy i ramiona.
										?>
										<circle cx="15" cy="17" r="6"/>
										<circle cx="33" cy="17" r="6"/>
										<path d="M6 40c0-6 4-10 9-10s9 4 9 10"/>
										<path d="M24 40c0-6 4-10 9-10s9 4 9 10"/>
										<?php
										break;

									case 'aktualnosci':
										// Gazetka.
										?>
										<rect x="7" y="11" width="27" height="26" rx="3"/>
										<path d="M34 18h7v15a4 4 0 0 1-8 0"/>
										<path d="M13 19h15M13 25h15M13 31h9"/>
										<?php
										break;

									case 'dla-rodzicow':
									case 'dokumenty':
										// Kartka z zagiętym rogiem.
										?>
										<path d="M12 6h16l10 10v26H12z"/>
										<path d="M28 6v10h10"/>
										<path d="M18 26h14M18 33h10"/>
										<?php
										break;

									case 'galeria':
										// Zdjęcie z górami i słońcem.
										?>
										<rect x="6" y="10" width="36" height="28" rx="3"/>
										<circle cx="17" cy="20" r="3"/>
										<path d="M6 32l10-9 8 7 6-5 12 11"/>
										<?php
										break;

									case 'kontakt':
										// Koperta.
										?>
										<rect x="6" y="11" width="36" height="26" rx="3"/>
										<path d="M6 15l18 12 18-12"/>
										<?php
										break;

									case 'jadlospis':
										// Talerz ze sztućcami.
										?>
										<circle cx="24" cy="24" r="13"/>
										<circle cx="24" cy="24" r="6"/>
										<?php
										break;

									default:
										// Domyślna: gwiazdka - kafelek prowadzi gdziekolwiek.
										?>
										<path d="M24 8l5 11 12 1-9 8 3 12-11-6-11 6 3-12-9-8 12-1z"/>
										<?php
								}
								?>
							</svg>
						</span>

						<span class="skrot__tytul"><?php echo esc_html( $przedszkole_pozycja->title ); ?></span>

						</span>

						<span class="skrot__dol">
							<?php
							/*
							 * Falista granica między kolorową górą a białym dołem karty.
							 * Rysunek leży na dole, wypełniony kolorem góry, i wchodzi
							 * w biel — dlatego siedzi w dolnej części, a nie w górnej.
							 */
							?>
							<span class="skrot__fala" aria-hidden="true">
								<svg viewBox="0 0 1200 120" preserveAspectRatio="none" focusable="false">
									<path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"/>
								</svg>
							</span>

							<?php if ( $przedszkole_pozycja->post_content ) : ?>
								<span class="skrot__opis"><?php echo esc_html( $przedszkole_pozycja->post_content ); ?></span>
							<?php endif; ?>
						</span>

					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>

	<?php
	/*
	 * Fala zamykająca należy do sekcji, bo tylko wewnątrz niej widać żółte tło
	 * nad grzbietem — poza sekcją przezroczysta część rysunku pokazywałaby tło
	 * strony i przejście rozjechałoby się na ostrą krawędź.
	 */
	get_template_part(
		'template-parts/fala',
		null,
		array(
			'ksztalt' => 'warstwy',
			'kolor'   => 'var(--wp--preset--color--base)',
		)
	);
	?>
</section>
