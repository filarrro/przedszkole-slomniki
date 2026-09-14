#!/usr/bin/env python3
"""Migracja treści statycznych ze zrzutu Joomli do stron WordPressa.

Nie dotyczy aktualności — te idą osobnym skryptem (Etap 2b).
Tutaj wyłącznie strony, które na starej stronie stały pod konkretnymi
pozycjami menu: kadra, opłaty, rozkład dnia, kontakt i tak dalej.

Mapa „pozycja menu Joomli → artykuł” wzięta z tabeli `l6hwz_menu`,
a nie ze zgadywania po tytułach.

Użycie:
    python3 tools/migracja_tresci.py            # zapis do tools/migracja_tresci.json
    python3 tools/migracja_tresci.py --zastosuj # dodatkowo wgrywa do WordPressa
"""

import html
import json
import re
import subprocess
import sys
from pathlib import Path

PROJEKT = Path(__file__).resolve().parent.parent
WYNIK = PROJEKT / 'tools' / 'migracja_tresci.json'

# Czego brakuje - zbierane w trakcie, drukowane na końcu.
braki = []


# Wyrażenia kolumn trzymane osobno — inaczej cudzysłowy SQL-a i Pythona
# zaczynają ze sobą walczyć w f-stringach.
OPIS = "COALESCE(description, '')"
TRESC = "COALESCE(content, '')"


def plaskie(kolumna):
	"""Wyrażenie SQL spłaszczające pole do jednej linii.

	Treść z Joomli ma `\r\n` z edytorów windowsowych. Bez tego każdy
	akapit stawał się osobnym wierszem wyniku i rozjeżdżał parsowanie.
	"""
	for znak in ('\\r', '\\n', '\\t'):
		kolumna = f"REPLACE({kolumna}, '{znak}', ' ')"
	return kolumna


def sql(zapytanie):
	"""Zwraca wiersze z bazy roboczej Joomli jako listy pól."""
	proc = subprocess.run(
		['ddev', 'mysql', '-N', '--raw', 'joomla', '-e', zapytanie],
		cwd=PROJEKT, capture_output=True, text=True, stdin=subprocess.DEVNULL
	)
	if proc.returncode != 0:
		sys.exit(f'blad SQL: {proc.stderr.strip()}')
	return [w.split('\t') for w in proc.stdout.splitlines() if w.strip()]


def artykuly(ids):
	"""Treść artykułów po ID: {id: (tytul, html)}."""
	lista = ','.join(str(i) for i in ids)
	wiersze = sql(
		f'SELECT id, title, {plaskie("CONCAT(introtext, `fulltext`)")} '
		f'FROM l6hwz_content WHERE id IN ({lista})'
	)
	return {int(w[0]): (w[1], w[2] if len(w) > 2 else '') for w in wiersze}


def opisy_kategorii(ids):
	lista = ','.join(str(i) for i in ids)
	wiersze = sql(
		f"SELECT id, title, {plaskie(OPIS)} "
		f"FROM l6hwz_categories WHERE id IN ({lista})"
	)
	return {int(w[0]): (w[1], w[2] if len(w) > 2 else '') for w in wiersze}


def moduly(ids):
	lista = ','.join(str(i) for i in ids)
	wiersze = sql(
		f"SELECT id, title, {plaskie(TRESC)} "
		f"FROM l6hwz_modules WHERE id IN ({lista})"
	)
	return {int(w[0]): (w[1], w[2] if len(w) > 2 else '') for w in wiersze}


# ---------------------------------------------------------------- konwersja

def sprzataj(kod):
	"""Usuwa z HTML-a Joomli to, czego WordPress nie potrzebuje."""
	if not kod:
		return ''
	kod = re.sub(r'<(script|style)[^>]*>.*?</\1>', '', kod, flags=re.S | re.I)
	kod = re.sub(r'<!--.*?-->', '', kod, flags=re.S)
	# Obrazki i galerie zostaja na starym serwerze - odnotowujemy i tniemy.
	kod = re.sub(r'<img[^>]*>', '', kod, flags=re.I)
	kod = re.sub(r'\{gallery\}.*?\{/gallery\}', '', kod, flags=re.S | re.I)
	# Atrybuty prezentacyjne - wyglad bierzemy z motywu, nie ze starej strony.
	kod = re.sub(r'\s(style|class|lang|width|height|border|cellpadding|cellspacing|align|valign|dir)="[^"]*"', '', kod, flags=re.I)
	kod = re.sub(r'</?(span|font|o:p)[^>]*>', '', kod, flags=re.I)
	kod = re.sub(r'&nbsp;', ' ', kod)
	kod = re.sub(r'[ \t\xa0]+', ' ', kod)
	return kod.strip()


