<?php
/**
 * Kafelki podstron danego działu.
 *
 * Strony w serwisie są zagnieżdżone (Grupy → Misie, Dla rodziców → Jadłospis).
 * Strona-rodzic bywa pusta, bo cała treść siedzi w dzieciach — bez tej listy
 * byłby to ślepy zaułek, z którego wychodzi się tylko przez menu.
 *
 * Lista jest wyliczana z drzewa stron, więc nowa podstrona pojawia się tu sama,
 * bez dotykania kodu i bez pamiętania o dopisaniu linku.
 *
 * @package Przedszkole
 * @param int $args['rodzic'] ID strony nadrzędnej.
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_rodzic = (int) ( $args['rodzic'] ?? 0 );

if ( ! $przedszkole_rodzic ) {
	return;
}

$przedszkole_podstrony = get_pages(
	array(
		'parent'      => $przedszkole_rodzic,
		'sort_column' => 'menu_order,post_title',
	)
);

if ( empty( $przedszkole_podstrony ) ) {
	return;
}

$przedszkole_grupy = przedszkole_grupy();
?>

<nav class="podstrony" aria-labelledby="podstrony-naglowek">

	<h2 class="podstrony__naglowek" id="podstrony-naglowek">
		<?php esc_html_e( 'W tym dziale', 'przedszkole' ); ?>
	</h2>

	<ul class="podstrony__lista">
		<?php foreach ( $przedszkole_podstrony as $przedszkole_podstrona ) : ?>
			<?php
			// Strony grup dostają kolor swojej grupy — te same barwy co etykiety wpisów.
			$przedszkole_modyfikator = in_array( $przedszkole_podstrona->post_name, $przedszkole_grupy, true )
				? ' podstrona--' . $przedszkole_podstrona->post_name
				: '';
			?>
			<li class="podstrona<?php echo esc_attr( $przedszkole_modyfikator ); ?>">
				<a href="<?php echo esc_url( get_permalink( $przedszkole_podstrona ) ); ?>">
					<?php echo esc_html( get_the_title( $przedszkole_podstrona ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

</nav>
