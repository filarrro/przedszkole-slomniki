<?php
/**
 * Drobne funkcje pomocnicze motywu.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slugi grup przedszkolnych. Każda ma własny kolor w palecie.
 *
 * @return string[]
 */
function przedszkole_grupy() {
	return array( 'wiewiorki', 'zabki', 'zajaczki', 'misie', 'kotki', 'jezyki' );
}

/**
 * Zwraca pierwszą kategorię wpisu, która jest grupą przedszkolną.
 *
 * @param int|null $post_id ID wpisu.
 * @return WP_Term|null
 */
function przedszkole_grupa_wpisu( $post_id = null ) {
	$terms = get_the_category( $post_id );

	if ( empty( $terms ) ) {
		return null;
	}

	$grupy = przedszkole_grupy();

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, $grupy, true ) ) {
			return $term;
		}
	}

	return $terms[0];
}

/**
 * Wypisuje etykietę kategorii wpisu, pokolorowaną jeśli to grupa.
 *
 * @param int|null $post_id ID wpisu.
 */
function przedszkole_etykieta( $post_id = null ) {
	$term = przedszkole_grupa_wpisu( $post_id );

	if ( ! $term ) {
		return;
	}

	$modyfikator = in_array( $term->slug, przedszkole_grupy(), true ) ? ' pill--' . $term->slug : '';

	printf(
		'<a class="pill%1$s" href="%2$s">%3$s</a>',
		esc_attr( $modyfikator ),
		esc_url( get_category_link( $term ) ),
		esc_html( $term->name )
	);
}
