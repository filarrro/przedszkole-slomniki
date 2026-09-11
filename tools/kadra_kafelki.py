# -*- coding: utf-8 -*-
"""Przepisanie strony Kadra na kafelki osob.

Czyta obecna tresc strony 33, parsuje naglowki i akapity, i sklada ja
na nowo w kafelki `is-style-kafelek-osoby`. Nic nie wpisujemy z pamieci -
biogramy ida z oryginalu, poprawiane tylko o liste literowek nizej.

Sekcja administracji i obslugi zostaje lista akapitow - 11 osob opisanych
jedna linijka nie ma czym wypelnic kafelka.

    python3 tools/kadra_kafelki.py              # podglad na stdout
    python3 tools/kadra_kafelki.py --zastosuj   # wgrywa do WordPressa

Uruchomienie drugi raz na juz przepisanej stronie nic nie da - parser
szuka naglowkow h3 z akapitami pod nimi, a w kafelkach siedza one
w kolumnach. Zrodlem jest tresc przed przepisaniem.
"""
import re, subprocess, sys, tempfile, pathlib

STRONA = 33          # strona „Kadra"
ZASTOSUJ = '--zastosuj' in sys.argv

# --- Literowki po migracji z Joomli (decyzja: poprawiamy ortografie) -------
LITEROWKI = [
	('studa magisterskie', 'studia magisterskie'),
	('podyplomowe - „ Organizacja i zarządzanie oświatą”', 'podyplomowe „Organizacja i zarządzanie oświatą”'),
	('optymistką,kocham', 'optymistką, kocham'),
	('krzyżowek', 'krzyżówek'),
	('stwarzac', 'stwarzać'),
	('Krakowie gdzie zdobyła', 'Krakowie, gdzie zdobyła'),
	('”Kotki”', '„Kotki”'),
	('(Wiewiorki)', '(Wiewiórki)'),
	('5 latków', '5-latków'),
	('3 - latków', '3-latków'),
	('3-4 latków', '3-4-latków'),
	('min. biżuterię', 'm.in. biżuterię'),
	('"Ignatianum"', '„Ignatianum”'),
	('"Edukacja przedszkolna z przygotowaniem pedagogicznym"', '„Edukacja przedszkolna z przygotowaniem pedagogicznym”'),
	('"Nowoczesna Szkoła Francuska Technik C. Freineta”', '„Nowoczesna Szkoła Francuska Technik C. Freineta”'),
	('życie-staram', 'życie — staram'),
	('Usmiech', 'Uśmiech'),
	('z 38 stażem pracy', 'z 38-letnim stażem pracy'),
	('Akademii Krakowskiej specjalność', 'Akademii Krakowskiej, specjalność'),
	(',,Humanitas"', '„Humanitas”'),
	('Wolnej chwili interesuje się', 'W wolnej chwili interesuję się'),
	('Marii Curie- Skłodowskiej', 'Marii Curie-Skłodowskiej'),
	('Plolicealną', 'Policealną'),
	('im.Jana', 'im. Jana'),
	('Prywatnie jest mamą czterech chłopców, z którymi codziennie, wspólnie odkrywam świat.',
	 'Prywatnie jestem mamą czterech chłopców, z którymi codziennie wspólnie odkrywam świat.'),
	('a wolnych chwilach najchętniej', 'a w wolnych chwilach najchętniej'),
	('"Pracuję w najstarszej', '„Pracuję w najstarszej'),
	('w ogrodzie."', 'w ogrodzie.”'),
	('"Praca z dziećmi daje', '„Praca z dziećmi daje'),
	('oraz psychologią."', 'oraz psychologią.”'),
	('<br />', ''),
]

