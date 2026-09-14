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

Zdjecie do usuniecia: skasuj linie i uruchom - kafelek wroci do inicjalow.
Skrypt przechodzi po wszystkich kafelkach, nie po samym slowniku, wiec brak
wpisu jest instrukcja „inicjaly", a nie „nie ruszaj". Sam zalacznik zostaje
w bibliotece mediow - usuwa sie go osobno (`wp post delete <ID> --force`).
"""
import pathlib, re, subprocess, sys, tempfile

STRONA = 33          # strona „Kadra"
ZASTOSUJ = '--zastosuj' in sys.argv

# Nazwisko dokladnie jak w naglowku h3, ale bez tytulu „mgr".
# Slownik jest zrodlem prawdy dla calej strony: kto tu jest, ma zdjecie,
# kogo tu nie ma, ma inicjaly. Usuniecie linii przywraca inicjaly.
ZDJECIA = {
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


def inicjaly(imie_nazwisko):
	"""Jak w `kadra_kafelki.py`: pierwsze litery dwoch pierwszych czlonow
	pisanych z wielkiej litery. Stopien „mgr" wypada sam."""
	czlony = [c for c in imie_nazwisko.split() if c and c[0].isupper()]
	return ''.join(c[0] for c in czlony[:2])


def osoby(tresc):
	"""Nazwiska z naglowkow kafelkow, w kolejnosci wystepowania."""
	return [
		re.sub(r'^mgr\s+', '', m.group(1)).strip()
		for m in re.finditer(r'<h3 class="wp-block-heading">([^<]*)</h3>', tresc)
	]


def inicjaly_bloku(imie_nazwisko):
	"""Inicjaly ida dwa razy: raz normalnie, raz jako powiekszony znak wodny
	obciety krawedzia kola. CSS nie odczyta tekstu elementu, wiec duplikat
	musi stac w tresci; `aria-hidden` trzyma go poza drzewem dostepnosci."""
	return (
		'<!-- wp:paragraph {"className":"kafelek-osoby__inicjaly"} -->\n'
		'<p class="kafelek-osoby__inicjaly">%(ini)s'
		'<span class="kafelek-osoby__znak-wodny" aria-hidden="true">%(ini)s</span></p>\n'
		'<!-- /wp:paragraph -->'
	) % dict(ini=inicjaly(imie_nazwisko))


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

	for osoba in osoby(tresc):
		plik = ZDJECIA.get(osoba)
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

		if plik:
			idp, url = zalacznik(plik)
			nowa = obrazek(idp, url)
			opis = plik
		else:
			nowa = inicjaly_bloku(osoba)
			opis = 'inicjaly'

		if kolumna.group(2).strip() == nowa.strip():
			continue

		tresc = tresc[:kolumna.start(2)] + nowa + tresc[kolumna.end(2):]
		czym = 'inicjaly' if 'kafelek-osoby__inicjaly' in kolumna.group(2) else 'zdjecie'
		print('  ZMIANA     %-18s %s  (bylo: %s)' % (osoba, opis, czym), file=sys.stderr)
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
