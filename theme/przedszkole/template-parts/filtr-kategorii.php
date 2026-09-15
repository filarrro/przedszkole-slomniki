<?php
/**
 * Filtr aktualności po kategorii.
 *
 * Zwykłe odnośniki do archiwów kategorii, które WordPress i tak już generuje —
 * bez JavaScriptu i bez własnych zapytań. Dzięki temu filtr działa z paginacją,
 * z wyłączonym JS i da się wysłać komuś odnośnik do konkretnej grupy.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_kategorie = get_categories(
	array(
		'hide_empty' => true,
	)
);

if ( count( $przedszkole_kategorie ) < 2 ) {
	return;
}

/*
 * Kolejnosc: najpierw grupy w kolejnosci z `przedszkole_grupy()` (od
 * najmlodszej do najstarszej - tak samo jak na stronie „Grupy”), potem
 * reszta alfabetycznie, tak jak zwrocil je WordPress.
 */
$przedszkole_grupy = przedszkole_grupy();

usort(
	$przedszkole_kategorie,
	static function ( $a, $b ) use ( $przedszkole_grupy ) {
		$poz_a = array_search( $a->slug, $przedszkole_grupy, true );
		$poz_b = array_search( $b->slug, $przedszkole_grupy, true );

		$poz_a = false === $poz_a ? count( $przedszkole_grupy ) : $poz_a;
		$poz_b = false === $poz_b ? count( $przedszkole_grupy ) : $poz_b;

		return $poz_a <=> $poz_b;
	}
);

$przedszkole_strona_wpisow  = get_option( 'page_for_posts' );
$przedszkole_adres_wszystko = $przedszkole_strona_wpisow ? get_permalink( $przedszkole_strona_wpisow ) : home_url( '/' );
$przedszkole_biezaca        = is_category() ? (int) get_queried_object_id() : 0;
?>
<nav class="filtr" aria-label="<?php esc_attr_e( 'Filtrowanie aktualności po kategorii', 'przedszkole' ); ?>">
	<ul class="filtr__lista">
		<li>
			<a class="filtr__link<?php echo $przedszkole_biezaca ? '' : ' is-aktywny'; ?>"
				href="<?php echo esc_url( $przedszkole_adres_wszystko ); ?>"
				<?php echo $przedszkole_biezaca ? '' : ' aria-current="page"'; ?>>
				<?php esc_html_e( 'Wszystkie', 'przedszkole' ); ?>
			</a>
		</li>

		<?php foreach ( $przedszkole_kategorie as $przedszkole_kategoria ) : ?>
			<?php
			$przedszkole_aktywna     = $przedszkole_biezaca === (int) $przedszkole_kategoria->term_id;
			$przedszkole_modyfikator = in_array( $przedszkole_kategoria->slug, $przedszkole_grupy, true ) ? ' filtr__link--' . $przedszkole_kategoria->slug : '';
			?>
			<li>
				<a class="filtr__link<?php echo esc_attr( $przedszkole_modyfikator ); ?><?php echo $przedszkole_aktywna ? ' is-aktywny' : ''; ?>"
					href="<?php echo esc_url( get_category_link( $przedszkole_kategoria ) ); ?>"
					<?php echo $przedszkole_aktywna ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $przedszkole_kategoria->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
