<?php
/**
 * Stopka strony.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<?php
get_template_part(
	'template-parts/fala',
	null,
	array(
		'ksztalt' => 'warstwy',
		'kolor'   => 'var(--wp--preset--color--primary)',
	)
);
?>

<footer class="site-footer">
	<div class="wrap site-footer__inner">

		<div>
			<h2><?php bloginfo( 'name' ); ?></h2>
			<?php if ( is_active_sidebar( 'footer-kontakt' ) ) : ?>
				<?php dynamic_sidebar( 'footer-kontakt' ); ?>
			<?php endif; ?>
		</div>

		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Menu w stopce', 'przedszkole' ); ?>">
				<h2><?php esc_html_e( 'Na skróty', 'przedszkole' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'footer-godziny' ) ) : ?>
			<div>
				<h2><?php esc_html_e( 'Godziny otwarcia', 'przedszkole' ); ?></h2>
				<?php dynamic_sidebar( 'footer-godziny' ); ?>
			</div>
		<?php endif; ?>

	</div>

	<div class="site-footer__bottom">
		<div class="wrap">
			&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