def tekst(kod):
	"""HTML → czysty tekst, do wykrywania pustych fragmentów."""
	return html.unescape(re.sub(r'<[^>]+>', '', kod or '')).strip()


def akapit(tresc):
	tresc = tresc.strip()
	return f'<!-- wp:paragraph -->\n<p>{tresc}</p>\n<!-- /wp:paragraph -->' if tekst(tresc) else ''


def naglowek(tresc, poziom=2):
	return (f'<!-- wp:heading {{"level":{poziom}}} -->\n'
	        f'<h{poziom} class="wp-block-heading">{html.escape(tresc)}</h{poziom}>\n'
	        f'<!-- /wp:heading -->')


def tabela(kod):
	"""Tabela Joomli → blok tabeli WordPressa, bez atrybutów prezentacyjnych."""
	wiersze = re.findall(r'<tr[^>]*>(.*?)</tr>', kod, flags=re.S | re.I)
	if not wiersze:
		return ''
	out = []
	for w in wiersze:
		komorki = re.findall(r'<(td|th)[^>]*>(.*?)</\1>', w, flags=re.S | re.I)
		if not komorki:
			continue
		# Wnetrze komorki splaszczamy do tekstu z <br> - tabele Joomli
		# mialy w srodku akapity, ktore w WordPressie rozpychaja wiersz.
		pola = []
		for _, zawartosc in komorki:
			z = re.sub(r'</p>\s*<p[^>]*>', '<br>', zawartosc, flags=re.I)
			z = re.sub(r'</?p[^>]*>', '', z, flags=re.I)
			z = re.sub(r'(<br\s*/?>\s*)+', '<br>', z, flags=re.I)
			# Uwaga: `str.strip("<br>")` obcina pojedyncze znaki, nie podciag,
			# i rozjezdza `<strong>`. Musi byc wyrazenie regularne.
			z = re.sub(r'^(?:<br>)+|(?:<br>)+$', '', z.strip()).strip()
			pola.append(f'<td>{z}</td>')
		out.append('<tr>' + ''.join(pola) + '</tr>')
	if not out:
		return ''
	return ('<!-- wp:table -->\n<figure class="wp-block-table"><table><tbody>'
	        + ''.join(out) + '</tbody></table></figure>\n<!-- /wp:table -->')


def lista(kod):
	pozycje = re.findall(r'<li[^>]*>(.*?)</li>', kod, flags=re.S | re.I)
	pozycje = [re.sub(r'</?p[^>]*>', '', p).strip() for p in pozycje]
	pozycje = [p for p in pozycje if tekst(p)]
	if not pozycje:
		return ''
	srodek = ''.join(
		f'<!-- wp:list-item -->\n<li>{p}</li>\n<!-- /wp:list-item -->\n' for p in pozycje
	)
	return f'<!-- wp:list -->\n<ul class="wp-block-list">\n{srodek}</ul>\n<!-- /wp:list -->'


def na_bloki(kod):
	"""HTML Joomli → treść w blokach Gutenberga."""
	kod = sprzataj(kod)
	if not kod:
		return ''
	bloki = []
	# Wycinamy kolejno tabele i listy; reszte traktujemy jako akapity.
	wzor = re.compile(r'(<table[^>]*>.*?</table>|<[uo]l[^>]*>.*?</[uo]l>)', re.S | re.I)
	pozycja = 0
	for m in wzor.finditer(kod):
		bloki.extend(akapity(kod[pozycja:m.start()]))
		fragment = m.group(1)
		bloki.append(tabela(fragment) if fragment.lower().startswith('<table') else lista(fragment))
		pozycja = m.end()
	bloki.extend(akapity(kod[pozycja:]))
	return '\n\n'.join(b for b in bloki if b)


def akapity(kod):
	"""Fragment bez tabel i list → lista bloków akapitu."""
	if not tekst(kod):
		return []
	kod = re.sub(r'<br\s*/?>\s*<br\s*/?>', '</p><p>', kod, flags=re.I)
	czesci = re.split(r'</p>\s*|<p[^>]*>|</?div[^>]*>|</?h[1-6][^>]*>', kod, flags=re.I)
	return [akapit(c) for c in czesci if tekst(c)]


# ---------------------------------------------------------------- mapa treści

