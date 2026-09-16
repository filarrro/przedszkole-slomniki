<?php
/**
 * Pojedyncza strona.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="wrap">

	<?php get_template_part( 'template-parts/sciezka' ); ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<?php
		/*
		 * Strony grup zaczynają się kartą grupy, która sama niesie <h1>
		 * z nazwą — powtórzony nagłówek dałby dwa <h1> na stronie.
		 * Sprawdzamy treść, a nie rodzica strony: jeśli ktoś skasuje kartę
		 * w edytorze, zwykły nagłówek wraca i strona nie zostaje bez <h1>.
		 */
		$przedszkole_ma_karte = false !== strpos( get_the_content(), 'karta-grupy__nazwa' );
		?>
		<article <?php post_class( 'entry' ); ?>>

			<?php if ( ! $przedszkole_ma_karte ) : ?>
				<header class="page-header">
					<h1><?php the_title(); ?></h1>
				</header>
			<?php endif; ?>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="entry__thumb"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

			<div class="entry__content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<nav class="pagination"><div class="nav-links">',
						'after'  => '</div></nav>',
					)
				);
				?>
			</div>

		</article>

		<?php get_template_part( 'template-parts/wpisy-kategorii', null, array( 'slug' => get_post_field( 'post_name' ) ) ); ?>

		<?php get_template_part( 'template-parts/podstrony', null, array( 'rodzic' => get_the_ID() ) ); ?>
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
