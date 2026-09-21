<?php
/**
 * Ostatnie wpisy kategorii pod treścią strony.
 *
 * Wiążemy stronę z kategorią przez slug: strona „Misie" i kategoria „Misie"
 * mają ten sam slug, więc nie trzeba niczego łączyć ręcznie w panelu. Tak samo
 * strona „Kącik logopedy" i kategoria o slugu `logopeda`, „Kącik pedagoga"
 * i `pedagog` — lista kącików siedzi w {@see przedszkole_kaciki()}.
 *
 * Dwa zachowania, bo dwa rodzaje treści:
 *
 * * **Grupa** — wpisy są aktualnościami, więc blok pokazuje bieżący rocznik.
 *   Gdy grupa nic jeszcze nie dodała, zamiast znikać zostawia zdanie
 *   i przejście do poprzedniego rocznika. Pusty blok niesie tu informację:
 *   „jeszcze nic, stare jest tutaj".
 * * **Kącik specjalisty** — poradniki bez daty ważności. Bez cięcia po
 *   roczniku, a gdy pusto, sekcja po prostu się nie pokazuje. Świeżo
 *   założony kącik nie straszy więc pustą ramką.
 *
 * @package Przedszkole
 * @param string $args['slug'] Slug strony, ten sam co slug kategorii.
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';

$przedszkole_kaciki     = przedszkole_kaciki();
$przedszkole_jest_grupa = in_array( $przedszkole_slug, przedszkole_grupy(), true );
$przedszkole_jest_kacik = isset( $przedszkole_kaciki[ $przedszkole_slug ] );

if ( ! $przedszkole_jest_grupa && ! $przedszkole_jest_kacik ) {
	return;
}

$przedszkole_kategoria = get_category_by_slug( $przedszkole_slug );

if ( ! $przedszkole_kategoria ) {
	return;
}

$przedszkole_parametry = array(
	'post_type'           => 'post',
	'cat'                 => $przedszkole_kategoria->term_id,
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);

$przedszkole_rok     = '';
$przedszkole_starszy = '';

if ( $przedszkole_jest_grupa ) {
	/* Strona grupy nie jest listą, więc nie czytamy `?rok=` — zawsze
	   pokazujemy rocznik bieżący. */
	$przedszkole_rok = przedszkole_rok_slug( przedszkole_rok_szkolny() );

	$przedszkole_parametry['date_query'] = przedszkole_zakres_roku( $przedszkole_rok );

	$przedszkole_lata = przedszkole_lata_szkolne();
	$przedszkole_poz  = array_search( $przedszkole_rok, $przedszkole_lata, true );

	if ( false !== $przedszkole_poz && isset( $przedszkole_lata[ $przedszkole_poz + 1 ] ) ) {
		$przedszkole_starszy = $przedszkole_lata[ $przedszkole_poz + 1 ];
	}
}

$przedszkole_wpisy = new WP_Query( $przedszkole_parametry );

// Kącik bez artykułów: sekcja się nie pokazuje.
if ( ! $przedszkole_wpisy->have_posts() && $przedszkole_jest_kacik ) {
	wp_reset_postdata();
	return;
}

$przedszkole_adres_kategorii = get_category_link( $przedszkole_kategoria );
?>

<section class="section">

	<div class="section__head">
		<h2>
			<?php
			if ( $przedszkole_jest_kacik ) {
				echo esc_html( $przedszkole_kaciki[ $przedszkole_slug ] );
			} else {
				esc_html_e( 'Aktualności grupy', 'przedszkole' );
			}
			?>
		</h2>

		<?php if ( $przedszkole_wpisy->have_posts() ) : ?>
			<a href="<?php echo esc_url( $przedszkole_adres_kategorii ); ?>">
				<?php esc_html_e( 'Zobacz więcej', 'przedszkole' ); ?> →
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $przedszkole_wpisy->have_posts() ) : ?>

		<div class="cards">
			<?php
			while ( $przedszkole_wpisy->have_posts() ) :
				$przedszkole_wpisy->the_post();
				get_template_part( 'template-parts/card' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>

	<?php else : ?>

		<p>
			<?php
			printf(
				/* translators: %s: rok szkolny, na przykład 2026/2027. */
				esc_html__( 'Grupa nie dodała jeszcze wpisów w roku szkolnym %s.', 'przedszkole' ),
				esc_html( przedszkole_rok_z_slug( $przedszkole_rok ) )
			);
			?>
		</p>

		<?php if ( $przedszkole_starszy ) : ?>
			<p>
				<a href="<?php echo esc_url( add_query_arg( 'rok', $przedszkole_starszy, $przedszkole_adres_kategorii ) ); ?>">
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

	<?php endif; ?>

</section>
