<?php
/**
 * Falista krawędź z chmurkami — przejście między sekcjami.
 *
 * Rysowana jako SVG, więc skaluje się bez utraty jakości i waży kilkaset bajtów.
 *
 * @package Przedszkole
 * @param string $args['kolor'] Kolor wypełnienia fali (CSS).
 * @param bool   $args['gora']  Czy fala ma być odwrócona (wchodzi od góry).
 */

defined( 'ABSPATH' ) || exit;

$przedszkole_kolor = $args['kolor'] ?? 'var(--wp--preset--color--base)';
$przedszkole_gora  = ! empty( $args['gora'] );
?>
<div class="chmurki<?php echo $przedszkole_gora ? ' chmurki--gora' : ''; ?>" aria-hidden="true">
	<svg viewBox="0 0 1440 90" preserveAspectRatio="none" focusable="false">
		<path fill="<?php echo esc_attr( $przedszkole_kolor ); ?>"
			d="M0 44c70-26 121 14 174 14s77-40 144-40 86 35 148 35 90-40 160-40 100 37 170 37 97-35 165-35 93 33 163 33 104-30 174-30 91 21 142 30H1440v52H0z"/>
	</svg>
</div>