# Grupa → (id kategorii z opisem, id modułu „zajęcia stałe”, id modułu „logopeda”)
GRUPY = {
	'misie':     (13, 108, 110),
	'zajaczki':  (14, 107, 111),
	'zabki':     (12, 106, 112),
	'kotki':     (15, 105, 113),
	'wiewiorki': (16,  97, 115),
	'jezyki':    (17, 104, 116),
}

# Personel: (id artykułu, nagłówek sekcji)
KADRA_DYREKTOR = 18
KADRA_NAUCZYCIELE = [6, 11, 12, 13, 14, 15, 16, 1335, 1508]
KADRA_SPECJALISCI = [7, 1805, 1813, 1815]
KADRA_ADMINISTRACJA = 8

# Artykuły zużyte przez strony statyczne. Migracja aktualności
# (`migracja_wpisow.py`) importuje tę listę i pomija te artykuły — inaczej
# ta sama treść wychodzi dwa razy: jako strona i jako wpis.
ARTYKULY_STRON = (
	[KADRA_DYREKTOR, KADRA_ADMINISTRACJA, 9, 21, 22, 23, 24, 25, 178, 197, 823, 1274]
	+ KADRA_NAUCZYCIELE + KADRA_SPECJALISCI
)


def zbuduj():
	art = artykuly(ARTYKULY_STRON)
	kat = opisy_kategorii([k[0] for k in GRUPY.values()] + [19])
	mod = moduly([k[1] for k in GRUPY.values()] + [k[2] for k in GRUPY.values()] + [95, 120])
	strony = {}

	def tresc_art(aid):
		return na_bloki(art.get(aid, ('', ''))[1])

	# --- Kadra: składanka z pięciu źródeł ---
	czesci = [naglowek('Dyrektor'), tresc_art(KADRA_DYREKTOR), naglowek('Nauczyciele')]
	for aid in KADRA_NAUCZYCIELE:
		tytul, _ = art.get(aid, ('', ''))
		czesci += [naglowek(tytul.strip(), 3), tresc_art(aid)]
	czesci.append(naglowek('Specjaliści'))
	for aid in KADRA_SPECJALISCI:
		tytul, _ = art.get(aid, ('', ''))
		czesci += [naglowek(tytul.strip(), 3), tresc_art(aid)]
	czesci += [naglowek('Pracownicy administracji i obsługi'), tresc_art(KADRA_ADMINISTRACJA)]
	strony['kadra'] = '\n\n'.join(c for c in czesci if c)

	# --- Strony grup ---
	for slug, (kid, mid_zaj, mid_log) in GRUPY.items():
		czesci = [na_bloki(kat.get(kid, ('', ''))[1])]
		zaj = na_bloki(re.sub(
			r'^\s*<p[^>]*>\s*<strong>\s*Zaj[^<]*dodatkowe\s*:?\s*</strong>\s*</p>', '',
			mod.get(mid_zaj, ('', ''))[1], flags=re.I
		))
		if zaj:
			czesci += [naglowek('Zajęcia dodatkowe'), zaj]
		log = na_bloki(mod.get(mid_log, ('', ''))[1])
		if log:
			czesci += [naglowek('Zajęcia z logopedą'), log]
		strony[slug] = '\n\n'.join(c for c in czesci if c)

	# --- Strony jednoźródłowe ---
	strony['jadlospis'] = tresc_art(22)
	strony['oplaty'] = tresc_art(25)
	strony['dofinansowanie'] = tresc_art(178)
	strony['deklaracja-dostepnosci'] = tresc_art(823)
	strony['kacik-logopedy'] = '\n\n'.join(
		c for c in [naglowek('Godziny pracy logopedy'), na_bloki(kat.get(19, ('', ''))[1])] if c
	)

	# --- Ramowy rozkład dnia: trzy warianty na jednej stronie ---
	warianty = [
		('Misie i Zajączki', 24),
		('Żabki', 197),
		('Kotki, Wiewiórki i Jeżyki', 23),
	]
	czesci = []
	for etykieta, aid in warianty:
		czesci += [naglowek(etykieta), tresc_art(aid)]
	strony['ramowy-rozklad-dnia'] = '\n\n'.join(c for c in czesci if c)

	# --- Kontakt: artykuł + dane z belki nagłówka ---
	strony['kontakt'] = tresc_art(9)

	# --- Dokumenty: sam wstęp; pliki PDF leżą na FTP starej strony ---
	strony['dokumenty'] = tresc_art(21)
	pliki = sql(
		"SELECT COALESCE(NULLIF(display_name,''), filename) FROM l6hwz_attachments "
		'WHERE state=1 AND parent_id=21'
	)
	braki.append(
		'Dokumenty — sama lista plików jest w bazie ({} PDF-ów: {}), ale pliki leżą '
		'na FTP starej strony i nie da się ich podpiąć bez pobrania.'.format(
			len(pliki), ', '.join(w[0] for w in pliki)
		)
	)

	# --- Polityka prywatności: jest wyłącznie kontakt do IOD, nie polityka ---
	strony['polityka-prywatnosci'] = '\n\n'.join(
		c for c in [naglowek('Inspektor Ochrony Danych'), tresc_art(1274)] if c
	)

	# --- Czego nie ma w ogóle ---
	braki.append('O przedszkolu — stara strona nie miała takiej podstrony. '
	             'Najbliższy materiał to artykuł „Witamy w nowym przedszkolu” (id 1) '
	             'i „Uroczyste otwarcie Przedszkola” (id 93), ale to aktualności z 2015–2016, '
	             'nie opis placówki. Treść do napisania od zera.')
	braki.append('Oferta — brak podstrony. Da się złożyć z modułów „Zajęcia stałe” '
	             '(angielski, religia) i ogłoszenia o kółkach zainteresowań, '
	             'ale to grafik zajęć, nie opis oferty. Treść do napisania od zera.')
	braki.append('Grupy (strona nadrzędna) — brak, stare menu prowadziło prosto do listy grup.')
	braki.append('Dla rodziców (strona nadrzędna) — kategoria nie miała opisu; '
	             'pod spodem wisiało 19 artykułów poradnikowych.')
	braki.append('Galeria — brak. Artykuł „Galeria prac plastycznych” (id 191) jest '
	             'niepublikowany i zawiera pusty shortcode Joomli.')
	braki.append('Polityka prywatności — w bazie jest wyłącznie kontakt do Inspektora '
	             'Ochrony Danych (artykuł 1274). Właściwej polityki i klauzuli RODO brak.')
	braki.append('Zdjęcia — 5 obrazków wycięto z treści (1 w Kontakcie, 4 logotypy '
	             'dofinansowania w artykule 178). Pliki są na FTP starej strony.')

	return {s: t for s, t in strony.items() if t.strip()}


