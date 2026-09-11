# -*- coding: utf-8 -*-
"""Wstawienie zdjec osob w kafelki na stronie Kadra.

`kadra_kafelki.py` przepisal strone raz i wiecej sie nie uruchomi - parser
oczekuje ukladu sprzed przepisania. Zdjecia przychodza pozniej i pojedynczo,
wiec dokladamy je tym skryptem: podmienia zawartosc lewej kolumny kafelka,
czyli inicjaly albo poprzednie zdjecie, na wskazany plik.

    python3 tools/kadra_zdjecie.py              # podglad, nic nie zapisuje
    python3 tools/kadra_zdjecie.py --zastosuj   # wgrywa do WordPressa

Idempotentny: powtorne uruchomienie z tym samym slownikiem nic nie zmienia.

Wyrownanie kolumn (`top` albo `center`) zalezy od dlugosci biogramu, nie od
zdjecia - zostawiamy je nietkniete, inaczej rozjechalby sie uklad kafelka.

Nowa osoba ze zdjeciem: dopisz linie do ZDJECIA i uruchom. Sciezki licza sie
od katalogu projektu - `wp media import` w kontenerze startuje w /var/www/html.
"""
import pathlib, re, subprocess, sys, tempfile

STRONA = 33          # strona „Kadra"
ZASTOSUJ = '--zastosuj' in sys.argv

# Nazwisko dokladnie jak w naglowku h3, ale bez tytulu „mgr".
ZDJECIA = {
	'Ewa Dolaś':       'media/kadra/awatar-1.jpg',
	'Aneta Gumula':    'media/kadra/awatar-2.jpg',
	'Agnieszka Kurek': 'media/kadra/awatar-3.jpg',
	'Agata Dudek':     'media/kadra/awatar-4.jpg',
	'Marta Gądek':     'media/kadra/awatar-5.jpg',
}

# Lewa kolumna kafelka: od otwarcia diva z flex-basis do jego zamkniecia.
KOLUMNA = re.compile(
	r'(<div class="wp-block-column is-vertically-aligned-(?:top|center)"'
	r' style="flex-basis:38%">)(.*?)(</div>\n<!-- /wp:column -->)', re.S )


def wp(*args):
	"""wp-cli w kontenerze. stderr zostaje widoczny, zeby nie zgubic bledow."""
	return subprocess.run(
		['ddev', 'exec', 'wp', '--path=wp', *args],
		check=True, capture_output=True, text=True, stdin=subprocess.DEVNULL,
	).stdout


def zalacznik(plik):
	"""(ID, URL) obrazka w bibliotece mediow; importuje go, jesli go tam nie ma,
	ale tylko przy `--zastosuj` - podglad niczego nie wgrywa."""
	slug = pathlib.Path(plik).stem
	csv = wp('post', 'list', '--post_type=attachment', '--fields=ID,post_name',
	         '--format=csv', '--posts_per_page=-1')
	for linia in csv.splitlines()[1:]:
		idp, nazwa = linia.split(',')[:2]
		if nazwa.strip('"') == slug:
			return idp, wp('post', 'get', idp, '--field=guid').strip()

	if not pathlib.Path(plik).exists():
		sys.exit('Brak pliku: %s' % plik)
	if not ZASTOSUJ:
		return '0', 'URL-PO-IMPORCIE'

	# --title wymuszone: bez niego WP bierze tytul z metadanych pliku, a z niego
	# wylicza slug. Zrzuty ekranu niosa w EXIF „Screenshot", wiec zalacznik
	# ladowal pod slugiem `screenshot` i ponowne wyszukanie go nie znajdowalo.
	idp = wp('media', 'import', plik, '--title=' + slug, '--porcelain').strip()
	print('zaimportowane: %s -> ID %s' % (plik, idp), file=sys.stderr)
	return idp, wp('post', 'get', idp, '--field=guid').strip()


def obrazek(idp, url):
	"""alt puste celowo: zdjecie stoi obok naglowka z imieniem i nazwiskiem,
	wiec dla czytnika ekranu nie niesie nic ponad to, co juz przeczytal."""
	return (
		'<!-- wp:image {"id":%(id)s,"sizeSlug":"full","linkDestination":"none"} -->\n'
		'<figure class="wp-block-image size-full">'
		'<img src="%(url)s" alt="" class="wp-image-%(id)s"/></figure>\n'
		'<!-- /wp:image -->'
	) % dict(id=idp, url=url)


def main():
	tresc = wp('post', 'get', str(STRONA), '--field=content')
	zmian = 0

	for osoba, plik in ZDJECIA.items():
		naglowek = re.search(
			r'<h3 class="wp-block-heading">[^<]*%s</h3>' % re.escape(osoba), tresc )
		if not naglowek:
			print('  POMINIETE  %-18s brak naglowka na stronie' % osoba, file=sys.stderr)
			continue

		# Kafelek zaczyna sie przed naglowkiem - lewa kolumna jest pierwsza.
		poczatek = tresc.rfind('<!-- wp:columns', 0, naglowek.start())
		kolumna = KOLUMNA.search(tresc, poczatek, naglowek.start())
		if not kolumna:
			print('  POMINIETE  %-18s nie znaleziono lewej kolumny' % osoba, file=sys.stderr)
			continue

		idp, url = zalacznik(plik)
		nowa = obrazek(idp, url)
		if kolumna.group(2) == nowa:
			print('  bez zmian  %-18s %s' % (osoba, plik), file=sys.stderr)
			continue

		tresc = tresc[:kolumna.start(2)] + nowa + tresc[kolumna.end(2):]
		czym = 'inicjaly' if 'kafelek-osoby__inicjaly' in kolumna.group(2) else 'inne zdjecie'
		print('  ZMIANA     %-18s %s  (bylo: %s)' % (osoba, plik, czym), file=sys.stderr)
		zmian += 1

	if not zmian:
		print('Nic do zrobienia.', file=sys.stderr)
		return

	if not ZASTOSUJ:
		print(tresc)
		print('\nPodglad. Uruchom z --zastosuj, zeby wgrac.', file=sys.stderr)
		return

	with tempfile.NamedTemporaryFile('w', suffix='.html', dir='.',
	                                 delete=False, encoding='utf-8') as f:
		f.write(tresc)
		sciezka = pathlib.Path(f.name)
	try:
		wp('post', 'update', str(STRONA), sciezka.name)
	finally:
		sciezka.unlink()
	print('Wgrane do strony %d. Zmienionych kafelkow: %d' % (STRONA, zmian), file=sys.stderr)


main()
