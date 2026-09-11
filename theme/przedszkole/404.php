<?php
/**
 * Strona błędu 404.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="wrap">
	<div class="notice">
		<h1><?php esc_html_e( 'Nie ma takiej strony', 'przedszkole' ); ?></h1>
		<p><?php esc_html_e( 'Strona mogła zostać przeniesiona albo usunięta. Spróbuj wyszukać to, czego szukasz.', 'przedszkole' ); ?></p>
		<?php get_search_form(); ?>
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Wróć na stronę główną', 'przedszkole' ); ?></a></p>
	</div>
</div>

<?php
get_footer();