def zastosuj(strony):
	"""Wgrywa treść do stron WordPressa po slugu."""
	plan = PROJEKT / 'wp' / '_migracja.json'
	skrypt = PROJEKT / 'wp' / '_migracja.php'
	plan.write_text(json.dumps(strony, ensure_ascii=False), encoding='utf-8')
	skrypt.write_text('''<?php
$strony = json_decode( file_get_contents( __DIR__ . '/_migracja.json' ), true );
foreach ( $strony as $slug => $tresc ) {
	$posty = get_posts( array( 'post_type' => 'page', 'name' => $slug, 'numberposts' => 1, 'post_status' => 'any' ) );
	if ( ! $posty ) {
		echo "POMINIETO (brak strony): $slug\\n";
		continue;
	}
	$wynik = wp_update_post( array( 'ID' => $posty[0]->ID, 'post_content' => $tresc ), true );
	if ( is_wp_error( $wynik ) ) {
		echo "BLAD $slug: ", $wynik->get_error_message(), "\\n";
	} else {
		echo sprintf( "OK  %-26s id=%-4d %6d znakow\\n", $slug, $posty[0]->ID, strlen( $tresc ) );
	}
}
''', encoding='utf-8')
	proc = subprocess.run(
		['ddev', 'exec', 'wp', '--path=wp', 'eval-file', 'wp/_migracja.php'],
		cwd=PROJEKT, capture_output=True, text=True, stdin=subprocess.DEVNULL
	)
	print('\n'.join(
		w for w in (proc.stdout + proc.stderr).splitlines()
		if 'Using null as an array offset' not in w and w.strip()
	))
	plan.unlink(missing_ok=True)
	skrypt.unlink(missing_ok=True)


if __name__ == '__main__':
	wynik = zbuduj()
	WYNIK.write_text(json.dumps(wynik, ensure_ascii=False, indent=1), encoding='utf-8')
	print(f'Zbudowano treść dla {len(wynik)} stron → {WYNIK.relative_to(PROJEKT)}')
	for slug, tresc in sorted(wynik.items()):
		print(f'  {slug:26} {len(tresc):6d} znakow')
	if '--zastosuj' in sys.argv:
		print('\n--- wgrywanie do WordPressa ---')
		zastosuj(wynik)
	print('\n--- CZEGO BRAKUJE ---')
	for b in braki:
		print(f'  * {b}')
