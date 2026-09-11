<?php
/**
 * Nagłówek strony.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'Przejdź do treści', 'przedszkole' ); ?></a>

<div class="rainbow-bar" aria-hidden="true"></div>

<header class="site-header">
	<div class="wrap site-header__inner">

		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<p class="site-title">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<?php bloginfo( 'name' ); ?>
						<?php
						$przedszkole_tagline = get_bloginfo( 'description', 'display' );
						if ( $przedszkole_tagline ) {
							echo '<span>' . esc_html( $przedszkole_tagline ) . '</span>';
						}
						?>
					</a>
				</p>
				<?php
			}
			?>
		</div>

		<?php if ( has_nav_menu( 'primary' ) ) : ?>
			<button class="nav-toggle" aria-expanded="false" aria-controls="main-nav">
				<span class="nav-toggle__bars" aria-hidden="true"></span>
				<?php esc_html_e( 'Menu', 'przedszkole' ); ?>
			</button>

			<nav class="main-nav" id="main-nav" aria-label="<?php esc_attr_e( 'Menu główne', 'przedszkole' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 2,
					)
				);
				?>
			</nav>
		<?php endif; ?>

	</div>
</header>

<main class="site-main" id="main">
