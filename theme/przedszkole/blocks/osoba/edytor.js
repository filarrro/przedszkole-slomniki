/**
 * Blok "Osoba" - widok w edytorze.
 *
 * Zwykly JavaScript, bez JSX i bez kroku budowania: `wp.element.createElement`
 * zamiast znacznikow, zaleznosci wyliczone recznie w `edytor.asset.php`.
 *
 * Kafelek w edytorze ma te same klasy co na froncie, wiec `style.css`
 * (dolaczony przez `add_editor_style`) rysuje go bez osobnej stylistyki.
 * Wyjatkiem sa przyciski wyboru zdjecia - te istnieja tylko w panelu
 * i ich wyglad siedzi w `assets/css/editor.css`.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;

	var useBlockProps = wp.blockEditor.useBlockProps;
	var useInnerBlocksProps = wp.blockEditor.useInnerBlocksProps;
	var InnerBlocks = wp.blockEditor.InnerBlocks;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;

	var Button = wp.components.Button;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;

	// Biogram to bloki podrzedne: akapity wolno formatowac jak wszedzie indziej,
	// ale lista dozwolonych trzyma w kafelku sam tekst - bez kolumn i obrazkow,
	// ktore rozwalilyby uklad.
	var SZABLON = [ [ 'core/paragraph', { placeholder: __( 'Biogram — wykształcenie, staż pracy, grupa, zainteresowania.', 'przedszkole' ) } ] ];
	var DOZWOLONE = [ 'core/paragraph', 'core/list', 'core/quote' ];

	/**
	 * Inicjaly: pierwsze litery dwoch pierwszych czlonow pisanych wielka litera.
	 * Stopien zapisany malymi literami ("mgr", "dr") wypada sam.
	 *
	 * Ta sama regula stoi drugi raz w `inc/blok-osoba.php` - PHP liczy inicjaly
	 * na froncie, JavaScript w podgladzie. Zmieniajac jedno, popraw drugie.
	 */
	function inicjaly( imie ) {
		var czyste = String( imie || '' ).replace( /<[^>]*>/g, ' ' );
		var litery = '';

		czyste.split( /\s+/ ).forEach( function ( czlon ) {
			if ( litery.length >= 2 || ! czlon ) {
				return;
			}
			var pierwsza = czlon.charAt( 0 );
			if ( /\p{Lu}/u.test( pierwsza ) ) {
				litery += pierwsza;
			}
		} );

		return litery;
	}

	function portret( atrybuty ) {
		if ( atrybuty.zdjecieUrl ) {
			return el( 'img', {
				src: atrybuty.zdjecieUrl,
				alt: '',
				className: 'kafelek-osoby__zdjecie',
			} );
		}

		var ini = inicjaly( atrybuty.imie );

		return el(
			'p',
			{ className: 'kafelek-osoby__inicjaly' },
			ini,
			el( 'span', { className: 'kafelek-osoby__znak-wodny', 'aria-hidden': 'true' }, ini )
		);
	}

	/**
	 * Przyciski pod zdjeciem. Wybor zdjecia stoi tu, a nie w pasku narzedzi
	 * bloku: pracownik szukajacy "gdzie wgrac fotke" patrzy na kolo, nie na
	 * pasek nad kafelkiem.
	 */
	function przyciskiZdjecia( atrybuty, ustaw ) {
		return el(
			MediaUploadCheck,
			null,
			el( 'div', { className: 'kafelek-osoby__sterowanie' },
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: atrybuty.zdjecieId,
					onSelect: function ( media ) {
						ustaw( { zdjecieId: media.id, zdjecieUrl: media.url } );
					},
					render: function ( otwieracz ) {
						return el(
							Button,
							{ variant: 'secondary', size: 'small', onClick: otwieracz.open },
							atrybuty.zdjecieUrl
								? __( 'Zmień zdjęcie', 'przedszkole' )
								: __( 'Dodaj zdjęcie', 'przedszkole' )
						);
					},
				} ),
				atrybuty.zdjecieUrl
					? el(
						Button,
						{
							variant: 'tertiary',
							size: 'small',
							isDestructive: true,
							onClick: function () {
								ustaw( { zdjecieId: 0, zdjecieUrl: '' } );
							},
						},
						__( 'Usuń zdjęcie', 'przedszkole' )
					)
					: null
			)
		);
	}

	wp.blocks.registerBlockType( 'przedszkole/osoba', {
		edit: function ( props ) {
			var a = props.attributes;
			var ustaw = props.setAttributes;
			var wyrownanie = 'gora' === a.wyrownanie ? 'gora' : 'srodek';

			var blockProps = useBlockProps( {
				className: 'kafelek-osoby kafelek-osoby--' + wyrownanie,
			} );

			var innerProps = useInnerBlocksProps(
				{ className: 'kafelek-osoby__biogram' },
				{ template: SZABLON, allowedBlocks: DOZWOLONE }
			);

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Układ kafelka', 'przedszkole' ) },
						el( SelectControl, {
							label: __( 'Wyrównanie w pionie', 'przedszkole' ),
							value: wyrownanie,
							options: [
								{ label: __( 'Do środka', 'przedszkole' ), value: 'srodek' },
								{ label: __( 'Do góry', 'przedszkole' ), value: 'gora' },
							],
							help: __( 'Przy długim biogramie zdjęcie wygląda lepiej wyrównane do góry.', 'przedszkole' ),
							onChange: function ( wartosc ) {
								ustaw( { wyrownanie: wartosc } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'div', { className: 'kafelek-osoby__portret' },
						portret( a ),
						przyciskiZdjecia( a, ustaw )
					),
					el( 'div', { className: 'kafelek-osoby__opis' },
						el( RichText, {
							tagName: 'h3',
							className: 'kafelek-osoby__imie',
							value: a.imie,
							allowedFormats: [],
							disableLineBreaks: true,
							placeholder: __( 'Imię i nazwisko', 'przedszkole' ),
							onChange: function ( wartosc ) {
								ustaw( { imie: wartosc } );
							},
						} ),
						el( RichText, {
							tagName: 'p',
							className: 'kafelek-osoby__tytul',
							value: a.tytul,
							allowedFormats: [],
							disableLineBreaks: true,
							placeholder: __( 'Tytuł lub wykształcenie', 'przedszkole' ),
							onChange: function ( wartosc ) {
								ustaw( { tytul: wartosc } );
							},
						} ),
						el( 'div', innerProps )
					)
				)
			);
		},

		// Blok jest dynamiczny - front rysuje `render.php`, w tresci strony
		// zostaja same bloki podrzedne z biogramem.
		save: function () {
			return el( InnerBlocks.Content );
		},
	} );
} )( window.wp );
