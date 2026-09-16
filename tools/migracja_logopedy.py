#!/usr/bin/env python3
"""Import artykułów logopedki ze zrzutu Joomli do kategorii `logopeda`.

Kategoria 19 Joomli („Logopeda") ma 27 opublikowanych artykułów z lat
2015–2026. Migracja z Etapu 2b obejmowała tylko lata szkolne 2023/24–2025/26,
więc do WordPressa weszły cztery — resztę trzeba dobrać teraz.

Czego tu NIE ma i dlaczego:

* **Artykułu 32** („Godziny pracy logopedy", 2015). Podaje poniedziałek
  10:40–14:40, podczas gdy strona „Kącik logopedy" mówi 12:00–16:00. Import
  postawiłby na stronie dwie sprzeczne wersje tej samej informacji.
* **Kategorii 64** („Archiwum Logopedy"). Szesnaście odcinków serii
  #zostańwdomu z lockdownu. Kategoria była na starej stronie **niepublikowana**
  (`published = 0`) — przedszkole samo ją schowało po pandemii.
* **Artykułów w koszu** (`state = -2`, cztery sztuki z marca 2020).

Osobnego kroku „przepnij cztery istniejące wpisy" nie ma, bo nie jest
potrzebny: importer rozpoznaje wpis po meta `_joomla_id` i robi
`wp_set_post_categories( ..., false )`, czyli zastępuje kategorie. Cztery
wpisy siedzące dziś w `Ogłoszeniach` przejdą na `logopeda` w tym samym
przebiegu.

Skanowanie treści i konwersję na bloki bierzemy z `migracja_wpisow.py` —
stara strona była zaatakowana, więc żaden artykuł nie idzie do bazy bez
przejścia przez `skanuj()`.

Użycie:
    python3 tools/migracja_logopedy.py               # buduje plan, nic nie wgrywa
    python3 tools/migracja_logopedy.py --zastosuj    # wgrywa do WordPressa
"""

import html
import json
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from migracja_tresci import sql, tekst  # noqa: E402
from migracja_wpisow import skanuj, tresc_wpisu, zastosuj  # noqa: E402

PROJEKT = Path(__file__).resolve().parent.parent
WYNIK = PROJEKT / 'tools' / 'migracja_logopedy.json'

KATEGORIA_JOOMLI = 19
KATEGORIA = 'logopeda'
AUTOR = 'przedszkole'

# Godziny pracy logopedy z 2015 roku — aktualne stoją w treści strony.
POMIJANE = {32}


def artykuly():
	"""Opublikowane artykuły kategorii 19, najnowsze pierwsze."""
	# Znaki końca linii i tabulatory z edytorów windowsowych rozjechałyby
	# podział wierszy wyniku — spłaszczamy je w SQL-u.
	plaska = "REPLACE(REPLACE(REPLACE({}, '\\r', ' '), '\\n', ' '), '\\t', ' ')"
	return sql(
		'SELECT a.id, a.alias, a.created, '
		+ plaska.format('a.title') + ', '
		+ plaska.format('CONCAT(a.introtext, a.`fulltext`)') + ' '
		'FROM l6hwz_content a '
		f'WHERE a.state = 1 AND a.catid = {KATEGORIA_JOOMLI} '
		'ORDER BY a.created DESC'
	)


def zbuduj():
	"""Plan importu + raport z tego, co odpadło."""
	wpisy, pominiete, podejrzane = [], [], []
	wyciete_obrazki = 0

	for wiersz in artykuly():
		wiersz += [''] * (5 - len(wiersz))
		jid, alias, created, tytul, kod = wiersz[:5]
		jid = int(jid)

		if jid in POMIJANE:
			pominiete.append((jid, tytul, 'pominiety swiadomie'))
			continue

		blokady, ostrzezenia = skanuj(kod)
		if blokady:
			podejrzane.append((jid, tytul, 'POMINIETY', blokady))
			continue
		if ostrzezenia:
			podejrzane.append((jid, tytul, 'zaimportowany', ostrzezenia))

		if not tekst(kod):
			pominiete.append((jid, tytul, 'sama tresc nietekstowa'))
			continue

		wyciete_obrazki += len(re.findall(r'<img\b', kod, flags=re.I))
		tresc, _albumy = tresc_wpisu(kod)

		if not tresc.strip():
			pominiete.append((jid, tytul, 'pusto po wycieciu obrazkow'))
			continue

		wpisy.append({
			'joomla_id': jid,
			'tytul': html.unescape(tytul).strip(),
			'slug': alias,
			'data_utc': created,
			'kategoria': KATEGORIA,
			'autor': AUTOR,
			'albumy': [],
			'tresc': tresc,
		})

	return {
		'wpisy': wpisy,
		'pominiete': pominiete,
		'podejrzane': podejrzane,
		'wyciete_obrazki': wyciete_obrazki,
	}


def raport(plan):
	print(f'Wpisów do importu: {len(plan["wpisy"])}')
	print(f'Wyciętych znaczników <img>: {plan["wyciete_obrazki"]}')

	if plan['pominiete']:
		print('\nPominięte:')
		for jid, tytul, powod in plan['pominiete']:
			print(f'  {jid}  „{tytul}" — {powod}')

	if plan['podejrzane']:
		print('\nZgłoszone przez skaner:')
		for jid, tytul, stan, powody in plan['podejrzane']:
			print(f'  {jid}  „{tytul}" [{stan}] — {"; ".join(powody)}')


if __name__ == '__main__':
	plan = zbuduj()
	WYNIK.write_text(json.dumps(plan['wpisy'], ensure_ascii=False, indent=1), encoding='utf-8')
	raport(plan)
	print(f'\nPlan → {WYNIK.relative_to(PROJEKT)}')

	if '--zastosuj' in sys.argv:
		print('\n--- wgrywanie do WordPressa ---')
		zastosuj(plan['wpisy'])
