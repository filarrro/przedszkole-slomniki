<?php
/**
 * Pojedyncza aktualność.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();

$przedszkole_strona_wpisow = get_option( 'page_for_posts' );
?>

<div class="wrap">

	<?php if ( $przedszkole_strona_wpisow ) : ?>
		<nav class="powrot" aria-label="<?php esc_attr_e( 'Ścieżka', 'przedszkole' ); ?>">
			<a href="<?php echo esc_url( get_permalink( $przedszkole_strona_wpisow ) ); ?>">
				← <?php echo esc_html( get_the_title( $przedszkole_strona_wpisow ) ); ?>
			</a>
		</nav>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'entry' ); ?>>

			<header class="page-header">
				<h1><?php the_title(); ?></h1>
				<p class="entry__meta">
					<?php przedszkole_etykieta(); ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php
					/*
					 * Autorem bywa konto grupowe („Żabki”), a nie osoba — dlatego
					 * sama nazwa, bez odnośnika do archiwum autora i bez awatara
					 * pobieranego z Gravatara.
					 *
					 * Gdy nazwa autora powtarza etykietę obok (wpis Żabek
					 * w kategorii „Żabki”), zostaje sama etykieta — dwa razy to
					 * samo słowo w jednej linijce to szum, nie informacja.
					 */
					$przedszkole_autor    = get_the_author();
					$przedszkole_kategoria = przedszkole_grupa_wpisu();

					if ( ! $przedszkole_kategoria || $przedszkole_kategoria->name !== $przedszkole_autor ) :
						?>
						<span class="entry__autor">
							<?php
							/* translators: %s: nazwa autora wpisu. */
							printf( esc_html__( 'napisali: %s', 'przedszkole' ), esc_html( $przedszkole_autor ) );
							?>
						</span>
						<?php
					endif;
					?>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="entry__thumb"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

			<div class="entry__content">
				<?php
				the_content();

				// Wpis podzielony znacznikiem „następna strona” — bez tego nie da się przejść dalej.
				wp_link_pages(
					array(
						'before' => '<nav class="pagination"><div class="nav-links">',
						'after'  => '</div></nav>',
					)
				);
				?>
			</div>

		</article>

		<?php
		/*
		 * Sąsiednie wpisy w obrębie kategorii: wpis Misiów prowadzi do Misiów,
		 * ogłoszenie do ogłoszenia, poradnik do poradnika.
		 *
		 * Wcześniej nawigacja szła po całym serwisie chronologicznie, z myślą
		 * o rodzicu, który przy okazji zajrzy do innej grupy. W praktyce dawało
		 * to sąsiedztwa bez sensu — najwyraźniej przy kącikach specjalistów,
		 * bo daty poradników są rozsypane po całej historii serwisu
		 * i „Seplenienie międzyzębowe" wypadało obok „Wioski Indiańskiej
		 * Kotków". Kto czyta wpis swojej grupy, chce następny wpis tej grupy;
		 * do pozostałych prowadzi filtr nad listą aktualności.
		 *
		 * Wystarczy `in_same_term` — każdy wpis w serwisie ma dokładnie jedną
		 * kategorię (sprawdzone 2026-09-16, 452 na 452), więc nie ma tu
		 * niejednoznaczności. Gdyby ktoś nadał wpisowi dwie, rdzeń policzy
		 * sąsiadów z obu i nawigacja przeskoczy między kategoriami.
		 */
		the_post_navigation(
			array(
				'class'               => 'pagination--wpisy',
				'prev_text'           => '<span class="pagination__kierunek">' . esc_html__( 'Poprzedni wpis', 'przedszkole' ) . '</span><span class="pagination__tytul">%title</span>',
				'next_text'           => '<span class="pagination__kierunek">' . esc_html__( 'Następny wpis', 'przedszkole' ) . '</span><span class="pagination__tytul">%title</span>',
				'screen_reader_text'  => __( 'Sąsiednie wpisy', 'przedszkole' ),
				'aria_label'          => __( 'Sąsiednie wpisy', 'przedszkole' ),
				'in_same_term'        => true,
				'taxonomy'            => 'category',
			)
		);
		?>
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
