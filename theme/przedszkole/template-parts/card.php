<?php
/**
 * Kafelek aktualności na listach.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/* Uklad `pozioma` - lista aktualnosci: zdjecie obok tresci. Domyslnie kafelek
   pionowy, uzywany na stronie glownej i w wynikach wyszukiwania. */
$uklad = isset( $args['uklad'] ) ? $args['uklad'] : '';
$klasy = 'pozioma' === $uklad ? array( 'card', 'card--pozioma' ) : array( 'card' );

/* Poziom naglowka kafelka zalezy od tego, co stoi nad nim na stronie.
   Na liscie aktualnosci kafelki ida wprost pod H1, wiec tytul jest H2.
   Na stronie glownej poprzedza je naglowek sekcji "Aktualnosci" (H2),
   wiec tytul schodzi na H3. Przeskok z H1 na H3 to blad struktury
   dokumentu - czytnik ekranu zglasza brakujacy poziom. Wyglad sie nie
   zmienia, bo style siedza na klasie `card__title`, nie na znaczniku. */
$poziom = isset( $args['poziom'] ) ? (int) $args['poziom'] : 3;
$poziom = min( 6, max( 2, $poziom ) );
?>
<article <?php post_class( $klasy ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<span class="card__media">
			<?php the_post_thumbnail( 'przedszkole-karta', array( 'loading' => 'lazy' ) ); ?>
		</span>
	<?php else : ?>
		<?php /* Zastepnik trzyma te sama proporcje co zdjecie, wiec kafelki bez zdjecia nie rozjezdzaja sie w rzedzie. */ ?>
		<span class="card__media card__media--brak" aria-hidden="true">
			<img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/brak-zdjecia.webp' ) ); ?>"
				alt="" width="1200" height="805" loading="lazy" decoding="async">
		</span>
	<?php endif; ?>

	<div class="card__body">
		<div class="card__meta">
			<?php przedszkole_etykieta(); ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>

		<h<?php echo (int) $poziom; ?> class="card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h<?php echo (int) $poziom; ?>>

		<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
			<p class="card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</div>

</article>
