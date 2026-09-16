# -*- coding: utf-8 -*-
"""Przepisanie kafelkow kadry z kolumn na blok `przedszkole/osoba`.

Kafelek byl wzorcem na zwyklych `core/columns`: uklad siedzial w tresci
strony, inicjaly trzeba bylo wpisac recznie dwa razy, a szerokosc kolumny
i klasa wariantu stylu daly sie skasowac jednym klikiem. Blok zdejmuje to
z tresci - zostaja zdjecie, imie, tytul i biogram.

    python3 tools/kadra_na_blok.py              # podglad, nic nie zapisuje
    python3 tools/kadra_na_blok.py --zastosuj   # wgrywa do WordPressa

Idempotentny przez brak roboty: skrypt szuka starych kolumn, a po migracji
zadnych juz nie ma, wiec drugie uruchomienie konczy sie "Nic do zrobienia".

Jednorazowy. Trzymamy go w repo jako zapis tego, co poszlo do bazy - nowe
osoby dodaje sie juz wylacznie blokiem w edytorze.
"""
import json, pathlib, re, subprocess, sys, tempfile

STRONA = 33          # strona "Kadra"
ZASTOSUJ = '--zastosuj' in sys.argv

# Caly kafelek: od otwarcia kolumn z klasa wariantu do ich zamkniecia.
# Kolumny w kafelku sie nie zagniezdzaja, wiec pierwsze domkniecie jest
# tym wlasciwym.
KAFELEK = re.compile( r'<!-- wp:columns (\{[^\n]*?\}) -->\n(.*?)<!-- /wp:columns -->', re.S )

# Prawa kolumna - ostatnia w kafelku, stad `rfind` zamiast wyszukiwania wprost.
KOLUMNA = re.compile(
	r'<!-- wp:column[^\n]*-->\n<div class="wp-block-column[^"]*"[^>]*>(.*)</div>\n<!-- /wp:column -->',
	re.S )

NAGLOWEK = re.compile(
	r'<!-- wp:heading \{"level":3\} -->\n<h3 class="wp-block-heading">(.*?)</h3>\n'
	r'<!-- /wp:heading -->\s*', re.S )
TYTUL = re.compile(
	r'<!-- wp:paragraph \{"className":"kafelek-osoby__tytul"\} -->\n'
	r'<p class="kafelek-osoby__tytul">(.*?)</p>\n<!-- /wp:paragraph -->\s*', re.S )
OBRAZEK = re.compile( r'<!-- wp:image \{"id":(\d+)[^\n]*-->\n.*?<img src="([^"]+)"', re.S )


def wp(*args):
	"""wp-cli w kontenerze. stderr zostaje widoczny, zeby nie zgubic bledow."""
	return subprocess.run(
		['ddev', 'exec', 'wp', '--path=wp', *args],
		check=True, capture_output=True, text=True, stdin=subprocess.DEVNULL,
	).stdout


def blok(imie, tytul, wyrownanie, zdjecie, biogram):
	"""Znaczniki bloku. Atrybuty rowne domyslnym z `block.json` pomijamy -
	Gutenberg zapisuje je tak samo, wiec tresc po edycji w panelu nie bedzie
	sie rozjezdzac z ta z migracji."""
	atrybuty = {'imie': imie, 'tytul': tytul}
	if zdjecie:
		atrybuty['zdjecieId'], atrybuty['zdjecieUrl'] = zdjecie
	if wyrownanie != 'srodek':
		atrybuty['wyrownanie'] = wyrownanie

	return '<!-- wp:przedszkole/osoba %s -->\n%s\n<!-- /wp:przedszkole/osoba -->' % (
		json.dumps( atrybuty, ensure_ascii=False, separators=(',', ':') ),
		biogram.strip(),
	)


def przepisz(naglowek_kolumn, wnetrze):
	"""Jeden kafelek: kolumny -> blok. Zwraca None, gdy uklad nie pasuje -
	lepiej zostawic taki kafelek nietkniety i wypisac ostrzezenie, niz
	wgrac do bazy polprodukt."""
	wyrownanie = 'gora' if '"verticalAlignment":"top"' in naglowek_kolumn else 'srodek'

	granica = wnetrze.rfind( '<!-- wp:column' )
	lewa, prawa_chunk = wnetrze[:granica], wnetrze[granica:]

	prawa = KOLUMNA.search( prawa_chunk )
	if not prawa:
		return None

	tresc = prawa.group(1)

	imie = NAGLOWEK.search( tresc )
	if not imie:
		return None
	tresc = NAGLOWEK.sub( '', tresc, count=1 )

	tytul = TYTUL.search( tresc )
	tresc = TYTUL.sub( '', tresc, count=1 )

	obrazek = OBRAZEK.search( lewa )
	zdjecie = ( int( obrazek.group(1) ), obrazek.group(2) ) if obrazek else None

	return blok(
		imie.group(1).strip(),
		tytul.group(1).strip() if tytul else '',
		wyrownanie,
		zdjecie,
		tresc,
	)


def main():
	tresc = wp( 'post', 'get', str(STRONA), '--field=content' )
	wynik, koniec, zmian, pominiete = [], 0, 0, 0

	for dopasowanie in KAFELEK.finditer( tresc ):
		if 'is-style-kafelek-osoby' not in dopasowanie.group(1):
			continue

		nowy = przepisz( dopasowanie.group(1), dopasowanie.group(2) )
		if nowy is None:
			print( '  POMINIETY  kafelek o nieznanym ukladzie (znak %d)' % dopasowanie.start(),
			       file=sys.stderr )
			pominiete += 1
			continue

		wynik.append( tresc[koniec:dopasowanie.start()] )
		wynik.append( nowy )
		koniec = dopasowanie.end()
		zmian += 1

		imie = re.search( r'"imie":"([^"]*)"', nowy )
		print( '  ZMIANA     %-28s %s' % (
			imie.group(1) if imie else '?',
			'ze zdjeciem' if '"zdjecieId"' in nowy else 'inicjaly' ), file=sys.stderr )

	if not zmian:
		print( 'Nic do zrobienia.', file=sys.stderr )
		return

	wynik.append( tresc[koniec:] )
	nowa_tresc = ''.join( wynik )

	if not ZASTOSUJ:
		print( nowa_tresc )
		print( '\nPodglad: %d kafelkow do przepisania, %d pominietych.'
		       '\nUruchom z --zastosuj, zeby wgrac.' % (zmian, pominiete), file=sys.stderr )
		return

	with tempfile.NamedTemporaryFile( 'w', suffix='.html', dir='.',
	                                  delete=False, encoding='utf-8' ) as f:
		f.write( nowa_tresc )
		sciezka = pathlib.Path( f.name )
	try:
		wp( 'post', 'update', str(STRONA), sciezka.name )
	finally:
		sciezka.unlink()
	print( 'Wgrane do strony %d. Przepisanych kafelkow: %d, pominietych: %d'
	       % (STRONA, zmian, pominiete), file=sys.stderr )


main()
