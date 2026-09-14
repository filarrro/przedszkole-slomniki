<?php
/**
 * Wyniki wyszukiwania.
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
			/* translators: %s: wyszukiwana fraza. */
			printf( esc_html__( 'Wyniki dla: %s', 'przedszkole' ), '<em>' . esc_html( get_search_query() ) . '</em>' );
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<div class="cards">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card', null, array( 'poziom' => 2 ) );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'class'    => 'pagination',
				'mid_size' => 1,
			)
		);
		?>

	<?php else : ?>

		<div class="notice">
			<h2><?php esc_html_e( 'Nic nie znaleziono', 'przedszkole' ); ?></h2>
			<p><?php esc_html_e( 'Spróbuj wpisać inne słowo.', 'przedszkole' ); ?></p>
			<?php get_search_form(); ?>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
