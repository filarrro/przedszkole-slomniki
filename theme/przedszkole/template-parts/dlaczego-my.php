<?php
/**
 * Sekcja „Dlaczego my" — cztery powody, każdy z własną ilustracją.
 *
 * Ikony rysowane inline jako SVG: skalują się bez utraty jakości, nie generują
 * dodatkowych żądań i dziedziczą kolory z palety motywu. Są dekoracyjne —
 * każdą opisuje nagłówek pod nią, więc cały rysunek ma `aria-hidden`.
 *
 * Plamy pod ikonami mają cztery różne kształty, żeby rząd kafelków nie wyglądał
 * jak powielony szablon. Każda to gradient dwóch kolorów grup — tych samych,
 * co tęcza w logotypie, więc sekcja nie wprowadza nowej palety. Rząd idzie
 * w kolejności tęczy: błękit, zieleń, żółć, pomarańcz. Gradient łączy zawsze
 * barwy sąsiednie — dalsze od siebie dawały po drodze oliwkę i brąz.
 *
 * Treść siedzi w tablicy poniżej, a nie w Gutenbergu, bo każdy punkt jest
 * związany z konkretną ilustracją — edycja w edytorze rozjechałaby układ.
 *
 * @package Przedszkole
 */

defined( 'ABSPATH' ) || exit;

/*
 * Godziny otwarcia do potwierdzenia z przedszkolem — wartość tymczasowa.
 */
$przedszkole_powody = array(
	array(
		'ikona' => 'zegar',
		'tytul' => __( 'Dogodne godziny otwarcia', 'przedszkole' ),
		'opis'  => __( 'Codzienna opieka od 6:30 do 17:00 przez cały rok szkolny.', 'przedszkole' ),
		'od'    => '#6FBFE4',
		'do'    => '#3D2FB5',
		'plama' => 'M190 62C200 81 190 115 178 135C166 156 138 184 118 183C98 183 64 154 56 135C47 116 56 86 67 67C78 49 101 23 122 22C142 21 181 43 190 62Z',
	),
	array(
		'ikona' => 'globus',
		'tytul' => __( 'Nauka angielskiego', 'przedszkole' ),
		'opis'  => __( 'Zajęcia z języka angielskiego dwa razy w tygodniu, dostosowane do wieku dziecka.', 'przedszkole' ),
		'od'    => '#7BC45F',
		'do'    => '#6FBFE4',
		'plama' => 'M184 127C178 147 149 180 130 182C111 184 83 158 69 139C55 120 39 87 46 69C53 51 91 32 111 31C132 30 156 47 169 63C181 79 191 107 184 127Z',
	),
	array(
		'ikona' => 'drzewa',
		'tytul' => __( 'Blisko przyrody', 'przedszkole' ),
		'opis'  => __( 'Spokojne otoczenie wśród zieleni i własny ogród z placem zabaw.', 'przedszkole' ),
		'od'    => '#EFC63F',
		'do'    => '#7BC45F',
		'plama' => 'M154 167C137 175 105 164 86 153C67 141 38 114 38 96C39 77 69 52 90 41C111 31 147 22 164 33C180 43 191 81 190 104C188 126 171 159 154 167Z',
	),
	array(
		'ikona' => 'miska',
		'tytul' => __( 'Zdrowe posiłki', 'przedszkole' ),
		'opis'  => __( 'Pełne wyżywienie przez cały dzień, z uwzględnieniem diet poszczególnych dzieci.', 'przedszkole' ),
		'od'    => '#EFA45C',
		'do'    => '#E4705C',
		'plama' => 'M143 40C165 48 196 69 199 88C202 107 182 139 164 154C145 169 107 185 90 178C72 170 61 132 58 110C55 87 58 52 72 40C86 29 122 32 143 40Z',
	),
);
?>