# --- Linia wykształcenia (decyzja: druga linia kafelka) -------------------
WYKSZTALCENIE = {
	'Małgorzata Wojasińska': 'Pedagogika przedszkolna · organizacja i zarządzanie oświatą',
	'Ewa Dolaś':            'Pedagogika przedszkolna z zarządzaniem oświatą',
	'Aneta Gumula':         'Pedagogika społeczno-opiekuńcza · pedagogika przedszkolna',
	'Agnieszka Kurek':      'Pedagogika przedszkolna i wczesnoszkolna',
	'Agata Dudek':          'Wychowanie przedszkolne z nauczaniem języka angielskiego',
	'Marta Gądek':          'Edukacja przedszkolna z przygotowaniem pedagogicznym',
	'Agnieszka Nagło':      'Pedagogika wczesnoszkolna i przedszkolna · oligofrenopedagogika',
	'Bożena Piwowarska':    'Pedagogika przedszkolna i nauczanie początkowe',
	'Mariola Zębala':       'Pedagogika resocjalizacyjna',
	'Agnieszka Jastrząb':   'Edukacja wczesnoszkolna i przedszkolna z podstawami logopedii',
	'Agnieszka Tekiela':    'Logopedia · neurologopedia',
	'Joanna Natkaniec':     'Pedagogika specjalna · terapia pedagogiczna z arteterapią',
	'Joanna Karnia':        'Nauki o rodzinie · pedagogika specjalna — logopedia',
	'Małgorzata Rożek':     'Pedagogika społeczno-opiekuńcza i przedszkolna',
}

# --- Zdjecia (na razie ilustrowane awatary zastepcze) --------------------
# Kto ma zdjecie, dostaje je w miejsce inicjalow. Sciezki wzgledem katalogu
# projektu - `wp media import` w kontenerze liczy je od /var/www/html.
ZDJECIA = {
	'Ewa Dolaś':       'media/kadra/awatar-1.jpg',
	'Aneta Gumula':    'media/kadra/awatar-2.jpg',
	'Agnieszka Kurek': 'media/kadra/awatar-3.jpg',
}

BLOK = re.compile(r'<!-- wp:(heading|paragraph)[^>]*-->\s*(.*?)\s*<!-- /wp:\1 -->', re.S)


def czysty(html):
	"""Tekst bez znacznikow - do inicjalow i liczenia dlugosci."""
	return re.sub(r'<[^>]+>', '', html).strip()


# Kolejnosc polskiego alfabetu - `sorted` po samym Unicode wrzucilby
# nazwiska z ogonkami na koniec (Gądek za Gumulą, Zębala za Tekielą).
ALFABET = 'aąbcćdeęfghijklłmnńoópqrsśtuvwxyzźż'


def klucz_alfabetyczny(imie_nazwisko):
	"""Nazwisko to ostatni czlon - wszystkie osoby maja je na koncu."""
	nazwisko = imie_nazwisko.split()[-1].lower()
	return [ALFABET.index(z) if z in ALFABET else len(ALFABET) for z in nazwisko]


def inicjaly(imie_nazwisko):
	czlony = [c for c in imie_nazwisko.split() if c and c[0].isupper()]
	return ''.join(c[0] for c in czlony[:2])


def zalacznik(plik):
	"""Zwraca (ID, URL) obrazka w bibliotece mediow. Jesli go tam nie ma,
	wrzuca plik - ale tylko przy `--zastosuj`, zeby podglad nic nie zmienial."""
	slug = pathlib.Path(plik).stem
	csv = wp('post', 'list', '--post_type=attachment', '--fields=ID,post_name',
	         '--format=csv', '--posts_per_page=-1')
	for linia in csv.splitlines()[1:]:
		idp, nazwa = linia.split(',')[:2]
		if nazwa.strip('"') == slug:
			return idp, wp('post', 'get', idp, '--field=guid').strip()

	if not ZASTOSUJ:
		return '0', 'URL-PO-IMPORCIE'

	idp = wp('media', 'import', plik, '--porcelain').strip()
	print('zaimportowane: %s -> ID %s' % (plik, idp), file=sys.stderr)
	return idp, wp('post', 'get', idp, '--field=guid').strip()


