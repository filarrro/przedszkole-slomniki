# Narzędzia pomocnicze

## `podglad.py`

Pobiera stronę z lokalnego WordPressa i zapisuje ją jako pojedynczy plik HTML
z wklejonym CSS i JS. Służy do oglądania wyglądu motywu w panelu podglądu,
który blokuje ładowanie plików podrzędnych z `*.ddev.site`.

```bash
python3 tools/podglad.py /                    # strona główna  -> podglad.html
python3 tools/podglad.py /aktualnosci/ a.html # lista wpisów   -> a.html
```

Pliki `podglad.html` i `*.podglad.html` są w `.gitignore`.
Do normalnej pracy używaj po prostu http://przedszkole.ddev.site w swojej przeglądarce.

## `struktura.sh`

Odtwarza szkielet treści z Etapu 4: strony (z zagnieżdżeniem), menu główne,
menu w stopce, kategorie, strona główna i strona wpisów.

```bash
tools/struktura.sh
```

Idempotentny w zakresie stron — strona o istniejącym slugu nie powstaje drugi raz,
aktualizowany jest tylko rodzic i kolejność. **Menu są kasowane i budowane od nowa**,
więc ręczne zmiany w menu przepadną.

Skrypt tworzy wyłącznie puste strony. Treść wchodzi w Etapie 7 i przy migracji.

## `skroty.sh`

Tworzy menu „Na skróty" — kafelki na stronie głównej (Etap 7). Trzy pozycje:
Grupy, Dla rodziców, Kontakt, każda z opisem pod tytułem. Kafelek „Galeria"
wypadł 2026-09-14 razem ze stroną; siatka to `auto-fit`, więc rząd wypełnia
się sam i trzy kafelki nie zostawiają dziury.

```bash
tools/skroty.sh
```

Idempotentny: istniejącego menu nie kasuje i nie dokłada pozycji drugi raz.
Dalsze zmiany — w Wyglądzie → Menu. Opis pod tytułem to pole „Opis" pozycji
menu, widoczne po włączeniu w Opcjach ekranu.

Bez tego menu sekcja „Na skróty" nie pojawia się na stronie głównej.

## `uzytkownicy.sh`

Zakłada konta z Etapu 6: zbiorcze `przedszkole` (rola Editor — dyrekcja) i sześć
kont grupowych `grupa-misie` … `grupa-kotki` (rola Author — nauczycielki).

```bash
tools/uzytkownicy.sh
```

Idempotentny: istniejącemu kontu poprawia tylko rolę, hasła nie rusza.

Hasła są losowe i **wypisywane raz, na ekranie** — nigdzie ich nie zapisujemy.
Zgub je, a zostaje odzyskiwanie hasła z panelu, które wymaga działającej skrzynki.
Adresy są zmyślone z nazwy konta (`grupa-misie@przedszkoleslomniki.pl`); przed
wdrożeniem muszą to być realne skrzynki — patrz PLAN.md, Etap 6.

Własnych ról nie tworzymy. Natywne Editor i Author pokrywają potrzeby przedszkola,
uzasadnienie i audyt uprawnień są w PLAN.md.

## `kadra_na_blok.py`

Przepisał kafelki na stronie „Kadra" ze zwykłych `core/columns` na blok
`przedszkole/osoba`. **Jednorazowy** — uruchomiony 2026-09-16, w repo zostaje
jako zapis tego, co poszło do bazy.

```bash
python3 tools/kadra_na_blok.py              # podgląd, nic nie zapisuje
python3 tools/kadra_na_blok.py --zastosuj   # wgrywa do WordPressa
```

Idempotentny przez brak roboty: szuka starych kolumn, a po migracji żadnych
już nie ma, więc drugie uruchomienie kończy się „Nic do zrobienia".

**Nowych osób nie dodaje się skryptem.** W edytorze: „Osoba" z kategorii
„Przedszkole", zdjęcie przyciskiem pod kółkiem, reszta wprost w kafelku.
Bez zdjęcia kafelek pokazuje inicjały wyliczone z imienia — nie trzeba ich
nigdzie wpisywać. Zdjęcia w `media/kadra/` wgrywa się przez bibliotekę mediów
albo `wp media import`.

**Zdjęcia muszą być kwadratowe** — kafelek kadruje je do koła. Przygotowanie:

```bash
sips -s format jpeg -c 905 905 zrodlo.png --out media/kadra/awatar-6.jpg
sips -z 800 800 media/kadra/awatar-6.jpg
```

Dwa przebiegi, nie jeden: `sips` łączy `-c` z `-Z` w nieprzewidywalnej kolejności
i wychodzi obrazek mniejszy, niż się prosiło.

