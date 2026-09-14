#!/usr/bin/env python3
"""Migracja aktualności ze zrzutu Joomli do wpisów WordPressa.

Zakres: 440 artykułów opublikowanych od 2023-09-01 (lata szkolne 2023/24–2025/26).
Strony statyczne migruje osobny skrypt — `migracja_tresci.py`.

Czego tu NIE ma i dlaczego:

* **Obrazków wyróżniających.** Pliki leżały na FTP starej strony, którego już nie
  ma (domena oddaje 403 od hostingu). W zakresie 440 artykułów treść nie zawiera
  ani jednego odnośnika do konkretnego zdjęcia — jedyne obrazki to ścieżki z FTP
  i ikonka `galeria.png`. Wpisy idą bez obrazka, motyw pokazuje zastępnik.
  Gdyby przedszkole zmieniło zdanie: okładkę albumu da się wyciągnąć z Google
  Photos przez `og:image` — szczegóły w MIGRACJA.md.
* **Załączników.** 118 plików PDF/ODT też było na FTP. Skrypt wypisuje, które
  wpisy je miały, żeby dało się je wgrać ręcznie.

Skanowanie treści: stara strona była zaatakowana, więc każdy artykuł przechodzi
przez `skanuj()` przed konwersją. Trafienie blokujące = wpis pomijany.

Użycie:
    python3 tools/migracja_wpisow.py               # buduje plan, nic nie wgrywa
    python3 tools/migracja_wpisow.py --zastosuj    # wgrywa do WordPressa
    python3 tools/migracja_wpisow.py --limit 20    # próbka do obejrzenia
"""

import html
import json
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

# Konwersja HTML → bloki jest wspólna ze migracją stron statycznych.
from migracja_tresci import ARTYKULY_STRON, akapit, na_bloki, sql, tekst  # noqa: E402

PROJEKT = Path(__file__).resolve().parent.parent
WYNIK = PROJEKT / 'tools' / 'migracja_wpisow.json'

OD_DATY = '2023-09-01'

GRUPY = ('misie', 'zajaczki', 'zabki', 'kotki', 'wiewiorki', 'jezyki')

# Kategoria zbiorcza dla wszystkiego, co nie jest komunikatem grupy:
# aktualności ogólne, projekty, kącik logopedy, dokumenty. Taksonomia
# WordPressa to 6 grup + Ogłoszenia — patrz PLAN.md, Etap 4.
DOMYSLNA_KATEGORIA = 'ogloszenia'
DOMYSLNY_AUTOR = 'przedszkole'


# ------------------------------------------------------------------ skaner

# Sygnatury blokujące — kod wykonywalny albo wstrzyknięta struktura strony.
# Stara strona była zaatakowana, więc nie zakładamy, że treść jest czysta.
BLOKUJACE = (
	(r'<\s*(script|iframe|object|embed|applet|frame|frameset|form|input|button|base|meta|link|style|svg)\b',
	 'znacznik wykonywalny albo strukturalny'),
	(r'\son[a-z]{3,}\s*=\s*["\']', 'atrybut zdarzenia (onclick, onerror...)'),
	(r'(javascript|vbscript|data\s*:\s*text/html)\s*:', 'adres z wykonywalnym schematem'),
	(r'\b(eval|atob|unescape|setTimeout|setInterval)\s*\(', 'wywołanie funkcji JS'),
	(r'String\.fromCharCode|document\.(write|cookie)|window\.location|location\.href',
	 'manipulacja dokumentem albo przekierowanie'),
	(r'<\?php|<\?=|<%', 'kod serwerowy'),
	(r'\\x[0-9a-f]{2}\\x[0-9a-f]{2}', 'ciąg escapowany szesnastkowo'),
)

# Sygnatury ostrzegawcze — wpis przechodzi, ale trafia do raportu.
OSTRZEGAJACE = (
	(r'(display\s*:\s*none|visibility\s*:\s*hidden|opacity\s*:\s*0\b'
	 r'|font-size\s*:\s*0|text-indent\s*:\s*-|left\s*:\s*-\d{3})',
	 'ukryta treść (klasyczny spam SEO)'),
	(r'\b(casino|kasyno|viagra|cialis|payday|loan|escort|betting|poker'
	 r'|replica|crypto|bitcoin|porn|sex\s*cam|xxx)\b', 'słownictwo spamowe'),
	(r'https?://[^\s"\'<>]*\.(ru|cn|tk|top|xyz|click|loan|work)(/|\b)',
	 'odnośnik do domeny typowej dla spamu'),
	(r'base64\s*,[A-Za-z0-9+/=]{500,}', 'duży blok base64 w treści'),
)


