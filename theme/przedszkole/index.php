<?php
/**
 * Listy wpisów: aktualności, kategorie, archiwa dat, autorzy, wyniki wyszukiwania.
 *
 * Jeden plik na wszystkie listy. WordPress szuka kolejno `home.php`,
 * `category.php`, `archive.php`, a na końcu `index.php` — skoro każdy z nich
 * miałby tę samą treść, zostaje wyłącznie ostatni. Mniej plików do utrzymania
 * i zero ryzyka, że poprawka trafi do jednego, a ominie dwa pozostałe.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="wrap">

	<header class="page-header">
		<h1>
			<?php
			if ( is_home() && ! is_front_page() ) {
				echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) );
			} else {
				the_archive_title();
			}
			?>
		</h1>
		<?php the_archive_description( '<p>', '</p>' ); ?>
	</header>

	<?php
	/* Filtr tylko na liscie aktualnosci i archiwach kategorii. W wynikach
	   wyszukiwania czy archiwum daty odsylalby do innego zestawu wpisow,
	   niz ten, ktory wlasnie widac - to mylace. */
	if ( is_home() || is_category() ) {
		get_template_part( 'template-parts/filtr-kategorii' );
		get_template_part( 'template-parts/przelacznik-lat' );
	}
	?>

	<?php if ( have_posts() ) : ?>

		<div class="cards cards--lista">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part(
					'template-parts/card',
					null,
					array(
						'uklad'  => 'pozioma',
						'poziom' => 2,
					)
				);
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'class'              => 'pagination',
				'mid_size'           => 1,
				'prev_text'          => __( '← Poprzednie', 'przedszkole' ),
				'next_text'          => __( 'Następne →', 'przedszkole' ),
				'screen_reader_text' => __( 'Nawigacja po stronach', 'przedszkole' ),
			)
		);
		?>

	<?php else : ?>

		<?php
		/* Pusto z powodu cięcia po roczniku to inna sytuacja niż pusto
		   w ogóle. „Brak wpisów” kazałoby rodzicowi myśleć, że grupa nigdy
		   nic nie napisała, podczas gdy poprzedni rocznik jest o jedno
		   kliknięcie stąd. */
		$przedszkole_rok_pusty = '';
		$przedszkole_starszy   = '';

		if ( przedszkole_rok_aktywny() ) {
			$przedszkole_rok_pusty = przedszkole_rok_z_zapytania();
			$przedszkole_lata      = przedszkole_lata_szkolne();
			$przedszkole_poz       = array_search( $przedszkole_rok_pusty, $przedszkole_lata, true );

			// Lista jest malejąca, więc następny indeks to rocznik starszy.
			if ( false !== $przedszkole_poz && isset( $przedszkole_lata[ $przedszkole_poz + 1 ] ) ) {
				$przedszkole_starszy = $przedszkole_lata[ $przedszkole_poz + 1 ];
			}
		}
		?>

		<div class="notice">
			<?php if ( $przedszkole_rok_pusty ) : ?>

				<h2>
					<?php
					printf(
						/* translators: %s: rok szkolny, na przykład 2026/2027. */
						esc_html__( 'W roku szkolnym %s nie ma jeszcze wpisów', 'przedszkole' ),
						esc_html( przedszkole_rok_z_slug( $przedszkole_rok_pusty ) )
					);
					?>
				</h2>

				<?php if ( $przedszkole_starszy ) : ?>
					<p>
						<a href="<?php echo esc_url( add_query_arg( 'rok', $przedszkole_starszy ) ); ?>">
							<?php
							printf(
								/* translators: %s: rok szkolny, na przykład 2025/2026. */
								esc_html__( 'Zobacz wpisy z roku %s', 'przedszkole' ),
								esc_html( przedszkole_rok_z_slug( $przedszkole_starszy ) )
							);
							?>
						</a>
					</p>
				<?php endif; ?>

			<?php else : ?>

				<h2><?php esc_html_e( 'Brak wpisów', 'przedszkole' ); ?></h2>
				<p><?php esc_html_e( 'Nie ma tu jeszcze żadnych treści.', 'przedszkole' ); ?></p>

			<?php endif; ?>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
