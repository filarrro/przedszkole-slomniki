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
			/*
			 * Litera NIE jest `aria-hidden`, a dopisek zaczyna sie mala litera:
			 * nazwa przycisku sklada sie na „A+ wiekszy tekst", czyli zawiera
			 * widoczna etykiete. Tego wymaga WCAG 2.5.3 (Label in Name) -
			 * inaczej osoba sterujaca glosem mowi „kliknij A plus" i nic
			 * sie nie dzieje, bo nazwa brzmiala tylko „Większy tekst".
			 */
			$przedszkole_rozmiary = array(
				'normalny'    => array( 'A', __( 'standardowy rozmiar tekstu', 'przedszkole' ) ),
				'duzy'        => array( 'A+', __( 'większy tekst', 'przedszkole' ) ),
				'bardzo-duzy' => array( 'A++', __( 'największy tekst', 'przedszkole' ) ),
			);

			foreach ( $przedszkole_rozmiary as $przedszkole_klucz => $przedszkole_opis ) {
				printf(
					'<button type="button" class="rozmiar-tekstu__przycisk" data-przedszkole-rozmiar="%1$s" aria-pressed="%2$s">%3$s<span class="screen-reader-text"> %4$s</span></button>',
					esc_attr( $przedszkole_klucz ),
					'normalny' === $przedszkole_klucz ? 'true' : 'false',
					esc_html( $przedszkole_opis[0] ),
					esc_html( $przedszkole_opis[1] )
				);
			}
			?>
		</div>

		<?php
		/*
		 * `aria-pressed` wychodzi z serwera na „false" z tego samego powodu
		 * co przy rozmiarze: strona jest cache'owalna. Prostuje je skrypt.
		 * Tekst przycisku nie zmienia sie po wlaczeniu - „Wysoki kontrast"
		 * nazywa funkcje, a stan niesie `aria-pressed` i wyglad przycisku.
		 */
		?>
		<button type="button" class="kontrast" data-przedszkole-kontrast="wysoki" aria-pressed="false">
			<span class="kontrast__ikona" aria-hidden="true"></span>
			<?php esc_html_e( 'Wysoki kontrast', 'przedszkole' ); ?>
		</button>
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