def skanuj(kod):
	"""Szuka w HTML-u śladów wstrzykniętej treści.

	Zwraca dwie listy opisów: blokujące i ostrzegawcze.
	"""
	blokady, ostrzezenia = [], []
	for wzor, opis in BLOKUJACE:
		if re.search(wzor, kod, flags=re.I):
			blokady.append(opis)
	for wzor, opis in OSTRZEGAJACE:
		if re.search(wzor, kod, flags=re.I):
			ostrzezenia.append(opis)
	return blokady, ostrzezenia


# --------------------------------------------------------------- konwersja

# Wzorzec z 399 kotwic w zakresie migracji: klikalna ikonka `galeria.png`
# prowadząca do albumu w Google Photos. Sam odnośnik nie ma tekstu, więc
# czytnik ekranu nie ma czego przeczytać — stąd zamiana na przycisk.
KOTWICA_ALBUMU = re.compile(
	r'<a\b[^>]*href="(https://photos\.(?:app\.goo\.gl|google\.com)/[^"]+)"[^>]*>'
	r'(?:(?!</a>).)*?<img\b(?:(?!</a>).)*?</a>',
	re.S | re.I
)

# Znacznik przenoszony przez `na_bloki` — nawiasy kwadratowe przechodzą
# przez sprzątanie HTML-a nietknięte, a adres albumu nie ma spacji.
ZNACZNIK = '[[GALERIA|{}]]'

# Akapit ze samym znacznikiem, opcjonalnie poprzedzony krótkim akapitem
# z etykietą („Grupa Żabki”) — tak stara strona opisywała wiele albumów
# w jednym artykule.
BLOK_ZNACZNIKA = re.compile(
	r'(?:<!-- wp:paragraph -->\n<p>(?P<etykieta>[^<>\[\]]{1,40})</p>\n<!-- /wp:paragraph -->\n\n)?'
	r'<!-- wp:paragraph -->\n<p>\[\[GALERIA\|(?P<adres>[^\]]+)\]\]</p>\n<!-- /wp:paragraph -->'
)

ZNACZNIK_W_TEKSCIE = re.compile(r'\[\[GALERIA\|([^\]]+)\]\]')

# Akapit, w którym znacznik siedzi razem z tekstem albo z `<br />`.
AKAPIT = re.compile(r'<!-- wp:paragraph -->\n<p>(.*?)</p>\n<!-- /wp:paragraph -->', re.S)


def przycisk_albumu(adres, etykieta=''):
	"""Blok przycisku w wariancie `is-style-galeria` z motywu.

	Motyw dokłada przy renderowaniu adnotację „(album w serwisie Google
	Zdjęcia)" dla czytników ekranu — patrz `przedszkole_linki_zewnetrzne()`.
	"""
	tekst_przycisku = 'Zobacz zdjęcia'
	if etykieta:
		tekst_przycisku += f' — {etykieta}'
	return (
		'<!-- wp:buttons -->\n'
		'<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-galeria"} -->\n'
		'<div class="wp-block-button is-style-galeria">'
		f'<a class="wp-block-button__link wp-element-button" href="{html.escape(adres, quote=True)}">'
		f'{html.escape(tekst_przycisku)}</a></div>\n'
		'<!-- /wp:button --></div>\n'
		'<!-- /wp:buttons -->'
	)