Poprzednicy: `kadra_kafelki.py` (proza → kafelki) i `kadra_zdjecie.py`
(wstawianie zdjęć w lewą kolumnę) — oba usunięte razem z migracją, bo parsują
układ, którego na stronie już nie ma.

## `migracja_tresci.py`

Przenosi treści **statyczne** ze zrzutu Joomli do stron WordPressa — kadrę,
opisy grup, rozkład dnia, opłaty, kontakt i resztę podstron spod menu starej
strony. Aktualności to osobna migracja.

```bash
python3 tools/migracja_tresci.py             # podglad: buduje JSON, nic nie zapisuje
python3 tools/migracja_tresci.py --zastosuj  # wgrywa do WordPressa
```

Wymaga bazy roboczej `joomla` w kontenerze — patrz [MIGRACJA.md](../MIGRACJA.md),
sekcja „Jak odtworzyć analizę od zera".

**Nadpisuje treść stron w całości.** Ręczne zmiany w migrowanych stronach przepadną.

Obrazki są wycinane — pliki leżą na FTP starej strony. Skrypt wypisuje na końcu
listę tego, czego w zrzucie nie ma.

Stała `ARTYKULY_STRON` wylicza artykuły zużyte przez strony. `migracja_wpisow.py`
ją importuje i pomija — inaczej ta sama treść wyszłaby i jako strona, i jako wpis.

## `migracja_wpisow.py`

Przenosi **aktualności** — 428 wpisów z lat szkolnych 2023/24–2025/26.
Kategorię i autora wyprowadza ze ścieżki kategorii Joomli (6 grup + Ogłoszenia),
datę z `created` (Joomla trzyma UTC, skrypt przelicza na czas lokalny).

```bash
python3 tools/migracja_wpisow.py             # podglad: buduje plan, nic nie wgrywa
python3 tools/migracja_wpisow.py --zastosuj  # wgrywa do WordPressa
python3 tools/migracja_wpisow.py --limit 20  # probka do obejrzenia
```

Idempotentny: wpis rozpoznaje po metadanej `_joomla_id`, więc powtórny przebieg
aktualizuje, a nie duplikuje. Konwersję HTML → bloki bierze z `migracja_tresci.py`.

**Skan treści przed importem.** Stara strona była zaatakowana, więc każdy artykuł
przechodzi przez `skanuj()`. Sygnatura blokująca (kod wykonywalny, znacznik
`<script>`/`<iframe>`, atrybut zdarzenia, kod PHP) = wpis pominięty i wypisany
w raporcie. Sygnatura ostrzegawcza (ukryta treść, słownictwo spamowe, domena
typowa dla spamu) = wpis przechodzi, ale trafia do raportu. Przebieg z 2026-09-14
nie znalazł ani jednego trafienia w 440 artykułach.

Klikalna ikonka `galeria.png` prowadząca do albumu w Google Photos zamienia się
w przycisk w wariancie `is-style-galeria` z tekstem „Zobacz zdjęcia". Gdy wpis ma
kilka albumów, etykieta z poprzedzającego akapitu („Grupa Żabki") wchodzi do
tekstu przycisku — inaczej strona miałaby sześć identycznych odnośników.

Bez obrazków wyróżniających — dlaczego, patrz [MIGRACJA.md](../MIGRACJA.md).

## `logopeda.sh`

Zakłada kategorię „Kącik logopedy" (slug `logopeda`) i dopasowuje do niej slug
strony o tej samej nazwie. Motyw wiąże stronę z kategorią po slugu, tak samo
jak strony grup, więc oba muszą być identyczne.

```bash
tools/logopeda.sh
```

Idempotentny: istniejącej kategorii nie zakłada drugi raz.

## `migracja_logopedy.py`

Dobiera ze zrzutu Joomli artykuły z kategorii 19 („Logopeda"), których nie
objęła migracja z Etapu 2b — obejmowała tylko lata szkolne 2023/24–2025/26,
a kącik logopedy sięga 2015 roku.

```bash
python3 tools/migracja_logopedy.py              # plan, nic nie wgrywa
python3 tools/migracja_logopedy.py --zastosuj   # wgrywa do WordPressa
```

Wymaga bazy roboczej `joomla`, tej samej, z której korzystają
`migracja_tresci.py` i `migracja_wpisow.py`. Idempotentny: wpisy rozpoznaje
po meta `_joomla_id`, więc powtórny przebieg aktualizuje, a nie duplikuje.

Pomija świadomie artykuł 32 (godziny pracy z 2015, sprzeczne z treścią strony)
i całą kategorię 64 („Archiwum Logopedy" — seria covidowa, na starej stronie
niepublikowana). Bilans importu (27 artykułów w kategorii, 25 finalnie
w WordPressie) jest w [MIGRACJA.md](../MIGRACJA.md).