<section class="powody">
	<div class="wrap">

		<div class="powody__naglowek">
			<h2><?php esc_html_e( 'Dlaczego my?', 'przedszkole' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'Dzieci uczą się u nas przez zabawę, w bezpiecznym miejscu i pod opieką osób, które znają je po imieniu. Oto, co wyróżnia nasze przedszkole.',
					'przedszkole'
				);
				?>
			</p>
		</div>

		<ul class="powody__lista">
			<?php foreach ( $przedszkole_powody as $przedszkole_i => $przedszkole_powod ) : ?>
				<?php $przedszkole_grad = 'pz-powod-' . $przedszkole_i; ?>
				<li class="powod">

					<svg class="powod__ikona" viewBox="0 0 240 210" aria-hidden="true" focusable="false">
						<defs>
							<linearGradient id="<?php echo esc_attr( $przedszkole_grad ); ?>" x1="0" y1="0" x2="1" y2="1">
								<stop offset="0" stop-color="<?php echo esc_attr( $przedszkole_powod['od'] ); ?>"/>
								<stop offset="1" stop-color="<?php echo esc_attr( $przedszkole_powod['do'] ); ?>"/>
							</linearGradient>
						</defs>

						<path fill="url(#<?php echo esc_attr( $przedszkole_grad ); ?>)"
							d="<?php echo esc_attr( $przedszkole_powod['plama'] ); ?>"/>

						<g fill="none" stroke="#FFFFFF" stroke-width="4"
							stroke-linecap="round" stroke-linejoin="round">
							<?php
							switch ( $przedszkole_powod['ikona'] ) {
								case 'zegar':
									?>
									<circle cx="120" cy="100" r="44"/>
									<path d="M120 66v8M154 100h8M120 134v8M78 100h8"/>
									<path d="M120 76v24l22 12"/>
									<?php
									break;

								case 'globus':
									?>
									<circle cx="120" cy="100" r="44"/>
									<ellipse cx="120" cy="100" rx="18" ry="44"/>
									<path d="M76 100h88"/>
									<path d="M87 73c22 12 46 12 66 0M87 127c22-12 46-12 66 0"/>
									<?php
									break;

								case 'drzewa':
									?>
									<path d="M84 140h72"/>
									<circle cx="104" cy="78" r="28"/>
									<path d="M104 140v-34"/>
									<circle cx="148" cy="106" r="17"/>
									<path d="M148 140v-17"/>
									<?php
									break;

								case 'miska':
									?>
									<path d="M72 110h96"/>
									<path d="M82 112a38 38 0 0 0 76 0"/>
									<path d="M114 104c-17-7-25-23-21-40 16 2 29 17 21 40z"/>
									<path d="M133 104c15-9 21-25 15-39-16 4-25 21-15 39z"/>
									<circle cx="151" cy="86" r="10"/>
									<?php
									break;
							}
							?>
						</g>

						<?php
						/* Drobiny wokół plamy — inne dla każdego kafelka, żeby rząd nie miał rytmu kalki.
						   Kolory z palety grup, zawsze inne niż plama obok. */
						switch ( $przedszkole_i % 4 ) {
							case 0:
								?>
								<circle cx="18" cy="52" r="5" fill="none" stroke="#6FBFE4" stroke-width="3"/>
								<circle cx="218" cy="43" r="6" fill="#7BC45F"/>
								<circle cx="222" cy="152" r="4" fill="#E88BAE"/>
								<circle cx="98" cy="198" r="3" fill="#EFC63F"/>
								<?php
								break;

							case 1:
								?>
								<circle cx="210" cy="42" r="5" fill="none" stroke="#E88BAE" stroke-width="3"/>
								<circle cx="26" cy="69" r="5" fill="#EFC63F"/>
								<circle cx="30" cy="170" r="3" fill="#7BC45F"/>
								<circle cx="206" cy="185" r="4" fill="#E8961C"/>
								<?php
								break;

							case 2:
								?>
								<circle cx="20" cy="128" r="6" fill="none" stroke="#6FBFE4" stroke-width="3"/>
								<circle cx="40" cy="47" r="5" fill="#E88BAE"/>
								<circle cx="204" cy="52" r="4" fill="#E8961C"/>
								<circle cx="196" cy="186" r="3" fill="#EFC63F"/>
								<?php
								break;

							default:
								?>
								<circle cx="222" cy="107" r="5" fill="none" stroke="#7BC45F" stroke-width="3"/>
								<circle cx="212" cy="52" r="5" fill="#EFC63F"/>
								<circle cx="34" cy="34" r="4" fill="#6FBFE4"/>
								<circle cx="40" cy="196" r="3" fill="#E8961C"/>
								<?php
						}
						?>
					</svg>

					<h3><?php echo esc_html( $przedszkole_powod['tytul'] ); ?></h3>
					<p><?php echo esc_html( $przedszkole_powod['opis'] ); ?></p>

				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>
