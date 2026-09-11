<?php
/**
 * Kafelek aktualności na listach.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<span class="card__media">
			<?php the_post_thumbnail( 'przedszkole-karta', array( 'loading' => 'lazy' ) ); ?>
		</span>
	<?php endif; ?>

	<div class="card__body">
		<div class="card__meta">
			<?php przedszkole_etykieta(); ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>

		<h3 class="card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>

		<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
			<p class="card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
	</div>

</article>
