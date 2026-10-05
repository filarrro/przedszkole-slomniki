<?php
/**
 * Strona główna.
 *
 * Powitanie ma stałe teksty z motywu ({@see przedszkole_powitanie()}).
 * Treść strony ustawionej jako główna, pisana w Gutenbergu, to ogłoszenie
 * tymczasowe: dyrekcja wpisuje je sama, a gdy je skasuje, sekcja znika.
 *
 * Kolejność: powitanie → ogłoszenie → aktualności → „Na skróty" → „Dlaczego
 * my" → hasło ze zdjęciem.
 *
 * Fala należy do sekcji, która ją poprzedza, ale ma kolor tej, która po niej
 * następuje. Dlatego zapytanie o wpisy i ogłoszenie lecą przed hero, a kolory
 * liczymy z góry: przy pustej stronie (bez ogłoszenia, wpisów i menu skrótów)
 * fala pod hero prowadziłaby do nieistniejącego tła.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Na stronie głównej pokazujemy wyłącznie ogłoszenia — wpisy grup mają
// własne strony i zalewałyby tę listę.
$przedszkole_aktualnosci = new WP_Query(
	array(
		'post_type'           => 'post',
		'category_name'       => 'ogloszenia',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

/*
 * Ogłoszenie renderujemy z góry, a nie w miejscu wypisania: o tym, czy sekcja
 * w ogóle powstanie, decyduje wynik po filtrach, nie surowe pole w bazie.
 */
$przedszkole_ogloszenie = '';
while ( have_posts() ) {
	the_post();
	ob_start();
	the_content();
	$przedszkole_ogloszenie = (string) ob_get_clean();
}

$przedszkole_jest_ogloszenie = przedszkole_tresc_niepusta( $przedszkole_ogloszenie );
$przedszkole_sa_wpisy        = $przedszkole_aktualnosci->have_posts();
$przedszkole_sa_skroty       = has_nav_menu( 'skroty' );
$przedszkole_powitanie       = przedszkole_powitanie();

$przedszkole_zolty = '#F9E229';   // Tło sekcji „Na skróty”.
$przedszkole_mieta = '#F3F8F2';   // Górny koniec gradientu pod aktualnościami.
$przedszkole_tlo   = 'var(--wp--preset--color--base)';

// Kolor fali pod hero to tło pierwszej sekcji, która faktycznie się pojawi.
// Ogłoszenie i aktualności dzielą jedno miękkie tło.
if ( $przedszkole_jest_ogloszenie || $przedszkole_sa_wpisy ) {
	$przedszkole_kolor_pod_hero = $przedszkole_mieta;
} elseif ( $przedszkole_sa_skroty ) {
	$przedszkole_kolor_pod_hero = $przedszkole_zolty;
} else {
	$przedszkole_kolor_pod_hero = $przedszkole_tlo;
}
?>

<section class="hero">
	<div class="wrap hero__inner">

		<div class="hero__text">
			<h1><?php echo esc_html( $przedszkole_powitanie['tytul'] ); ?></h1>
			<p><?php echo esc_html( $przedszkole_powitanie['opis'] ); ?></p>
			<div class="wp-block-buttons">
				<div class="wp-block-button">
					<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $przedszkole_powitanie['adres'] ); ?>">
						<?php echo esc_html( $przedszkole_powitanie['przycisk'] ); ?>
					</a>
				</div>
			</div>
		</div>

		<figure class="hero__art">
			<?php
			/*
			 * Ilustracja tylko w WebP — obrazek z przezroczystym tłem, a PNG
			 * z alfą waży sześć razy więcej. WebP obsługują wszystkie
			 * przeglądarki od 2020 roku.
			 */
			?>
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/hero.webp' ) ); ?>"
				srcset="<?php echo esc_attr( get_theme_file_uri( 'assets/img/hero-maly.webp' ) . ' 820w, ' . get_theme_file_uri( 'assets/img/hero.webp' ) . ' 1400w' ); ?>"
				sizes="(min-width: 900px) 52vw, 92vw"
				width="1400" height="931"
				alt="<?php esc_attr_e( 'Dzieci w kręgu, każde z pluszakiem: misiem, zajączkiem, żabką, kotkiem, jeżykiem i wiewiórką', 'przedszkole' ); ?>"
				fetchpriority="high">
		</figure>

	</div>

	<?php
	get_template_part(
		'template-parts/fala',
		null,
		array(
			'ksztalt' => 'warstwy',
			'kolor'   => $przedszkole_kolor_pod_hero,
		)
	);
	?>
</section>

<?php if ( $przedszkole_jest_ogloszenie || $przedszkole_sa_wpisy ) : ?>
	<div class="section--miekka">

		<?php if ( $przedszkole_jest_ogloszenie ) : ?>
			<?php
			/*
			 * Nagłówek stały, bo treść bywa jednym akapitem bez tytułu, a sekcja
			 * bez nazwy to dla czytnika ekranu anonimowy region.
			 *
			 * Treść wypisana bez `wp_kses_post()`: to wynik `the_content()`,
			 * a przepuszczenie go jeszcze raz przez kses zdjęłoby osadzenia
			 * (mapa, film), które rdzeń wstawia sam.
			 */
			?>
			<section class="wrap ogloszenie" aria-labelledby="ogloszenie-tytul">
				<div class="section__head">
					<h2 id="ogloszenie-tytul"><?php esc_html_e( 'Ważne informacje', 'przedszkole' ); ?></h2>
				</div>
				<div class="entry__content ogloszenie__tresc">
					<?php echo $przedszkole_ogloszenie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $przedszkole_sa_wpisy ) : ?>
		<section class="wrap">
			<div class="section__head">
				<h2><?php esc_html_e( 'Aktualności', 'przedszkole' ); ?></h2>
				<?php
				// Link prowadzi tam, skąd pochodzą kafelki — do archiwum ogłoszeń,
				// nie do listy wszystkich wpisów.
				$przedszkole_kategoria = get_category_by_slug( 'ogloszenia' );
				if ( $przedszkole_kategoria ) :
					?>
					<a href="<?php echo esc_url( get_category_link( $przedszkole_kategoria ) ); ?>">
						<?php esc_html_e( 'Zobacz wszystkie', 'przedszkole' ); ?> →
					</a>
				<?php endif; ?>
			</div>

			<div class="cards">
				<?php
				while ( $przedszkole_aktualnosci->have_posts() ) :
					$przedszkole_aktualnosci->the_post();
					get_template_part( 'template-parts/card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php endif; ?>

		<?php
		if ( $przedszkole_sa_skroty ) {
			get_template_part(
				'template-parts/fala',
				null,
				array(
					'ksztalt' => 'skos',
					'kolor'   => $przedszkole_zolty,
				)
			);
		} else {
			get_template_part( 'template-parts/chmurki' );
		}
		?>
	</div>
<?php endif; ?>

<?php get_template_part( 'template-parts/skroty' ); ?>

<?php get_template_part( 'template-parts/dlaczego-my' ); ?>

<?php get_template_part( 'template-parts/skrzydla' ); ?>

<?php
get_footer();
