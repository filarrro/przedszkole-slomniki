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

<?php
/*
 * Pasek dostepnosci. Ukryty, dopoki skrypt w naglowku nie oznaczy dokumentu
 * klasa `ma-js` - bez JavaScriptu przyciski nic by nie robily, a przycisk,
 * ktory nic nie robi, jest gorszy niz jego brak.
 */
?>
<div class="pasek-dostepnosci">
	<div class="wrap pasek-dostepnosci__inner">
		<div class="rozmiar-tekstu" role="group" aria-label="<?php esc_attr_e( 'Rozmiar tekstu', 'przedszkole' ); ?>">
			<span class="rozmiar-tekstu__etykieta" aria-hidden="true"><?php esc_html_e( 'Rozmiar tekstu', 'przedszkole' ); ?></span>
			<?php
			/*
			 * `aria-pressed` wychodzi z serwera zawsze na „normalny", bo strona
			 * jest cache'owalna i serwer nie zna preferencji przegladarki.
			 * Stan widoczny rysuje CSS z atrybutu `data-rozmiar` na <html>
			 * (sekcja 27), wiec nic nie mruga; `aria-pressed` prostuje skrypt.
			 */
			$przedszkole_rozmiary = array(
				'normalny'    => array( 'A', __( 'Standardowy rozmiar tekstu', 'przedszkole' ) ),
				'duzy'        => array( 'A+', __( 'Większy tekst', 'przedszkole' ) ),
				'bardzo-duzy' => array( 'A++', __( 'Największy tekst', 'przedszkole' ) ),
			);

			foreach ( $przedszkole_rozmiary as $przedszkole_klucz => $przedszkole_opis ) {
				printf(
					'<button type="button" class="rozmiar-tekstu__przycisk" data-przedszkole-rozmiar="%1$s" aria-pressed="%2$s"><span aria-hidden="true">%3$s</span><span class="screen-reader-text">%4$s</span></button>',
					esc_attr( $przedszkole_klucz ),
					'normalny' === $przedszkole_klucz ? 'true' : 'false',
					esc_html( $przedszkole_opis[0] ),
					esc_html( $przedszkole_opis[1] )
				);
			}
			?>
		</div>
	</div>
</div>

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
