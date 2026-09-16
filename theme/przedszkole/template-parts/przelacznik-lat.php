<?php
/**
 * Wybór rocznika nad listą wpisów.
 *
 * Zwykły formularz GET: bez JavaScriptu, z klawiatury, z czytnikiem ekranu.
 * Przycisk „Pokaż” zamiast zdarzenia `change`, bo lista, która przeskakuje
 * przy strzałce w dół, jest dla użytkownika klawiatury pułapką.
 *
 * Paginacji nie obsługujemy sami — `paginate_links()` scala parametry
 * z bieżącego adresu do odnośników stron.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

if ( ! przedszkole_rok_aktywny() ) {
	return;
}

$przedszkole_lata = przedszkole_lata_szkolne();

// Przy jednym roczniku przełącznik nie ma czego przełączać.
if ( count( $przedszkole_lata ) < 2 ) {
	return;
}

$przedszkole_wybrany = przedszkole_rok_z_zapytania();

if ( is_category() ) {
	$przedszkole_adres = get_category_link( get_queried_object_id() );
} else {
	$przedszkole_strona = get_option( 'page_for_posts' );
	$przedszkole_adres  = $przedszkole_strona ? get_permalink( $przedszkole_strona ) : home_url( '/' );
}
?>
<nav class="lata" aria-label="<?php esc_attr_e( 'Wybór roku szkolnego', 'przedszkole' ); ?>">
	<form class="lata__form" method="get" action="<?php echo esc_url( $przedszkole_adres ); ?>">
		<label class="lata__etykieta" for="rok">
			<?php esc_html_e( 'Rok szkolny', 'przedszkole' ); ?>
		</label>

		<select class="lata__wybor" name="rok" id="rok">
			<?php foreach ( $przedszkole_lata as $przedszkole_rok ) : ?>
				<option value="<?php echo esc_attr( $przedszkole_rok ); ?>" <?php selected( $przedszkole_rok, $przedszkole_wybrany ); ?>>
					<?php echo esc_html( przedszkole_rok_z_slug( $przedszkole_rok ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button class="lata__przycisk" type="submit">
			<?php esc_html_e( 'Pokaż', 'przedszkole' ); ?>
		</button>
	</form>
</nav>