def awatar(osoba):
	"""Zawartosc lewej kolumny: zdjecie, a jesli go nie ma - inicjaly.

	Awatary sa ilustracjami zastepczymi, nie portretami tych osob, wiec
	`alt` zostaje puste: obrazek nic nie wnosi, a nazwisko stoi obok
	w naglowku. Prawdziwe zdjecie dostanie opis przy wstawianiu.

	Inicjaly ida dwa razy: raz normalnie, raz jako powiekszony znak wodny
	obciety krawedzia kola. CSS nie odczyta tekstu elementu, wiec duplikat
	musi stac w tresci; `aria-hidden` trzyma go poza drzewem dostepnosci."""
	plik = ZDJECIA.get(osoba['nazwisko'])
	if plik:
		idp, url = zalacznik(plik)
		return (
			'<!-- wp:image {"id":%(id)s,"sizeSlug":"full","linkDestination":"none"} -->\n'
			'<figure class="wp-block-image size-full">'
			'<img src="%(url)s" alt="" class="wp-image-%(id)s"/></figure>\n'
			'<!-- /wp:image -->'
		) % dict(id=idp, url=url)

	return (
		'<!-- wp:paragraph {"className":"kafelek-osoby__inicjaly"} -->\n'
		'<p class="kafelek-osoby__inicjaly">%(ini)s'
		'<span class="kafelek-osoby__znak-wodny" aria-hidden="true">%(ini)s</span></p>\n'
		'<!-- /wp:paragraph -->'
	) % dict(ini=osoba['inicjaly'])


def kafelek(osoba):
	"""Kolumny z wariantem stylu. Dlugi biogram - zdjecie na gorze,
	krotki - wysrodkowane, jak we wzorcu."""
	# Prog 900 znakow to mniej wiecej dwie wysokosci zdjecia. Ponizej zdjecie
	# wysrodkowane wzgledem tekstu wyglada jak we wzorcu; powyzej wisialoby
	# w polowie dlugiego biogramu, wiec idzie na gore.
	dlugosc = sum(len(czysty(p)) for p in osoba['biogram'])
	pion = 'top' if dlugosc > 900 else 'center'
	akapity = '\n\n'.join(
		'<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % p for p in osoba['biogram']
	)
	return (
		'<!-- wp:columns {"verticalAlignment":"%(pion)s","className":"is-style-kafelek-osoby"} -->\n'
		'<div class="wp-block-columns are-vertically-aligned-%(pion)s is-style-kafelek-osoby">'
		'<!-- wp:column {"verticalAlignment":"%(pion)s","width":"38%%"} -->\n'
		'<div class="wp-block-column is-vertically-aligned-%(pion)s" style="flex-basis:38%%">'
		'%(awatar)s</div>\n'
		'<!-- /wp:column -->\n\n'
		'<!-- wp:column {"verticalAlignment":"%(pion)s"} -->\n'
		'<div class="wp-block-column is-vertically-aligned-%(pion)s"><!-- wp:heading {"level":3} -->\n'
		'<h3 class="wp-block-heading">%(tytul)s</h3>\n'
		'<!-- /wp:heading -->\n\n'
		'<!-- wp:paragraph {"className":"kafelek-osoby__tytul"} -->\n'
		'<p class="kafelek-osoby__tytul">%(wyksztalcenie)s</p>\n'
		'<!-- /wp:paragraph -->\n\n'
		'%(akapity)s</div>\n'
		'<!-- /wp:column --></div>\n'
		'<!-- /wp:columns -->'
	) % dict(pion=pion, awatar=awatar(osoba), tytul=osoba['tytul'],
	         wyksztalcenie=osoba['wyksztalcenie'], akapity=akapity)


def wp(*args, wejscie=None):
	"""wp-cli w kontenerze. `--format=csv` gdzieindziej, tu i tak czysty tekst;
	stderr zostawiamy widoczny, zeby nie zgubic prawdziwych bledow."""
	return subprocess.run(
		['ddev', 'exec', 'wp', '--path=wp', *args],
		check=True, capture_output=True, text=True, stdin=subprocess.DEVNULL,
	).stdout