def tresc_wpisu(kod):
	"""HTML artykułu Joomli → treść w blokach Gutenberga.

	Zwraca parę: (bloki, liczba albumów).
	"""
	albumy = KOTWICA_ALBUMU.findall(kod)
	kod = KOTWICA_ALBUMU.sub(lambda m: ZNACZNIK.format(m.group(1)), kod)
	bloki = na_bloki(kod)

	# Etykietę z osobnego akapitu wciągamy do przycisku, ale tylko gdy wpis ma
	# kilka albumów. Przy jednym albumie poprzedzający akapit to zwykłe zdanie.
	wiele = len(albumy) > 1

	def zamien(m):
		etykieta = (m.group('etykieta') or '').strip() if wiele else ''
		przycisk = przycisk_albumu(m.group('adres'), etykieta)
		if not wiele and m.group('etykieta'):
			# Akapit nie jest etykietą — zostaje na swoim miejscu.
			return (f'<!-- wp:paragraph -->\n<p>{m.group("etykieta")}</p>\n'
			        f'<!-- /wp:paragraph -->\n\n{przycisk}')
		return przycisk

	bloki = BLOK_ZNACZNIKA.sub(zamien, bloki)

	# Część odnośników stała w akapicie razem z tekstem albo z `<br />`.
	# Taki akapit trzeba rozciąć, bo przycisk jest blokiem, nie treścią wiersza.
	bloki = AKAPIT.sub(rozbij_akapit, bloki)

	# Zostaje to, co siedziało w liście albo w tabeli — tam przycisk nie ma
	# gdzie stanąć, więc zostaje zwykłym odnośnikiem z czytelnym tekstem.
	bloki = ZNACZNIK_W_TEKSCIE.sub(
		lambda m: f'<a href="{html.escape(m.group(1), quote=True)}">Zobacz zdjęcia</a>',
		bloki
	)

	# Pozycja listy dopuszcza tylko treść liniową. `<div>` w środku przechodzi
	# przez przeglądarkę, ale edytor bloków zgłasza wtedy „nieoczekiwaną treść”
	# i personel widzi ostrzeżenie przy każdej próbie edycji wpisu.
	bloki = re.sub(
		r'<li>.*?</li>',
		lambda m: re.sub(r'</?div[^>]*>', '', m.group(0)),
		bloki, flags=re.S
	)
	bloki = re.sub(r'<li>\s+', '<li>', bloki)
	bloki = re.sub(r'\s+</li>', '</li>', bloki)

	return bloki, len(albumy)


def rozbij_akapit(m):
	"""Akapit ze znacznikiem albumu → akapity i przyciski osobno."""
	wnetrze = m.group(1)
	if not ZNACZNIK_W_TEKSCIE.search(wnetrze):
		return m.group(0)
	czesci, pozycja = [], 0
	for znacznik in ZNACZNIK_W_TEKSCIE.finditer(wnetrze):
		czesci.append(akapit(wnetrze[pozycja:znacznik.start()]))
		czesci.append(przycisk_albumu(znacznik.group(1)))
		pozycja = znacznik.end()
	czesci.append(akapit(wnetrze[pozycja:]))
	return '\n\n'.join(c for c in czesci if c)


def kategoria_i_autor(sciezka):
	"""Ścieżka kategorii Joomli → (slug kategorii WP, login autora).

	Autorstwo wyprowadzamy z kategorii, nie z `created_by` — każda autorka
	pisała dla kilku grup, więc oryginalny autor nic nie mówi o grupie.
	"""
	for segment in (sciezka or '').split('/'):
		# `kotkiorojekty` to literówka w drzewie kategorii starej strony.
		nazwa = re.sub(r'(o?projekty)$', '', segment)
		if nazwa in GRUPY:
			return nazwa, f'grupa-{nazwa}'
	return DOMYSLNA_KATEGORIA, DOMYSLNY_AUTOR


# ------------------------------------------------------------------ dane

def artykuly():
	"""Artykuły w zakresie migracji, najnowsze pierwsze."""
	# Znaki końca linii i tabulatory z edytorów windowsowych rozjechałyby
	# podział wierszy wyniku — spłaszczamy je w SQL-u.
	plaska = "REPLACE(REPLACE(REPLACE({}, '\\r', ' '), '\\n', ' '), '\\t', ' ')"
	return sql(
		'SELECT a.id, a.alias, a.created, c.path, '
		+ plaska.format('a.title') + ', '
		+ plaska.format('CONCAT(a.introtext, a.`fulltext`)') + ' '
		'FROM l6hwz_content a LEFT JOIN l6hwz_categories c ON c.id = a.catid '
		f"WHERE a.state = 1 AND a.created >= '{OD_DATY}' "
		'ORDER BY a.created DESC'
	)


def zalaczniki():
	"""{id artykułu: [nazwy plików]} — pliki zostały na FTP starej strony."""
	mapa = {}
	for wiersz in sql(
		"SELECT parent_id, COALESCE(NULLIF(display_name, ''), filename) "
		'FROM l6hwz_attachments WHERE state = 1'
	):
		mapa.setdefault(int(wiersz[0]), []).append(wiersz[1] if len(wiersz) > 1 else '?')
	return mapa


