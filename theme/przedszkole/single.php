<?php
/**
 * Pojedyncza aktualność.
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
				<p class="entry__meta">
					<?php przedszkole_etykieta(); ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="entry__thumb"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

			<div class="entry__content"><?php the_content(); ?></div>

		</article>
		<?php
	endwhile;
	?>
</div>

<?php
get_footer();