def main():
	tresc = wp('post', 'get', str(STRONA), '--field=content')

	if 'is-style-kafelek-osoby' in tresc:
		raise SystemExit(
			'Strona %d jest juz przepisana na kafelki. Parser oczekuje ukladu\n'
			'naglowek h3 + akapity, a nie kolumn - uruchomienie drugi raz\n'
			'zniszczyloby tresc. Przywroc wersje sprzed przepisania.' % STRONA
		)
	for zle, dobrze in LITEROWKI:
		tresc = tresc.replace(zle, dobrze)

	sekcje = []          # [(naglowek_h2, [osoby] albo None), ...]
	biezaca = None
	osoba = None
	surowe = []          # akapity sekcji administracyjnej

	for m in BLOK.finditer(tresc):
		rodzaj, wnetrze = m.group(1), m.group(2)
		poziom = re.search(r'"level":(\d)', m.group(0))
		poziom = int(poziom.group(1)) if poziom else 2

		if rodzaj == 'heading' and poziom == 2:
			biezaca = {'naglowek': czysty(wnetrze), 'osoby': [], 'akapity': []}
			sekcje.append(biezaca)
			osoba = None
			continue

		if rodzaj == 'heading' and poziom == 3:
			nazwa = czysty(wnetrze)
			osoba = {'tytul': nazwa, 'biogram': []}
			biezaca['osoby'].append(osoba)
			continue

		# akapit
		tekst = wnetrze
		tekst = re.sub(r'^<p>|</p>$', '', tekst).strip()
		tekst = re.sub(r'<em>\s+', '<em>', tekst)
		tekst = re.sub(r'\s+</em>', '</em>', tekst)
		tekst = re.sub(r'\s{2,}', ' ', tekst).strip()

		if biezaca['naglowek'] == 'Dyrektor' and osoba is None:
			# Imie dyrektora stoi w akapicie, nie w naglowku.
			osoba = {'tytul': czysty(tekst), 'biogram': []}
			biezaca['osoby'].append(osoba)
			continue

		if osoba is not None:
			osoba['biogram'].append(tekst)
		else:
			biezaca['akapity'].append(tekst)

	# Inicjaly i linia wyksztalcenia.
	braki = []
	for s in sekcje:
		for o in s['osoby']:
			bez_stopnia = re.sub(r'^(mgr|dr|lic\.)\s+', '', o['tytul']).strip()
			o['nazwisko'] = bez_stopnia
			o['inicjaly'] = inicjaly(bez_stopnia)
			o['wyksztalcenie'] = WYKSZTALCENIE.get(bez_stopnia, '')
			if not o['wyksztalcenie']:
				braki.append(bez_stopnia)
	if braki:
		raise SystemExit('Brak linii wyksztalcenia dla: %s' % ', '.join(braki))

	# Alfabetycznie po nazwisku, w obrebie sekcji. Dyrektor jest jeden,
	# a administracja i obsluga to akapity, nie kafelki - obu to nie dotyczy.
	for s in sekcje:
		s['osoby'].sort(key=lambda o: klucz_alfabetyczny(o['nazwisko']))

	czesci = []
	for s in sekcje:
		czesci.append('<!-- wp:heading {"level":2} -->\n<h2 class="wp-block-heading">%s</h2>\n<!-- /wp:heading -->' % s['naglowek'])
		for o in s['osoby']:
			czesci.append(kafelek(o))
		for a in s['akapity']:
			czesci.append('<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % a)

	nowa = '\n\n'.join(czesci) + '\n'

	if ZASTOSUJ:
		with tempfile.NamedTemporaryFile('w', suffix='.html', dir='.', delete=False, encoding='utf-8') as f:
			f.write(nowa)
			plik = pathlib.Path(f.name)
		try:
			wp('post', 'update', str(STRONA), plik.name)
		finally:
			plik.unlink()
		print('Wgrane do strony %d.' % STRONA)
	else:
		print(nowa)

	print('sekcje:', file=sys.stderr)
	for s in sekcje:
		print('  %-36s kafelki: %2d   akapity: %2d'
		      % (s['naglowek'], len(s['osoby']), len(s['akapity'])), file=sys.stderr)
	print('razem kafelkow:', sum(len(s['osoby']) for s in sekcje), file=sys.stderr)


main()
