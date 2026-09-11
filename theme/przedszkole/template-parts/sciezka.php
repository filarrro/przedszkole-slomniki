<?php
/**
 * Ścieżka powrotu na stronie zagnieżdżonej.
 *
 * Nie pełny okruszkowy szlak — jeden odnośnik do strony nadrzędnej. Na drzewie
 * o dwóch poziomach pełna ścieżka powtarzałaby tylko nazwę serwisu i dział.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_rodzic = wp_get_post_parent_id( get_queried_object_id() );

if ( ! $przedszkole_rodzic ) {
	return;
}
?>

<nav class="powrot" aria-label="<?php esc_attr_e( 'Ścieżka', 'przedszkole' ); ?>">
	<a href="<?php echo esc_url( get_permalink( $przedszkole_rodzic ) ); ?>">
		← <?php echo esc_html( get_the_title( $przedszkole_rodzic ) ); ?>
	</a>
</nav>
