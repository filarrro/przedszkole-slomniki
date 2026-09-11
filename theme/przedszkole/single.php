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

			<div class="entry__content"><?php the_content(); ?></div>

		</article>

		<?php
		/*
		 * Sąsiednie wpisy chronologicznie, bez ograniczania do kategorii —
		 * rodzic czytający wpis Misiów równie chętnie zajrzy do Żabek.
		 */
		the_post_navigation(
			array(
				'class'               => 'pagination--wpisy',
				'prev_text'           => '<span class="pagination__kierunek">' . esc_html__( 'Poprzedni wpis', 'przedszkole' ) . '</span><span class="pagination__tytul">%title</span>',
				'next_text'           => '<span class="pagination__kierunek">' . esc_html__( 'Następny wpis', 'przedszkole' ) . '</span><span class="pagination__tytul">%title</span>',
				'screen_reader_text'  => __( 'Sąsiednie wpisy', 'przedszkole' ),
				'aria_label'          => __( 'Sąsiednie wpisy', 'przedszkole' ),
				'in_same_term'        => false,
			)
		);
		?>
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