def zbuduj(limit=None):
	"""Plan importu + raport z tego, co odpadło."""
	pliki = zalaczniki()
	wpisy, pominiete, podejrzane, braki, na_stronach = [], [], [], [], []
	wyciete_obrazki = 0

	for wiersz in artykuly():
		if limit and len(wpisy) >= limit:
			break
		wiersz += [''] * (6 - len(wiersz))
		jid, alias, created, sciezka, tytul, kod = wiersz[:6]
		jid = int(jid)

		# Kadra, jadłospis i opisy specjalistów są już treścią stron
		# (patrz `migracja_tresci.py`) — jako wpisy byłyby duplikatem.
		if jid in ARTYKULY_STRON:
			na_stronach.append((jid, tytul, sciezka))
			continue

		blokady, ostrzezenia = skanuj(kod)
		if blokady:
			podejrzane.append((jid, tytul, 'POMINIETY', blokady))
			continue
		if ostrzezenia:
			podejrzane.append((jid, tytul, 'zaimportowany', ostrzezenia))

		if not tekst(kod):
			pominiete.append((jid, tytul, created, pliki.get(jid, [])))
			continue

		wyciete_obrazki += len(re.findall(r'<img\b', kod, flags=re.I))
		tresc, albumy = tresc_wpisu(kod)
		if not tresc.strip():
			pominiete.append((jid, tytul, created, pliki.get(jid, [])))
			continue

		kategoria, autor = kategoria_i_autor(sciezka)
		wpisy.append({
			'joomla_id': jid,
			'tytul': html.unescape(tytul).strip(),
			'slug': alias,
			'data_utc': created,
			'kategoria': kategoria,
			'autor': autor,
			'albumy': albumy,
			'tresc': tresc,
		})

		if jid in pliki:
			braki.append((jid, tytul, pliki[jid]))

	return {
		'wpisy': wpisy,
		'pominiete': pominiete,
		'podejrzane': podejrzane,
		'na_stronach': na_stronach,
		'braki_plikow': braki,
		'wyciete_obrazki': wyciete_obrazki,
	}


# ----------------------------------------------------------------- import

IMPORTER = r'''<?php
/* Import wpisów z planu migracji. Idempotentny — wpis rozpoznajemy po
   `_joomla_id`, więc powtórny przebieg aktualizuje, a nie duplikuje. */

$wpisy = json_decode( file_get_contents( __DIR__ . '/_wpisy.json' ), true );

$autorzy = array();
$terminy = array();
$nowe = 0;
$zmienione = 0;
$bledy = 0;

foreach ( $wpisy as $w ) {
	$login = $w['autor'];
	if ( ! isset( $autorzy[ $login ] ) ) {
		$user = get_user_by( 'login', $login );
		$autorzy[ $login ] = $user ? (int) $user->ID : 0;
	}
	if ( ! $autorzy[ $login ] ) {
		echo "BLAD {$w['joomla_id']}: brak konta $login\n";
		++$bledy;
		continue;
	}

	$slug_kat = $w['kategoria'];
	if ( ! isset( $terminy[ $slug_kat ] ) ) {
		$term = get_term_by( 'slug', $slug_kat, 'category' );
		$terminy[ $slug_kat ] = $term ? (int) $term->term_id : 0;
	}
	if ( ! $terminy[ $slug_kat ] ) {
		echo "BLAD {$w['joomla_id']}: brak kategorii $slug_kat\n";
		++$bledy;
		continue;
	}

	$istniejace = get_posts( array(
		'post_type'   => 'post',
		'post_status' => 'any',
		'numberposts' => 1,
		'meta_key'    => '_joomla_id',
		'meta_value'  => $w['joomla_id'],
		'fields'      => 'ids',
	) );

	$dane = array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_title'    => $w['tytul'],
		'post_name'     => $w['slug'],
		'post_content'  => $w['tresc'],
		'post_author'   => $autorzy[ $login ],
		/* Joomla trzyma `created` w UTC, WordPress chce obu wersji. */
		'post_date_gmt' => $w['data_utc'],
		'post_date'     => get_date_from_gmt( $w['data_utc'] ),
	);

	if ( $istniejace ) {
		$dane['ID'] = (int) $istniejace[0];
		$id = wp_update_post( $dane, true );
		$etykieta = 'AKT';
	} else {
		$id = wp_insert_post( $dane, true );
		$etykieta = 'NOWY';
	}

	if ( is_wp_error( $id ) ) {
		echo "BLAD {$w['joomla_id']}: ", $id->get_error_message(), "\n";
		++$bledy;
		continue;
	}

	wp_set_post_categories( $id, array( $terminy[ $slug_kat ] ), false );
	update_post_meta( $id, '_joomla_id', $w['joomla_id'] );

	if ( 'NOWY' === $etykieta ) {
		++$nowe;
	} else {
		++$zmienione;
	}
}

echo "\nnowe: $nowe   zaktualizowane: $zmienione   bledy: $bledy\n";
'''


