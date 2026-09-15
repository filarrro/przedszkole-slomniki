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

$kategorie = get_categories(
	array(
		'hide_empty' => true,
	)
);

if ( count( $kategorie ) < 2 ) {
	return;
}

/*
 * Kolejnosc: najpierw grupy w kolejnosci z `przedszkole_grupy()` (od
 * najmlodszej do najstarszej - tak samo jak na stronie „Grupy”), potem
 * reszta alfabetycznie, tak jak zwrocil je WordPress.
 */
$grupy = przedszkole_grupy();

usort(
	$kategorie,
	static function ( $a, $b ) use ( $grupy ) {
		$poz_a = array_search( $a->slug, $grupy, true );
		$poz_b = array_search( $b->slug, $grupy, true );

		$poz_a = false === $poz_a ? count( $grupy ) : $poz_a;
		$poz_b = false === $poz_b ? count( $grupy ) : $poz_b;

		return $poz_a <=> $poz_b;
	}
);

$strona_wpisow = get_option( 'page_for_posts' );
$adres_wszystko = $strona_wpisow ? get_permalink( $strona_wpisow ) : home_url( '/' );
$biezaca        = is_category() ? (int) get_queried_object_id() : 0;
?>
<nav class="filtr" aria-label="<?php esc_attr_e( 'Filtrowanie aktualności po kategorii', 'przedszkole' ); ?>">
	<ul class="filtr__lista">
		<li>
			<a class="filtr__link<?php echo $biezaca ? '' : ' is-aktywny'; ?>"
				href="<?php echo esc_url( $adres_wszystko ); ?>"
				<?php echo $biezaca ? '' : ' aria-current="page"'; ?>>
				<?php esc_html_e( 'Wszystkie', 'przedszkole' ); ?>
			</a>
		</li>

		<?php foreach ( $kategorie as $kategoria ) : ?>
			<?php
			$aktywna     = $biezaca === (int) $kategoria->term_id;
			$modyfikator = in_array( $kategoria->slug, $grupy, true ) ? ' filtr__link--' . $kategoria->slug : '';
			?>
			<li>
				<a class="filtr__link<?php echo esc_attr( $modyfikator ); ?><?php echo $aktywna ? ' is-aktywny' : ''; ?>"
					href="<?php echo esc_url( get_category_link( $kategoria ) ); ?>"
					<?php echo $aktywna ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $kategoria->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
