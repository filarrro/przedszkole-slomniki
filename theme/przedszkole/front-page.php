<?php
/**
 * Strona główna.
 *
 * Treść w sekcji powitalnej pochodzi ze zwykłej strony WordPressa edytowanej
 * w Gutenbergu — pracownicy przedszkola mogą ją zmieniać sami. Grafika,
 * fale i sekcja aktualności są dokładane przez motyw.
 *
 * Kolejność: powitanie → aktualności → „Na skróty" → „Dlaczego my" → hasło
 * ze zdjęciem.
 *
 * Fala należy do sekcji, która ją poprzedza, ale ma kolor tej, która po niej
 * następuje. Dlatego zapytanie o wpisy leci przed hero, a kolory liczymy z góry:
 * przy pustej stronie (bez wpisów, bez menu skrótów) fala pod hero prowadziłaby
 * do nieistniejącego tła.
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

$przedszkole_sa_wpisy  = $przedszkole_aktualnosci->have_posts();
$przedszkole_sa_skroty = has_nav_menu( 'skroty' );

$przedszkole_zolty = '#F9E229';   // Tło sekcji „Na skróty”.
$przedszkole_mieta = '#F3F8F2';   // Górny koniec gradientu pod aktualnościami.
$przedszkole_tlo   = 'var(--wp--preset--color--base)';

// Kolor fali pod hero to tło pierwszej sekcji, która faktycznie się pojawi.
if ( $przedszkole_sa_wpisy ) {
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
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
			endif;
			?>
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

<?php if ( $przedszkole_sa_wpisy ) : ?>
	<section class="section--miekka">
		<div class="wrap">
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
		</div>

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
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/skroty' ); ?>

<?php get_template_part( 'template-parts/dlaczego-my' ); ?>

<?php get_template_part( 'template-parts/skrzydla' ); ?>

<?php
get_footer();
