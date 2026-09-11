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
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'entry' ); ?>>

			<header class="page-header">
				<h1><?php the_title(); ?></h1>
			</header>

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
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