def zastosuj(wpisy):
	"""Wgrywa plan do WordPressa przez `wp eval-file`."""
	plan = PROJEKT / 'wp' / '_wpisy.json'
	skrypt = PROJEKT / 'wp' / '_wpisy.php'
	plan.write_text(json.dumps(wpisy, ensure_ascii=False), encoding='utf-8')
	skrypt.write_text(IMPORTER, encoding='utf-8')
	try:
		proc = subprocess.run(
			['ddev', 'exec', 'wp', '--path=wp', 'eval-file', 'wp/_wpisy.php'],
			cwd=PROJEKT, capture_output=True, text=True, stdin=subprocess.DEVNULL
		)
		# Pod PHP 8.5 wp-cli zalewa stderr ostrzeżeniami z własnej biblioteki.
		print('\n'.join(
			w for w in (proc.stdout + proc.stderr).splitlines()
			if 'Using null as an array offset' not in w and w.strip()
		))
	finally:
		plan.unlink(missing_ok=True)
		skrypt.unlink(missing_ok=True)


# ------------------------------------------------------------------ raport

def raport(plan):
	wpisy = plan['wpisy']
	print(f'Wpisów do importu: {len(wpisy)}')

	rozklad = {}
	for w in wpisy:
		rozklad[w['kategoria']] = rozklad.get(w['kategoria'], 0) + 1
	for kat, ile in sorted(rozklad.items(), key=lambda p: -p[1]):
		print(f'  {kat:12} {ile:4d}')

	z_albumem = sum(1 for w in wpisy if w['albumy'])
	albumow = sum(w['albumy'] for w in wpisy)
	print(f'\nPrzyciski do albumów: {albumow} w {z_albumem} wpisach')
	print(f'Obrazków wyciętych z treści (pliki zostały na FTP): {plan["wyciete_obrazki"]}')

	print('\n--- SKAN TRESCI ---')
	if not plan['podejrzane']:
		print('  Czysto: zero sygnatur wstrzykniętej treści.')
	for jid, tytul, los, powody in plan['podejrzane']:
		print(f'  [{los}] {jid} „{tytul}"')
		for p in powody:
			print(f'      - {p}')

	if plan['na_stronach']:
		print('\n--- POMINIETE (treść trafiła na strony) ---')
		for jid, tytul, sciezka in plan['na_stronach']:
			print(f'  {jid}  „{tytul}" ({sciezka})')

	if plan['pominiete']:
		print('\n--- POMINIETE (pusta treść) ---')
		print('  Artykuły bez treści — na starej stronie były samą zajawką dla')
		print('  załącznika. Pliki zostały na FTP, więc nie ma z czego zrobić wpisu.')
		for jid, tytul, data, pl in plan['pominiete']:
			pliki = ', '.join(pl) if pl else 'bez załączników'
			print(f'  {jid}  {data[:10]}  „{tytul}" — {pliki}')

	if plan['braki_plikow']:
		print('\n--- ZAIMPORTOWANE, ALE BEZ ZALACZNIKOW ---')
		for jid, tytul, pl in plan['braki_plikow']:
			print(f'  {jid}  „{tytul}" — {", ".join(pl)}')


if __name__ == '__main__':
	limit = None
	if '--limit' in sys.argv:
		limit = int(sys.argv[sys.argv.index('--limit') + 1])

	plan = zbuduj(limit)
	WYNIK.write_text(json.dumps(plan['wpisy'], ensure_ascii=False, indent=1), encoding='utf-8')
	raport(plan)
	print(f'\nPlan → {WYNIK.relative_to(PROJEKT)}')

	if '--zastosuj' in sys.argv:
		print('\n--- wgrywanie do WordPressa ---')
		zastosuj(plan['wpisy'])
