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

	<?php if ( have_posts() ) : ?>

		<div class="cards cards--lista">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', null, array( 'uklad' => 'pozioma' ) );
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

		<div class="notice">
			<h2><?php esc_html_e( 'Brak wpisów', 'przedszkole' ); ?></h2>
			<p><?php esc_html_e( 'Nie ma tu jeszcze żadnych treści.', 'przedszkole' ); ?></p>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
