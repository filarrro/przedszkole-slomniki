<?php
/**
 * Strona główna.
 *
 * Treść w sekcji powitalnej pochodzi ze zwykłej strony WordPressa edytowanej
 * w Gutenbergu — pracownicy przedszkola mogą ją zmieniać sami. Grafika,
 * chmurki i sekcja aktualności są dokładane przez motyw.
 *
 * Kolejność: powitanie → aktualności → „Dlaczego my". Fala z chmurkami ma
 * kolor sekcji, która po niej następuje, więc zapytanie o wpisy leci przed
 * hero — bez wpisów fala pod hero prowadziłaby do nieistniejącego tła.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();

$przedszkole_aktualnosci = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

$przedszkole_sa_wpisy = $przedszkole_aktualnosci->have_posts();
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
		'template-parts/chmurki',
		null,
		$przedszkole_sa_wpisy ? array( 'kolor' => '#F3F8F2' ) : null
	);
	?>
</section>

<?php if ( $przedszkole_sa_wpisy ) : ?>
	<section class="section--miekka section--chmury">
		<div class="wrap">
			<div class="section__head">
				<h2><?php esc_html_e( 'Aktualności', 'przedszkole' ); ?></h2>
				<?php
				$przedszkole_strona_wpisow = get_option( 'page_for_posts' );
				if ( $przedszkole_strona_wpisow ) :
					?>
					<a href="<?php echo esc_url( get_permalink( $przedszkole_strona_wpisow ) ); ?>">
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

		<?php get_template_part( 'template-parts/chmurki' ); ?>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/dlaczego-my' ); ?>

<?php
get_footer();
