<?php
/**
 * Ostatnie aktualności grupy pod treścią strony grupy.
 *
 * Strony grup opisują wychowawczynie i rozkład dnia, a to, co się u nich
 * dzieje, żyje we wpisach. Bez tej listy rodzic musiałby szukać wpisów grupy
 * w filtrze nad aktualnościami — tutaj widzi je od razu.
 *
 * Wpisy wiążemy z grupą przez slug: strona „Misie” i kategoria „Misie” mają
 * ten sam slug, więc nie trzeba niczego łączyć ręcznie w panelu.
 *
 * @package Przedszkole
 * @param string $args['slug'] Slug grupy, ten sam co slug kategorii.
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_slug = isset( $args['slug'] ) ? (string) $args['slug'] : '';

if ( ! in_array( $przedszkole_slug, przedszkole_grupy(), true ) ) {
	return;
}

$przedszkole_kategoria = get_category_by_slug( $przedszkole_slug );

if ( ! $przedszkole_kategoria ) {
	return;
}

$przedszkole_wpisy = new WP_Query(
	array(
		'post_type'           => 'post',
		'cat'                 => $przedszkole_kategoria->term_id,
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $przedszkole_wpisy->have_posts() ) {
	return;
}
?>

<section class="section">

	<div class="section__head">
		<h2><?php esc_html_e( 'Aktualności grupy', 'przedszkole' ); ?></h2>
		<a href="<?php echo esc_url( get_category_link( $przedszkole_kategoria ) ); ?>">
			<?php esc_html_e( 'Zobacz więcej', 'przedszkole' ); ?> →
		</a>
	</div>

	<div class="cards">
		<?php
		while ( $przedszkole_wpisy->have_posts() ) :
			$przedszkole_wpisy->the_post();
			get_template_part( 'template-parts/card' );
		endwhile;
		wp_reset_postdata();
		?>
	</div>

</section>
